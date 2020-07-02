<?php
class altiProfilListener extends jEventListener{

    protected function getAltiProviderConfig($configItem){
        $localConfig = jApp::configPath('localconfig.ini.php');
        $localConfig = new jIniFileModifier($localConfig);
        $configItemValue = $localConfig->getValue($configItem, 'altiProfil');
        return $configItemValue ;
    }

    function onmapDockable ( $event ) {        
        if($this->getAltiProviderConfig('altiProfileProvider') == 'database' || $this->getAltiProviderConfig('altiProfileProvider') == 'ign'){
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
            $event->add($dockable);
        } else {
            $errorConfigMsg = jLocale::get('altiProfil~altiProfil.error.configMsg');
            jLog::log($errorConfigMsg);
        }        
    }

    function ongetMapAdditions ($event) {        
        if($this->getAltiProviderConfig('altiProfileProvider') == 'database' || $this->getAltiProviderConfig('altiProfileProvider') == 'ign'){
            $js = array();
            $jscode = array();
            $css = array();

            $js [] = jUrl::get('jelix~www:getfile', array('targetmodule'=>'altiProfil', 'file'=>'js/altiProfil.js'));
            $js [] = jUrl::get('jelix~www:getfile', array('targetmodule'=>'altiProfil', 'file'=>'js/PointTrack.js'));
            // Add Dataviz if not already available
            if ( !$this->getDatavizStatus($event) ) {
                $bp = jApp::config()->urlengine['basePath'];
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
    function onmapMiniDockable ($event) { }
    function onmapRightDockable ($event) { }
    function onmapBottomDockable ($event) { }

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
