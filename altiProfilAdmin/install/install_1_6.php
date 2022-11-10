<?php
/**
* @package   lizmap
* @subpackage occtax_admin
* @author    your name
* @copyright 2011 3liz
* @link      http://3liz.com
* @license    All rights reserved
*/

/**
 * Installer for Lizmap <=3.5
 */
class altiProfilAdminModuleInstaller extends jInstallerModule {

    function install() {

        if ($this->entryPoint->getEpId() == 'admin') {
            $localConfigIni = $this->entryPoint->localConfigIni->getMaster();

            $adminControllers = $localConfigIni->getValue('admin', 'simple_urlengine_entrypoints');
            $mbCtrl = 'altiProfilAdmin~*@classic';
            if (strpos($adminControllers, $mbCtrl) === false) {
                // let's register altiProfilAdmin controllers
                $adminControllers .= ', '.$mbCtrl;
                $localConfigIni->setValue('admin', $adminControllers, 'simple_urlengine_entrypoints');
            }
        }
    }
}
