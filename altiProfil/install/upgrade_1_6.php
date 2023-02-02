<?php

/**
 * Upgrader for Lizmap <=3.5
 */
class altiProfilModuleUpgrader extends jInstallerModule
{
    public function install()
    {
        $this->copyDirectoryContent('../www/', jApp::wwwPath('altiprofil/'));
    }
}