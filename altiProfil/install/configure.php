<?php
/**
 * @package   lizmap
 * @subpackage altiProfil
 * @author    3liz
 * @copyright 2022 3liz
 * @link      http://3liz.com
 * @license   GPL 3
 */

use Jelix\Installer\Module\API\ConfigurationHelpers;
use Jelix\Routing\UrlMapping\EntryPointUrlModifier;
use Jelix\Routing\UrlMapping\MapEntry\MapInclude;

class altiProfilModuleConfigurator extends \Jelix\Installer\Module\Configurator
{
    public function getDefaultParameters()
    {
        return array( );
    }

    public function configure(ConfigurationHelpers $helpers)
    {
        $altiProfilConfigPath = $helpers->configFilePath('altiProfil.ini.php');
        $helpers->copyFile('config/altiProfil.ini.php.dist', $altiProfilConfigPath, false);
    }

}
