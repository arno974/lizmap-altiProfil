<?php

require(__DIR__.'/altiServicesFromDB.classic.php');
require(__DIR__.'/altiServicesFromIGN.classic.php');

class ajaxCtrl extends jController {

    /**
     * Get config parameters
    **/
    function getModuleConfig(){
        $localConfig = jApp::configPath('localconfig.ini.php');
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
        $GetAltiServicesFromDB = New GetAltiServicesFromDB;
        $GetAltiServicesFromIGN = New GetAltiServicesFromIGN;        

        $lon = $this->param('lon');
        $lat = $this->param('lat');
        
        if ($this->checkParams($lon, $lat)){              
            if($altiProvider == 'ign' ){
                $rep->data = $GetAltiServicesFromIGN->getAlti($lon, $lat); 
                return $rep;
                //return $this->getAltiFromIGN($lon, $lat);
            }elseif ( $altiProvider == 'database' ) {
                $rep->data = $GetAltiServicesFromDB->getAlti($lon, $lat); 
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
        $GetAltiServicesFromDB = New GetAltiServicesFromDB;
        $GetAltiServicesFromIGN = New GetAltiServicesFromIGN;

        $p1Lon = $this->param('p1Lon');
        $p1Lat = $this->param('p1Lat');
        $p2Lon = $this->param('p2Lon');
        $p2Lat = $this->param('p2Lat');
        $sampling = $this->param('sampling');

        if ( ($this->checkParams($p1Lon, $p1Lat)) and ($this->checkParams($p2Lon, $p2Lat)) ){
            if($altiProvider == 'ign' ){
                $rep->data = $GetAltiServicesFromIGN->getProfil($p1Lon, $p1Lat, $p2Lon, $p2Lat, $sampling); 
                return $rep;
            }elseif ( $altiProvider == 'database' ) {
                $rep->data = $GetAltiServicesFromDB->getProfil($p1Lon, $p1Lat, $p2Lon, $p2Lat); 
                return $rep;
            }else{
                return $this->errorMsg("Wrong or No Alti Provider defined (config $this->AltiProvider)");
            }
        }
    }
}
?>