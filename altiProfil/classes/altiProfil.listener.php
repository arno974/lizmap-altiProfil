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
            5
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
            $bp = jApp::urlBasePath();
            $js [] = $bp.'altiprofil/js/altiProfil.js';
            $locale = substr(jApp::config()->locale, 0, 2);

            // Add Dataviz if not already available
            if ( !$this->getDatavizStatus($event) ) {

                // old LWC version ?
                if (file_exists(jApp::wwwPath('js/dataviz/plotly-latest.min.js'))) {
                    $js[] = $bp.'js/dataviz/plotly-latest.min.js';
                    $js[] = $bp.'js/dataviz/dataviz.js';
                } elseif (file_exists(jApp::wwwPath('assets/js/dataviz/plotly-latest.min.js'))) {
                    // LWC 3.6, 3.7, <3.84
                    $js[] = $bp.'assets/js/dataviz/plotly-latest.min.js';
                    $js[] = $bp.'assets/js/dataviz/dataviz.js';
                } elseif (file_exists(jApp::wwwPath('assets/js/dataviz/plotly-custom.min.js'))) {
                    // since LWC 3.8.4 plotly asset is 'plotly-custom'
                    $js[] = $bp.'assets/js/dataviz/plotly-custom.min.js';
                    $js[] = $bp.'assets/js/dataviz/dataviz.js';
                }
                // add lang
                if (file_exists(jApp::wwwPath('assets/js/dataviz/plotly-locale-'.$locale.'-latest.js'))) {
                    $js[] = $bp.'assets/js/dataviz/plotly-locale-'.$locale.'-latest.js';
                }
            }
            $css = array(
                $bp.'altiprofil/css/altiProfil.css'
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

