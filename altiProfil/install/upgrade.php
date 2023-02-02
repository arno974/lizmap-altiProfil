<?php

/**
 * Upgrader for Lizmap 3.6+
 */
class altiProfilModuleUpgrader extends \Jelix\Installer\Module\Installer {

    function install(Jelix\Installer\Module\API\InstallHelpers $helpers) {
        // Copy CSS and JS assets
        $helpers->copyDirectoryContent('../www/', \jApp::wwwPath('altiprofil/'));
    }
}
