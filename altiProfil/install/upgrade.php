<?php

/**
 * Upgrader for Lizmap 3.6+
 */
class altiProfilModuleUpgrader extends \Jelix\Installer\Module\Installer {

    function install(Jelix\Installer\Module\API\InstallHelpers $helpers) {
        // Copy CSS and JS assets
        // We use overwrite to be sure the new versions of the JS files
        // will be used
        $overwrite = true;
        $helpers->copyDirectoryContent('www', \jApp::wwwPath(), $overwrite);
    }
}
