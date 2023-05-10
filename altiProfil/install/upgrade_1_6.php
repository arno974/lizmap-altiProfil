<?php

/**
 * Upgrader for Lizmap <=3.5
 */
class altiProfilModuleUpgrader extends jInstallerModule
{
    public function install()
    {
        // Copy CSS and JS assets
        // We use overwrite to be sure the new versions of the JS files
        // will be used
        $overwrite = true;
        $this->copyDirectoryContent('www', jApp::wwwPath(), $overwrite);
    }
}
