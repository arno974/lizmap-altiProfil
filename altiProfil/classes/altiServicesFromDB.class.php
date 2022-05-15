<?php

Class GetAltiServicesFromDB {

    protected $Srid = "";
    protected $AltiProfileTable = "";
    protected $Altisource = "";
    protected $profilUnit = "";
    protected $repository = Null;
    protected $project = Null;

    function __construct($repository, $project) {

        // Get global config
        $altiProfilConfigFile = jApp::configPath('altiProfil.ini.php');
        $localConfig = new jIniFileModifier($altiProfilConfigFile);
        $this->Srid = $localConfig->getValue('srid', 'altiProfil');
        $this->AltiProfileTable = $localConfig->getValue('altiProfileTable', 'altiProfil');
        $this->Altisource = $localConfig->getValue('altisource', 'altiProfil');
        $this->profilUnit = $localConfig->getValue('profilUnit', 'altiProfil');
        $this->AltiResolution = $localConfig->getValue('altiresolution', 'altiProfil');

        // Get project config: override table and source per project
        $this->repository = $repository;
        $this->project = $project;
        $p = lizmap::getProject($repository.'~'.$project);
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
                %1$s.rast,
                ST_Transform(ST_SetSRID(ST_MakePoint(%2$f,%3$f),4326),%4$s)
            ) as z
            FROM %1$s
            WHERE ST_Intersects(
                %1$s.rast,
                ST_Transform(ST_SetSRID(ST_MakePoint(%2$f,%3$f),4326),%4$s)

        )',
            $this->AltiProfileTable,
            $lon,
            $lat,
            $this->Srid
        );
        $cnx = jDb::getConnection( 'altiProfil' );
        $qResult = $cnx->query( $sql );
        $result = array("elevations"=>[$qResult->fetch(PDO::FETCH_ASSOC)]);
        return json_encode($result);
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
        //ref: https://www.crunchydata.com/blog/waiting-for-postgis-3.2-st_contour-and-st_setz
        $sql = sprintf('
            WITH
		        profile_line as (
                    SELECT
                        ST_MakeLine(
                                    ST_Transform(ST_SetSRID(ST_MakePoint(%2$f, %3$f),4326), %4$s),
                                    ST_Transform(ST_SetSRID(ST_MakePoint(%5$f, %6$f),4326), %4$s)) as geom
                        ),
                    linemesure AS(
                        -- Add a measure dimension to extract steps
                        SELECT

                            CASE
                                WHEN ST_Length(geom)::int < 1000 THEN %8$d
                                ELSE %8$d*5
                            END as resolution, geom
                        from profile_line

                    ),

				transect AS (
					 SELECT ST_Segmentize(
						geom,
					   resolution
						)::geometry
					  AS geom,resolution
					from linemesure
					),
					rast AS (
					 SELECT ST_Union(%1$s.rast) AS rast, resolution
					  FROM %1$s
					  JOIN transect
					  ON ST_Intersects(transect.geom, ST_ConvexHull(%1$s.rast))
						group by transect.resolution
					),
					z AS (
					  SELECT rast.resolution,(ST_DumpPoints(ST_SetZ(rast.rast, transect.geom, resample => \'bilinear\'))).*
					 FROM rast
					  CROSS JOIN transect
					),
					cells AS (
						SELECT round(ST_Z(geom)) AS val, geom, resolution
					FROM z
					),
					-- Instantiate 3D points
	                points3d AS (
	                    SELECT ST_SetSRID(
	                                ST_MakePoint(ST_X(geom), ST_Y(geom), val),
	                                %4$s
	                            ) AS geom, resolution FROM cells
	                ),
	                line3D AS(
	                    SELECT ST_MakeLine(geom)as geom, MAX(resolution) as resolution FROM points3d
	                ),
	                xz AS(
	                    SELECT (ST_DumpPoints(geom)).geom AS geom,
	                    ST_StartPoint(geom) AS origin, resolution
	                    FROM line3D
	                )
	            -- Build 3D line from 3D points
	            SELECT ST_distance(origin, geom) AS x, ST_Z(geom) as y, ST_X(ST_Transform(geom, 4326)) as lon, ST_Y(ST_Transform(geom, 4326)) as lat, resolution FROM xz',
            $this->AltiProfileTable,
            $p1Lon, $p1Lat,
            $this->Srid,
            $p2Lon, $p2Lat,
            $this->profilUnit,
            $this->AltiResolution,
        );
        $cnx = jDb::getConnection('altiProfil');
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
                    SELECT ST_Clip(%1$s.rast, line.geom, -9999, TRUE) as rast
                    FROM %1$s, line
                    WHERE ST_Intersects(%1$s.rast, line.geom)
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
        $cnx = jDb::getConnection('altiProfil');
        $qResult = $cnx->query($sql);
        $slope = json_encode(
                    $qResult->fetch(PDO::FETCH_ASSOC)
                );
        $data = [ [
            "x" => $x,
            "y" => $y,
            "customdata" => $customdata,
            "srid" => $this->Srid,
            "resolution" => $resolution,
            "altisource" => $this->Altisource,
            "slope" => $slope
         ] ];

        return json_encode($data);
    }
}
?>
