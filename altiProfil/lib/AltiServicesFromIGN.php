<?php
namespace AltiProfil;

Class AltiServicesFromIGN
{
    /**
     * @var \AltiProfil\AltiConfig
     */
    protected $config;

    /**
     * the resource id as defined by IGN geoplateforme
     * https://data.geopf.fr/altimetrie/resources
     * mandatory for each request
     *
     * @var string
     */
    private string $resource_id;
    /**
     * Get config parameters
    **/
    function __construct(\AltiProfil\AltiConfig $config)
    {
        $this->config = $config;
        $this->resource_id = 'ign_rge_alti_wld';
    }

    /**
     * Get alti from IGN API
    **/
    public function getAlti($lon, $lat)
    {
        $APIRestElev = "/alti/rest/elevation.json";

        $data = array(
            'lon' => $lon,
            'lat' => $lat,
            'resource' => $this->resource_id
        );

        $urlAltiIGN = $this->config->getIgnServiceUrl($APIRestElev, $data);

        list($data, $mime, $code) = \lizmapProxy::getRemoteData($urlAltiIGN);
        $code = 200;
        if ($code == 200) {
            //DATA SHOULD BE LIKE '{"elevations":[{"x":55.38025625,"y":-21.14050849,"z":2154.75,"acc":2.5}]}'
            /* FOR TESTING
            $data = '{"elevations":[{"x":55.38025625,"y":-21.14050849,"z":2154.75,"acc":2.5}]}' ;
            */
            return $data;
        }else{
            $errorMsg = "AltiProfil IGN wrong request";
            \jLog::log($errorMsg);
            return '{"error msg": "'.$data.'" }';
        }
    }

    /**
     * Get profil from IGN API
    **/
    public function getProfil($p1Lon, $p1Lat, $p2Lon, $p2Lat, $sampling, $distance)
    {
        // 150 is the max allowed for a fast response by IGN
        $sampling = min(150, $sampling);
        $APIRestProfil = "/alti/rest/elevationLine.json";
        $data = array(
            'lon' => $p1Lon."|".$p2Lon,
            'lat' => $p1Lat."|".$p2Lat,
            'sampling' => $sampling,
            'resource' => $this->resource_id
        );

        $fullURL = $this->config->getIgnServiceUrl($APIRestProfil, $data);

        list($data, $mime, $code) = \lizmapProxy::getRemoteData($fullURL);

        /* FOR TESTING
        $data = '{
            "elevations": [
                {
                    "lon": 0.2367,
                    "lat": 48.0551,
                    "z": 93.58,
                    "acc": 2.5
                },
                {
                    "lon": 0.726682,
                    "lat": 47.696327,
                    "z": 138.01,
                    "acc": 2.5
                },
                {
                    "lon": 1.209968,
                    "lat": 47.335467,
                    "z": 62.79,
                    "acc": 2.5
                },
                {
                    "lon": 1.686696,
                    "lat": 46.972574,
                    "z": 176.45,
                    "acc": 2.5
                },
                {
                    "lon": 2.157,
                    "lat": 46.6077,
                    "z": 207.53,
                    "acc": 2.5
                },
                {
                    "lon": 2.735486,
                    "lat": 45.939827,
                    "z": 634.69,
                    "acc": 2.5
                },
                {
                    "lon": 3.300216,
                    "lat": 45.269017,
                    "z": 609.78,
                    "acc": 2.5
                },
                {
                    "lon": 3.85177,
                    "lat": 44.5954,
                    "z": 1336.03,
                    "acc": 2.5
                },
                {
                    "lon": 4.122779,
                    "lat": 44.257578,
                    "z": 266.01,
                    "acc": 2.5
                },
                {
                    "lon": 4.3907,
                    "lat": 43.9191,
                    "z": 171.29,
                    "acc": 2.5
                }
            ]
        }';
        $code = 200;*/

        if ($code == 200) {
            $ignProfilResponse = json_decode($data);
            $x = array();
            $y = array();
            $customdata = array();
            $resolution = "";
            $i=0;
            $distanceStep  = ($distance/$sampling);
            foreach($ignProfilResponse->elevations as $key => $value) {
                    $x[] = $i*$distanceStep;
                    $y[] = $value->z;
                    $customdata[] = [["lon" => $value->lon, "lat" => $value->lat]];
                    $i = $i+1;
            }
            $data = [ [
                "x" => $x,
                "y" => $y,
                "customdata" => $customdata,
                "srid" => 4326,
                "altisource" => $this->config->getAltisource(),
                "source" => 'IGN'
             ] ];
            return $data;
        }else{
            $errorMsg = "AltiProfil IGN wrong request";
            \jLog::log($errorMsg);
            return array("error msg" => $data);
        }
    }
}
