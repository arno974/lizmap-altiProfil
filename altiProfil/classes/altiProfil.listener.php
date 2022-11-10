<?php
class altiProfilListener extends jEventListener{

    /** @var  */
    protected $config;

    public function __construct()
    {
        $this->config = new \AltiProfil\AltiConfig();
    }

    private function getDockContent() {
        $tpl = new jTpl();
        $provider = $this->config->getProvider();
        $tpl->assign("altiProvider", $provider) ;
        if ($provider == 'database' ){
            $tpl->assign("profilUnit", $this->config->getProfilUnit());
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

    function onmapDockable ($event)
    {
        if ($this->config->getDock() != 'dock') {
            return Null;
        }
        $provider = $this->config->getProvider();
        if ($provider == 'database' || $provider == 'ign') {
            $dockable = $this->getDockContent();
            $event->add($dockable);
        } else {
            $errorConfigMsg = jLocale::get('altiProfil~altiProfil.error.configMsg');
            jLog::log($errorConfigMsg);
        }
    }

    function onmapMiniDockable ($event)
    {
        if ($this->config->getDock() != 'minidock') {
            return Null;
        }
        $provider = $this->config->getProvider();
        if ($provider == 'database' || $provider == 'ign') {
            $dockable = $this->getDockContent();
            $event->add($dockable);
        } else {
            $errorConfigMsg = jLocale::get('altiProfil~altiProfil.error.configMsg');
            jLog::log($errorConfigMsg);
        }
    }

    function onmapRightDockable ($event)
    {
        if ($this->config->getDock() != 'rightdock') {
            return Null;
        }
        $provider = $this->config->getProvider();
        if ($provider == 'database' || $provider == 'ign') {
            $dockable = $this->getDockContent();
            $event->add($dockable);
        } else {
            $errorConfigMsg = jLocale::get('altiProfil~altiProfil.error.configMsg');
            jLog::log($errorConfigMsg);
        }
    }
    function onmapBottomDockable ($event)
    {

    }

    function ongetMapAdditions ($event)
    {
        $provider = $this->config->getProvider();
        if ($provider == 'database' || $provider == 'ign') {
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

