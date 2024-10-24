<?php
namespace AltiProfil;

class AltiConfig
{

    protected $parameters = array();

    function __construct()
    {
        if (method_exists('jApp', 'varConfigPath')) {
            // LWC >= 3.6
            $altiProfilConfigFile = \jApp::varConfigPath('altiProfil.ini.php');
        } else {
            $altiProfilConfigFile = \jApp::configPath('altiProfil.ini.php');
        }
        $defaultValues =  array(
            'altisource' => '',
            'altiProfileTable' => '',
            'altiProfileProvider'=>'ign',
            'ignServiceUrl' => 'https://data.geopf.fr/altimetrie/1.0/calcul',
            'dock'=>'dock',
            'srid'=>'3857',
            'profilUnit'=>'PERCENT',
            'altiresolution'=>25

        );
        $values = parse_ini_file($altiProfilConfigFile, true, INI_SCANNER_TYPED);
        $this->parameters = array_merge($defaultValues, $values['altiProfil']);
    }


    public function getProvider()
    {
        return $this->parameters['altiProfileProvider'];
    }

    public function getProfilUnit()
    {
        return $this->parameters['profilUnit'];
    }

    public function getDock()
    {
        return $this->parameters['dock'];
    }

    public function getAltisource()
    {
        return $this->parameters['altisource'];
    }

    public function getSrid()
    {
        return $this->parameters['srid'];
    }

    public function getAltiProfileTable()
    {
        return $this->parameters['altiProfileTable'];
    }

    public function getAltiResolution()
    {
        return $this->parameters['altiresolution'];
    }

    /**
     * @param string $path the path of the API
     * @param array $parameters query parameters
     * @return string the full url
     */
    public function getIgnServiceUrl($path, $parameters)
    {
        $url = $this->parameters['ignServiceUrl'];
        $url .= $path;
        $url .= '?'. http_build_query($parameters, '', '&');
        return $url;
    }
}
