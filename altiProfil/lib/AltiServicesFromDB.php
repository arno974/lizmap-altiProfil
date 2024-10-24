<?php
namespace AltiProfil;

Class AltiServicesFromDB {

    protected $Srid = "";
    protected $AltiProfileTable = "";
    protected $Altisource = "";
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
        $this->AltiProfileTable = $altiConfig->getAltiProfileTable();
        $this->Altisource = $altiConfig->getAltisource();
        $this->profilUnit = $altiConfig->getProfilUnit();
        $this->AltiResolution = $altiConfig->getAltiResolution();

        // Get project config: override table and source per project
        $this->repository = $repository;
        $this->project = $project;
        $p = \lizmap::getProject($repository.'~'.$project);
        if( $p ){
            $alti_config_file = $p->getQgisPath() . '.alti';
            if (file_exists($alti_config_file)) {
                $config = parse_ini_file($alti_config_file, True);
                if ($config and array_key_exists('altiProfil', $config)) {
                    if (array_key_exists('srid', $config['altiProfil'])) {
                        $this->Srid = $config['altiProfil']['srid'];
                    }
                    if (array_key_exists('altiProfileTable', $config['altiProfil'])) {
                        $this->AltiProfileTable = $config['altiProfil']['altiProfileTable'];
                    }
                    if (array_key_exists('altisource', $config['altiProfil'])) {
                        $this->Altisource = $config['altiProfil']['altisource'];
                    }
                }
            }
        }
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

        $sql = sprintf('
            SELECT ST_Value(
                "%1$s".rast,
                ST_Transform(ST_SetSRID(ST_MakePoint(%2$f,%3$f),4326),%4$s)
            ) as z
            FROM "%1$s"
            WHERE ST_Intersects(
                "%1$s".rast,
                ST_Transform(ST_SetSRID(ST_MakePoint(%2$f,%3$f),4326),%4$s)

        )',
            $this->AltiProfileTable,
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

        //ref: https://blog.mathieu-leplatre.info/drape-lines-on-a-dem-with-postgis.html
        $sql = sprintf('
            WITH
                line AS(
                    -- From an arbitrary line
                    SELECT
                        ST_MakeLine(
                            ST_Transform(ST_SetSRID(ST_MakePoint(%2$f, %3$f),4326), %4$s),
                            ST_Transform(ST_SetSRID(ST_MakePoint(%5$f, %6$f),4326), %4$s)
                        )
                    AS geom
                ),
                linemesure AS(
                    -- Add a mesure dimension to extract steps
                    SELECT
                        ST_AddMeasure(line.geom, 0, ST_Length(line.geom)) as linem,
                        generate_series(
                            0,
                            ST_Length(line.geom)::int,
                            --for very long line we reduce the steps
                            CASE
                                WHEN ST_Length(line.geom)::int < 1000 THEN %8$d
                                ELSE %8$d*5
                            END
                        ) as i,
                        CASE
                            WHEN ST_Length(line.geom)::int < 1000 THEN %8$d
                            ELSE %8$d*5
                        END as resolution
                    FROM line
                ),
                points2d AS (
                    SELECT ST_GeometryN(ST_LocateAlong(linem, i), 1) AS geom, resolution FROM linemesure ORDER BY i
                ),
                cells AS (
                    -- Get DEM elevation for each
                    SELECT
                        p.geom AS geom,
                        ST_Value("%1$s".rast, 1, p.geom) AS val,
                        resolution
                    FROM "%1$s", points2d p
                    WHERE ST_Intersects("%1$s".rast, p.geom)
                ),
                -- Instantiate 3D points
                points3d AS (
                    SELECT ST_SetSRID(
                                ST_MakePoint(ST_X(geom), ST_Y(geom), val),
                                %4$s
                            ) AS geom, resolution FROM cells
                ),
                line3D AS(
                    SELECT ST_MakeLine(geom) as geom, MAX(resolution) as resolution FROM points3d
                ),
                xz AS(
                    SELECT (ST_DumpPoints(geom)).geom AS geom,
                    ST_LineLocatePoint(geom, (ST_DumpPoints(geom)).geom) * ST_Length(geom) AS loc,
                    resolution
                    FROM line3D
                )
            -- Build 3D line from 3D points
            SELECT loc AS x, ST_Z(geom) as y, ST_X(geom) as lon, ST_Y(geom) as lat, resolution FROM xz',
            $this->AltiProfileTable,
            $p1Lon, $p1Lat,
            $this->Srid,
            $p2Lon, $p2Lat,
            $this->profilUnit,
            $this->AltiResolution,
        );
        $cnx = \jDb::getConnection('altiProfil');
        $qResult = $cnx->query($sql);
        $x = array();
        $y = array();
        $customdata = array();
        $resolution = "";
        while($row=$qResult->fetch())  {
            $x[] = $row->x;
            $y[] = $row->y;
            $customdata[] = [["lon" => $row->lon, "lat" => $row->lat]];
            $resolution = $row->resolution;
        }
        //slope
        $sql = sprintf('
            WITH
                line AS(
                    -- Make the line from the input coordinates
                    SELECT
                        ST_MakeLine(
                            ST_Transform(ST_SetSRID(ST_MakePoint(%2$f, %3$f), 4326), %4$s),
                            ST_Transform(ST_SetSRID(ST_MakePoint(%5$f, %6$f), 4326), %4$s)
                        )
                    AS geom
                ), RasterCells AS (
                    -- Intersect the line with the DEM
                    SELECT ST_Clip("%1$s".rast, line.geom, -9999, TRUE) as rast
                    FROM "%1$s", line
                    WHERE ST_Intersects("%1$s".rast, line.geom)
                ), rasterSlopStat AS (
                    -- Compute the slope and the statistics
                    Select (ST_SummaryStatsAgg(ST_Slope(rast, 1, \'32BF\', \'%7$s\', 1.0), 1, TRUE, 1)).*
                    FROM RasterCells
                )
                SELECT  (rasterSlopStat).count,
                        Round((rasterSlopStat).min::numeric, 2) as min_slope,
                        Round((rasterSlopStat).max::numeric, 2) as max_slope,
                        Round((rasterSlopStat).mean::numeric, 2) as mean_slope
                        FROM rasterSlopStat

            ',
            $this->AltiProfileTable,
            $p1Lon, $p1Lat,
            $this->Srid,
            $p2Lon, $p2Lat,
            $this->profilUnit
        );
        $cnx = \jDb::getConnection('altiProfil');
        $qResult = $cnx->query($sql);
        $slope = $qResult->fetch(\PDO::FETCH_ASSOC);
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
