<?php
class altiProfilListener extends jEventListener{

    protected function getAltiProviderConfig($configItem) {
        $altiProfilConfigFile = jApp::configPath('altiProfil.ini.php');
        $localConfig = new jIniFileModifier($altiProfilConfigFile);
        $configItemValue = $localConfig->getValue($configItem, 'altiProfil');
        $defaultValues = array(
            'altiProfileProvider'=>'ign',
            'dock'=>'dock',
            'srid'=>'3857',
            'profilUnit'=>'PERCENT',
            'altiresolution'=>25
        );
        if (empty($configItemValue) and array_key_exists($configItem, $defaultValues)) {
            $configItemValue = $defaultValues[$configItem];
        }
        return $configItemValue;
    }

    private function getDockContent() {
        $tpl = new jTpl();
        $tpl->assign("altiProvider", $this->getAltiProviderConfig('altiProfileProvider'));
        if( $this->getAltiProviderConfig('altiProfileProvider') == 'database' ){
            $profilUnit="";
            $tpl->assign("profilUnit", $this->getAltiProviderConfig('profilUnit'));
        }

        $dockable = new lizmapMapDockItem(
            'altiProfil',
            jLocale::get('altiProfil~altiProfil.dock.title'),
            $tpl->fetch('altiProfil~altiProfil_Dock'),
            5,
            '<span class="icon-altiProfil"></span>'
        );
        return $dockable;
    }

    function onmapDockable ( $event ) {
        if ($this->getAltiProviderConfig('dock') != 'dock') {
            return Null;
        }
        if ($this->getAltiProviderConfig('altiProfileProvider') == 'database' || $this->getAltiProviderConfig('altiProfileProvider') == 'ign') {
            $dockable = $this->getDockContent();
            $event->add($dockable);
        } else {
            $errorConfigMsg = jLocale::get('altiProfil~altiProfil.error.configMsg');
            jLog::log($errorConfigMsg);
        }
    }
    function onmapMiniDockable ($event) {
        if ($this->getAltiProviderConfig('dock') != 'minidock') {
            return Null;
        }
        if ($this->getAltiProviderConfig('altiProfileProvider') == 'database' || $this->getAltiProviderConfig('altiProfileProvider') == 'ign') {
            $dockable = $this->getDockContent();
            $event->add($dockable);
        } else {
            $errorConfigMsg = jLocale::get('altiProfil~altiProfil.error.configMsg');
            jLog::log($errorConfigMsg);
        }
    }
    function onmapRightDockable ($event) {
        if ($this->getAltiProviderConfig('dock') != 'rightdock') {
            return Null;
        }
        if ($this->getAltiProviderConfig('altiProfileProvider') == 'database' || $this->getAltiProviderConfig('altiProfileProvider') == 'ign') {
            $dockable = $this->getDockContent();
            $event->add($dockable);
        } else {
            $errorConfigMsg = jLocale::get('altiProfil~altiProfil.error.configMsg');
            jLog::log($errorConfigMsg);
        }
    }
    function onmapBottomDockable ($event) { }

    function ongetMapAdditions ($event) {
        if($this->getAltiProviderConfig('altiProfileProvider') == 'database' || $this->getAltiProviderConfig('altiProfileProvider') == 'ign'){
            $js = array();
            $jscode = array();
            $css = array();
            $js [] = jUrl::get('jelix~www:getfile', array('targetmodule'=>'altiProfil', 'file'=>'js/altiProfil.js'));

            // Add Dataviz if not already available
            if ( !$this->getDatavizStatus($event) ) {
                $bp = jApp::urlBasePath();
                if (file_exists(jApp::wwwPath('js/dataviz/plotly-latest.min.js'))) {
                    $js[] = $bp.'js/dataviz/plotly-latest.min.js';
                    $js[] = $bp.'js/dataviz/dataviz.js';
                }
                if (file_exists(jApp::wwwPath('assets/js/dataviz/plotly-latest.min.js'))) {
                    $js[] = $bp.'assets/js/dataviz/plotly-latest.min.js';
                    $js[] = $bp.'assets/js/dataviz/dataviz.js';
                }
            }
            $css = array(
                jUrl::get('jelix~www:getfile', array('targetmodule'=>'altiProfil', 'file'=>'css/altiProfil.css'))
            );
            $event->add(
                array(
                    'js' => $js,
                    'jscode' => $jscode,
                    'css' => $css
                )
            );
        }
    }

    protected function getDatavizStatus ($event) {
        $project = $event->getParam( 'project' );
        $repository = $event->getParam( 'repository' );

        // Check dataviz config
        jClasses::inc('dataviz~datavizConfig');
        $dv = new datavizConfig($repository, $project);
        return $dv->getStatus();
    }
}
?>
