<?php
namespace AltiProfil;

Class AltiServicesFromDB {

    protected $Srid = "";
    protected $Altisource = "";
    protected $AltiResolution = "";
    protected $profilUnit = "";
    protected $repository = Null;
    protected $project = Null;

    /**
     * @var \AltiProfil\AltiConfig
     */
    protected $config;

    function __construct(\AltiProfil\AltiConfig $altiConfig, $repository, $project)
    {
        $this->config = $altiConfig;
        $this->Srid = $altiConfig->getSrid();
        $this->Altisource = $altiConfig->getAltisource();
        $this->profilUnit = $altiConfig->getProfilUnit();
        $this->AltiResolution = $altiConfig->getAltiResolution();

        // Get project config: override table and source per project
        $this->repository = $repository;
        $this->project = $project;
        $this->config->setProjectConfig($repository, $project);
    }

    /**
     * Get alti from one point based on database
    **/
    public function getAlti($lon, $lat){
        $queryalti = $this->queryAlti($lon, $lat);
        return $queryalti;
    }

    /**
     * Alti SQL Query
    **/
    private function queryAlti($lon, $lat) {

        /**
           * Point elevation query.
           *
           * - The queried point is built once in a CTE ("pt") and reused
           *   everywhere, so the spatial filter and ST_Value can never diverge.
           * - ST_Value uses bilinear resampling (PostGIS >= 3.2,
           *   https://postgis.net/docs/RT_ST_Value.html): the elevation is
           *   interpolated between the 4 neighbouring cells, which matters for
           *   sub-metric resolutions.
           *
           * PERFORMANCE - do not "simplify" the following (measured cost):
           * - "ST_ConvexHull(r.rast) && pt.geom" is written explicitly instead
           *   of ST_Intersects(rast, geom): the raster variant of ST_Intersects
           *   is NOT inlined by the planner, so the GiST expression index on
           *   st_convexhull(rast) would be ignored (full scan of every tile).
           *   For a north-up raster the convex hull IS the tile rectangle, and
           *   ST_Intersects without a band tests that same hull: equivalent.
           * - The NULL test lives in ORDER BY, NOT in the WHERE clause: inside
           *   a WHERE the planner may reorder it BEFORE the spatial filter and
           *   run ST_Value (tile decompression) on every tile of the table.
           *   ORDER BY is always evaluated after the filter, on the 1-2
           *   candidate tiles only. A point on a tile boundary matches several
           *   tiles and some return NULL: this ordering picks the tile that
           *   actually holds the value.
        **/
        $sql = sprintf('
            WITH pt AS (
                SELECT ST_Transform(ST_SetSRID(ST_MakePoint(%2$.8f, %3$.8f), 4326), %4$s) AS geom
            )
            SELECT ROUND(ST_Value(r.rast, 1, pt.geom, true, \'bilinear\')::numeric,2)::float8 AS z
            FROM %1$s r, pt
            WHERE ST_ConvexHull(r.rast) && pt.geom
            ORDER BY ST_Value(r.rast, 1, pt.geom) IS NOT NULL DESC
            LIMIT 1',
            $this->config->quotedSchemaTableName(),
            $lon,
            $lat,
            $this->Srid
        );

        $cnx = \jDb::getConnection( 'altiProfil' );
        $qResult = $cnx->query( $sql );
        $result = array("elevations"=>[$qResult->fetch(\PDO::FETCH_ASSOC)]);
        return $result;
    }

    /**
     * Get alti from database based on one point
    **/
    public function getProfil($p1Lon, $p1Lat, $p2Lon, $p2Lat){
        $getProfil =$this->queryProfil($p1Lon, $p1Lat, $p2Lon, $p2Lat);
        return $getProfil;
    }

    /**
     * SQL Query Profil from database
    **/
    protected function queryProfil($p1Lon, $p1Lat, $p2Lon, $p2Lat){

        /**
           * Elevation profile query. Pipeline:
           *   line    : transect built from the two input points (raster SRID)
           *   params  : sampling step = configured resolution, degraded so the
           *             profile never exceeds ~1500 samples (a chart is ~1000px
           *             wide; more points cost SQL/JSON/render time for nothing).
           *             The LEAST(..., 10000) in samples is a hard safety cap.
           *   samples : regular points via ST_LineInterpolatePoint. CEIL + the
           *             two LEAST() guarantee the LAST sample is exactly the
           *             end point (a plain generate_series over distances
           *             misses it unless len is a multiple of step).
           *   profile : one indexed probe per sample (see PERFORMANCE below).
           *             LEFT JOIN: a sample outside raster coverage/nodata is
           *             KEPT with z NULL -> visible gap at the right distance
           *             (Plotly breaks the line on nulls).
           *   final   : x = i*step BY CONSTRUCTION - never recompute distances
           *             from a rebuilt geometry, dropped samples would shift
           *             the x axis. slope = along-profile dz/ddist between
           *             consecutive samples (LAG), signed, in profilUnit
           *             (DEGREES/PERCENT). ORDER BY s.i is the ONLY ordering
           *             guarantee: never rely on a CTE's internal ORDER BY,
           *             joins are free to reorder rows.
           *
           * PERFORMANCE - same two rules as queryAlti (they were learned the
           * hard way: 37s on a 52km transect before, ~0.4s after):
           * - "ST_ConvexHull(r.rast) && s.geom" instead of ST_Intersects:
           *   the raster ST_Intersects is not inlined, the expression index
           *   would be ignored -> full tile scan PER SAMPLE.
           * - The NULL guard is in ORDER BY, never in WHERE (qual reordering
           *   would run ST_Value on every tile). It fixes real holes: without
           *   it, samples on tile boundaries randomly pick the empty neighbour.
           *
           * Slope min/max/mean are aggregated in PHP from the LAG column:
           * the former ST_Slope query did not scale (per-cell MapAlgebra,
           * 37s on 52km) and measured TERRAIN slope, while this measures the
           * slope ALONG the profile - assumed semantic change.
        **/
        $sql = sprintf('
            WITH
                line AS (
                    -- From an arbitrary line
                    SELECT ST_MakeLine(
                               ST_Transform(ST_SetSRID(ST_MakePoint(%2$.8f, %3$.8f), 4326), %4$s),
                               ST_Transform(ST_SetSRID(ST_MakePoint(%5$.8f, %6$.8f), 4326), %4$s)
                           ) AS geom
                ),
                params AS (
                    -- Step: config resolution, degraded to cap the profile at ~1500 samples
                    SELECT geom,
                           ST_Length(geom) AS len,
                           GREATEST(%8$s, ST_Length(geom) / 1500.0) AS step
                    FROM line
                    WHERE ST_Length(geom) > 0
                ),
                samples AS (
                    -- Regular samples along the line, endpoint included, capped at 10000
                    SELECT i,
                           LEAST(i * step, len)                                      AS dist,
                           ST_LineInterpolatePoint(geom, LEAST(i * step / len, 1.0)) AS geom,
                           step
                    FROM params,
                         generate_series(0, LEAST(CEIL(len / step)::int, 10000)) AS i
                ),
                profile AS (
                    -- Elevation per sample; LATERAL dedups tile-border matches
                    SELECT s.i,
                           s.dist,
                           v.z,
                           ST_X(s.geom) AS lon,
                           ST_Y(s.geom) AS lat,
                           s.step
                    FROM samples s
                    LEFT JOIN LATERAL (
                        SELECT ST_Value(r.rast, 1, s.geom, true, \'bilinear\') AS z
                        FROM %1$s r
                        WHERE ST_ConvexHull(r.rast) && s.geom
                        ORDER BY ST_Value(r.rast, 1, s.geom) IS NOT NULL DESC
                        LIMIT 1
                    ) v ON true
                )
            SELECT ROUND(dist::numeric, 2)::float8 AS x,
                   ROUND(z::numeric, 2)::float8    AS y,
                   lon,
                   lat,
                   step AS resolution,
                   ROUND((
                       CASE
                           WHEN dist - LAG(dist) OVER w > 0
                                AND z IS NOT NULL
                                AND LAG(z) OVER w IS NOT NULL
                           THEN CASE WHEN \'%7$s\' = \'DEGREES\'
                                     THEN degrees(atan((z - LAG(z) OVER w) / (dist - LAG(dist) OVER w)))
                                     ELSE (z - LAG(z) OVER w) / (dist - LAG(dist) OVER w) * 100
                                END
                       END
                   )::numeric, 1)::float8 AS slope
            FROM profile
            WINDOW w AS (ORDER BY i)
            ORDER BY i',
            $this->config->quotedSchemaTableName(),
            $p1Lon, $p1Lat,
            $this->Srid,
            $p2Lon, $p2Lat,
            $this->profilUnit,
            number_format((float) $this->AltiResolution, 2, '.', '')
        );

        $cnx = \jDb::getConnection('altiProfil');
        $qResult = $cnx->query($sql);
        $x = array();
        $y = array();
        $customdata = array();
        $resolution = "";
        $slopes = array();
        while($row=$qResult->fetch())  {
            $x[] = $row->x;
            $y[] = $row->y;
            $customdata[] = [["lon" => $row->lon, "lat" => $row->lat, "slope" => $row->slope]];
            $resolution = $row->resolution;
            if ($row->slope !== null) { $slopes[] = abs((float) $row->slope); }
        }
        //Remove previous SQL function to calculate slope and replace it with a PHP calculation 
        //=> Faster and in this version the slope has been added for each cooridnates inside the first SQL Query
        $slope = false;
        if (count($slopes) > 0) {
            $slope = array(
                "count"      => count($slopes),
                "min_slope"  => round(min($slopes), 2),
                "max_slope"  => round(max($slopes), 2),
                "mean_slope" => round(array_sum($slopes) / count($slopes), 2),
            );
        }        

        $data = [ [
            "x" => $x,
            "y" => $y,
            "customdata" => $customdata,
            "srid" => $this->Srid,
            "resolution" => $resolution,
            "altisource" => $this->Altisource,
            "slope" => $slope
         ] ];

        return $data;
    }
}
