<?php

use AltiProfil\Elevation\ProviderFactory;
use AltiProfil\Elevation\ProfileParams;
use AltiProfil\Elevation\InvalidRequestException;
use AltiProfil\Elevation\ServiceException;

class ajaxCtrl extends jController
{

    /**
     * Check if a user has the right to access
     * the requested repository/project
     */
    private function checkProjectAccess($repository, $project){
        try {
            $lizmapProject = \lizmap::getProject($repository.'~'.$project);
        } catch (\Lizmap\Project\UnknownLizmapProjectException $e) {
            return false;
        }
        if (!$lizmapProject) {
            return false;
        }
        return $lizmapProject->checkAcl();
    }

    /**
     * 403 response with the standard error body.
     */
    private function accessDenied(){
        $rep = $this->errorMsg('Access denied to this repository/project');
        $rep->setHttpStatus(403, \Lizmap\Request\Proxy::getHttpStatusMsg(403));

        return $rep;
    }

    /**
     * JSON error response, exact same shape the front reads as data['error msg'].
     */
    private function errorMsg($errorMsg){
        $rep = $this->getResponse('json');
        jLog::log("AltiProfil :: $errorMsg");
        $rep->data = array('error msg' => $errorMsg);

        return $rep;
    }

    /**
     * Get alti from one point based on IGN or database
    **/
    public function getAlti(){
        $repository = $this->param('repository');
        $project = $this->param('project');
        $rep = $this->getResponse('json');

        //check first for access to the project, if not return 403
        if (!$this->checkProjectAccess($repository, $project)) {
            return $this->accessDenied();
        }  

        $lon = $this->param('lon');
        $lat = $this->param('lat');
        if (!$this->request->isAjax() || !is_numeric($lon) || !is_numeric($lat)) {
            return $this->errorMsg('No Ajax or Wrong lon/lat values');
        }

        try {
            $config = new \AltiProfil\AltiConfig();   
            $provider = ProviderFactory::create($config, $repository, $project);
            $rep->data = $provider->getElevation($lon, $lat);
        } catch (ServiceException $e) {
            \jLog::log('AltiProfil :: '.$e->getMessage());

            return $this->errorMsg($e->getMessage());
        }
        return $rep;
    }

    /**
     * Get alti from one point based on IGN or database
    **/
    public function getProfil(){
        $repository = $this->param('repository');
        $project = $this->param('project');
        $rep = $this->getResponse('json');

        //check first for access to the project, if not return 403
        if (!$this->checkProjectAccess($repository, $project)) {
            return $this->accessDenied();
        }

        if (!$this->request->isAjax()) {
            return $this->errorMsg('No ajax request');
        }

        try {
            $request = new ProfileParams(
                $this->param('p1Lon'),
                $this->param('p1Lat'),
                $this->param('p2Lon'),
                $this->param('p2Lat'),
                $this->param('sampling'),
                $this->param('distance')
            );
            $config = new \AltiProfil\AltiConfig();
            $provider = ProviderFactory::create($config, $repository, $project);
            $rep->data = $provider->getProfile($request);
        } catch (InvalidRequestException $e) {
            // Bad client input — no need to log noise.
            return $this->errorMsg($e->getMessage());
        } catch (ServiceException $e) {
            \jLog::log('AltiProfil :: '.$e->getMessage());

            return $this->errorMsg($e->getMessage());
        }

        return $rep;
    }
}

