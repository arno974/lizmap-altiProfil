<?php
namespace AltiProfil;

class AltiConfig
{

    protected $parameters = array();

    /**
     * List of keys that can be overridden in the project configuration file
     */
    private const DEFAULTS_VALUES = array(
          'altisource'          => '',
          'altiProfileSchema'   => '',
          'altiProfileTable'    => '',
          'altiProfileProvider' => 'ign',
          'ignServiceUrl'       => 'https://data.geopf.fr/altimetrie/1.0/calcul',
          'dock'                => 'dock',
          'srid'                => '2975',
          'profilUnit'          => 'PERCENT',
          'altiresolution'      => 54,
    );

    function __construct()
    {
        if (method_exists('jApp', 'varConfigPath')) {
            // LWC >= 3.6
            $altiProfilConfigFile = \jApp::varConfigPath('altiProfil.ini.php');
        } else {
            $altiProfilConfigFile = \jApp::configPath('altiProfil.ini.php');
        }

        $values = parse_ini_file($altiProfilConfigFile, true, INI_SCANNER_TYPED);
        if ($values && array_key_exists('altiProfil', $values)) {
            $this->parameters = array_merge(self::DEFAULTS_VALUES, $values['altiProfil']);
        } else {
            $this->parameters = self::DEFAULTS_VALUES;
        }
    }

    /**
     * Check value that can be overridden in the project configuration file
     */
    public static function isValidValue($key, $value)
    {
        switch ($key) {
            case 'altiProfileSchema':
            case 'altiProfileTable':
                return is_string($value) && preg_match('/^[a-zA-Z0-9_]+$/', $value) === 1;

            case 'srid':
            case 'altiresolution':
                return ctype_digit((string) $value);   // gere le int de INI_SCANNER_TYPED

            case 'altiProfileProvider':
                return in_array($value, array('ign', 'database'), true);

            case 'profilUnit':
                return in_array($value, array('PERCENT', 'DEGREES'), true);

            case 'dock':
                return in_array($value, array('dock', 'minidock', 'rightdock'), true);

            case 'ignServiceUrl':
                 return filter_var((string) $value, FILTER_VALIDATE_URL) !== false;
                 
            case 'altisource':
                return is_string($value) && $value === strip_tags($value);

            default:
                return false;
        }
    }


    public function setProjectConfig($repository, $project)
    {
        $p = \lizmap::getProject($repository.'~'.$project);
        if (!$p) {
            return false;
        }

        $alti_config_file = $p->getQgisPath() . '.alti';
        if (!file_exists($alti_config_file)) {
            return false;
        }

        $values = parse_ini_file($alti_config_file, true, INI_SCANNER_TYPED);
        if (!$values || !array_key_exists('altiProfil', $values)) {
            return false;
        }

        // Only read keys that are allowed to be overridden in the project configuration file
        foreach ($values['altiProfil'] as $key => $value) {
            if (!array_key_exists($key, self::DEFAULTS_VALUES)) { 
                \jLog::log("AltiProfil :: clé '$key' invalide dans .alti, ignoree");
                continue; 
            } 
            if (!self::isValidValue($key, $value)) { 
                \jLog::log("AltiProfil :: valeur invalide pour '$key' dans .alti, ignoree");
                continue; 
            }
            $this->parameters[$key] = $value;
        }
        return true;
    }

    public function getValue($key)
    {
        if (array_key_exists($key, $this->parameters)) {
            return $this->parameters[$key];
        }

        return null;
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

    public function getAltiResolution()
    {
        return $this->parameters['altiresolution'];
    }

    public function getAltiProfileSchema()
    {
        return $this->parameters['altiProfileSchema'];
    }

    public function getAltiProfileTable()
    {
        return $this->parameters['altiProfileTable'];
    }

    public function quotedSchemaTableName()
    {
        if ($this->parameters['altiProfileSchema'] != '') {
            return sprintf(
                '"%1$s"."%2$s"',
                $this->parameters['altiProfileSchema'],
                $this->parameters['altiProfileTable']
            );
        } else {
            return sprintf(
                '"%1$s"',
                $this->parameters['altiProfileTable']
            );
        }
    }

    /**
     * Check connection
     */
    public function checkConnection() {
        try{
          $sql = sprintf('SELECT rast FROM %1$s limit 0', $this->quotedSchemaTableName());
          $cnx = \jDb::getConnection( 'altiProfil' );
          $qResult = $cnx->query( $sql);
          return true;
        } catch (\Exception $e) {
             \jLog::log("AltiProfil Admin :: ".$e->getMessage());
        }
        return false;
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
