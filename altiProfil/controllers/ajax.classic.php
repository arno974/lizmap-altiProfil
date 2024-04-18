<?php

class ajaxCtrl extends jController
{
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

        $altiConfig = new \AltiProfil\AltiConfig();
        $altiProvider = $altiConfig->getProvider();
        $lon = $this->param('lon');
        $lat = $this->param('lat');
        if ($this->checkParams($lon, $lat)){
            if($altiProvider == 'ign' ){
                $altiProviderInstance = new \AltiProfil\AltiServicesFromIGN($altiConfig);
                $rep->data = $altiProviderInstance->getAlti($lon, $lat);
                return $rep;
            }elseif ( $altiProvider == 'database' ) {
                $repository = $this->param('repository');
                $project = $this->param('project');
                $altiProviderInstance = new \AltiProfil\AltiServicesFromDB($altiConfig, $repository, $project);
                $rep->data = $altiProviderInstance->getAlti($lon, $lat);
                return $rep;
            }
            return $this->errorMsg("Wrong or No Alti Provider defined (config $altiProvider)");
        }
        return $this->errorMsg("Wrong lon/lat values");
    }

    /**
     * Get alti from one point based on IGN or database
    **/
    public function getProfil(){
        $rep = $this->getResponse('json');

        $altiConfig = new \AltiProfil\AltiConfig();
        $altiProvider = $altiConfig->getProvider();

        $p1Lon = $this->param('p1Lon');
        $p1Lat = $this->param('p1Lat');
        $p2Lon = $this->param('p2Lon');
        $p2Lat = $this->param('p2Lat');
        $sampling = $this->param('sampling');
        $distance = $this->param('distance');

        if ( ($this->checkParams($p1Lon, $p1Lat)) and ($this->checkParams($p2Lon, $p2Lat)) ){
            if($altiProvider == 'ign' ){
                $altiProviderInstance = new \AltiProfil\AltiServicesFromIGN($altiConfig);
                $rep->data = $altiProviderInstance->getProfil($p1Lon, $p1Lat, $p2Lon, $p2Lat, $sampling, $distance);
                return $rep;
            }elseif ( $altiProvider == 'database' ) {
                $repository = $this->param('repository');
                $project = $this->param('project');
                $altiProviderInstance = new \AltiProfil\AltiServicesFromDB($altiConfig, $repository, $project);
                $rep->data = $altiProviderInstance->getProfil($p1Lon, $p1Lat, $p2Lon, $p2Lat);
                return $rep;
            }
            return $this->errorMsg("Wrong or No Alti Provider defined (config $altiProvider)");
        }
        return $this->errorMsg("Wrong lon/lat values");
    }
}

