<?php

class ajaxCtrl extends jController {

    /**
     * Get config parameters
    **/
    function getModuleConfig(){
        $localConfig = jApp::configPath('altiProfil.ini.php');
        $localConfig = new jIniFileModifier($localConfig);
        $altiProvider = $localConfig->getValue('altiProfileProvider', 'altiProfil');
        return $altiProvider;
    }

    /**
     * Log Error Message
    **/
    private function errorMsg($errorMsg){
        $rep = $this->getResponse('json');
        jLog::log("AltiProfil :: $errorMsg");
        $errorMsg = '{"error msg": "'.$errorMsg.'" }';
        $rep->data = $errorMsg;
        return $rep;
    }

    /**
     * Check input parameters
    **/
    protected function checkParams($lon, $lat){
        if ( $this->request->isAjax() ){
            if( is_numeric($lon) &&  is_numeric($lat) ){
                return true;
            } else{
                jLog::log("AltiProfil :: Wrong lon/lat params");
            }
        }else{
            jLog::log("AltiProfil :: No ajax request");
        }
    }

    /**
     * Get alti from one point based on IGN or database
    **/
    public function getAlti(){
        $rep = $this->getResponse('json');

        $altiProvider = $this->getModuleConfig();
        $lon = $this->param('lon');
        $lat = $this->param('lat');
        if ($this->checkParams($lon, $lat)){
            if($altiProvider == 'ign' ){
                jClasses::inc('altiProfil~altiServicesFromIGN');
                $altiProviderInstance = new GetAltiServicesFromIGN();
                $rep->data = $altiProviderInstance->getAlti($lon, $lat);
                return $rep;
            }elseif ( $altiProvider == 'database' ) {
                jClasses::inc('altiProfil~altiServicesFromDB');
                $repository = $this->param('repository');
                $project = $this->param('project');
                $altiProviderInstance = new GetAltiServicesFromDB($repository, $project);
                $rep->data = $altiProviderInstance->getAlti($lon, $lat);
                return $rep;
            }else{
                return $this->errorMsg("Wrong or No Alti Provider defined (config $this->AltiProvider)");
            }
        }
    }

    /**
     * Get alti from one point based on IGN or database
    **/
    public function getProfil(){
        $rep = $this->getResponse('json');

        $altiProvider = $this->getModuleConfig();

        $p1Lon = $this->param('p1Lon');
        $p1Lat = $this->param('p1Lat');
        $p2Lon = $this->param('p2Lon');
        $p2Lat = $this->param('p2Lat');
        $sampling = $this->param('sampling');

        if ( ($this->checkParams($p1Lon, $p1Lat)) and ($this->checkParams($p2Lon, $p2Lat)) ){
            if($altiProvider == 'ign' ){
                jClasses::inc('altiProfil~altiServicesFromIGN');
                $altiProviderInstance = new GetAltiServicesFromIGN();
                $rep->data = $altiProviderInstance->getProfil($p1Lon, $p1Lat, $p2Lon, $p2Lat, $sampling);
            }elseif ( $altiProvider == 'database' ) {
                jClasses::inc('altiProfil~altiServicesFromDB');
                $repository = $this->param('repository');
                $project = $this->param('project');
                $altiProviderInstance = new GetAltiServicesFromDB($repository, $project);
                $rep->data = $altiProviderInstance->getProfil($p1Lon, $p1Lat, $p2Lon, $p2Lat);
                return $rep;
            }else{
                return $this->errorMsg("Wrong or No Alti Provider defined (config $this->AltiProvider)");
            }
        }
    }
}
?>
