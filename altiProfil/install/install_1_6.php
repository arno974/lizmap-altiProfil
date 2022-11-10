<?php
/**
* @package   lizmap
* @subpackage altiProfil
* @author    3liz
* @copyright 2018-2020 3liz
* @link      http://3liz.com
* @license   GPL 3
*/

/**
 * Installer for Lizmap <=3.5
 */
class altiProfilModuleInstaller extends jInstallerModule {

    function install() {

        // Copy configuration file
        $altiProfilConfigPath = jApp::configPath('altiProfil.ini.php');
        if (!file_exists($altiProfilConfigPath)) {
            $this->copyFile('config/altiProfil.ini.php.dist', $altiProfilConfigPath);
        }

    }
}
