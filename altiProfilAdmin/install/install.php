<?php
/**
* @package   lizmap
* @subpackage occtax_admin
* @author    your name
* @copyright 2011 3liz
* @link      http://3liz.com
* @license    All rights reserved
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


        //if ($this->firstDbExec())
        //    $this->execSQLScript('sql/install');

        /*if ($this->firstExec('acl2')) {
            jAcl2DbManager::addSubject('my.subject', 'occtax_admin~acl.my.subject', 'subject.group.id');
            jAcl2DbManager::addRight('admins', 'my.subject'); // for admin group
        }
        */
    }
}
