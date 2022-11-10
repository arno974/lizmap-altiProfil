<?php
namespace AltiProfil;

class AltiConfig
{

    protected $parameters = array();

    function __construct()
    {
        $altiProfilConfigFile = \jApp::configPath('altiProfil.ini.php');
        $defaultValues =  array(
            'altisource' => '',
            'altiProfileTable' => '',
            'altiProfileProvider'=>'ign',
            'ignServiceKey' => 'essentiels',
            'ignServiceUrl' => 'https://wxs.ign.fr/',
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
        $url = $this->parameters['ignServiceUrl'].$this->parameters['ignServiceKey'];
        $url .= $path;
        $url .= '?'. http_build_query($parameters, '', '&');
        return $url;
    }
}