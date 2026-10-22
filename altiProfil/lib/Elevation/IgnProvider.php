<?php

namespace AltiProfil\Elevation;

/**
 * Elevation from the IGN Géoplateforme altimetry REST API.
 */
class IgnProvider implements ProviderInterface
{
    /** IGN Géoplateforme altimetry REST base. */
    const SERVICE_URL = 'https://data.geopf.fr/altimetrie/1.0/calcul/alti/rest';

    /** IGN resource id, https://data.geopf.fr/altimetrie/resources */
    const RESOURCE_ID = 'ign_rge_alti_wld';

    /** IGN caps a "fast" elevationLine response at 150 samples. */
    const MAX_SAMPLING = 150;

    /** Value returned by IGN where there is no data (sea, outside coverage). */
    const NO_DATA = -99999;

    /** @var \AltiProfil\AltiConfig */
    protected $config;

    public function __construct(\AltiProfil\AltiConfig $config)
    {
        $this->config = $config;
    }

    public function getElevation($lon, $lat)
    {
        $url = $this->buildUrl('/elevation.json', array(
            'lon' => $lon,
            'lat' => $lat,
            'resource' => self::RESOURCE_ID,
        ));

        $response = $this->fetchJson($url);
        if (!isset($response->elevations[0]->z)) {
            throw new ServiceException('IGN returned an unexpected elevation response');
        }

        // Normalized to the same shape as the database provider so the front
        // reads data['elevations'][0]['z'] regardless of the backend.
        return array('elevations' => array(array('z' => $this->elevationOrNull($response->elevations[0]->z))));
    }

    public function getProfile(ProfileParams $profilParams)
    {
        $sampling = min(self::MAX_SAMPLING, $profilParams->getSampling());

        $url = $this->buildUrl('/elevationLine.json', array(
            'lon' => $profilParams->getP1Lon().'|'.$profilParams->getP2Lon(),
            'lat' => $profilParams->getP1Lat().'|'.$profilParams->getP2Lat(),
            'sampling' => $sampling,
            'resource' => self::RESOURCE_ID,
        ));

        $response = $this->fetchJson($url);
        if (!isset($response->elevations) || !is_array($response->elevations)) {
            throw new ServiceException('IGN returned an unexpected profile response');
        }

        $x = array();
        $y = array();
        $customdata = array();
        // Distance step derived from the number of points IGN actually returned:
          // n points span n-1 intervals, so the last point lands on the distance.
        $count = count($response->elevations);
        $step = $count > 1 ? ($profilParams->getDistance() / ($count - 1)) : 0;
        $i = 0;
        foreach ($response->elevations as $point) {
            $x[] = $i * $step;
            $y[] = $this->elevationOrNull($point->z);
            $customdata[] = array(array('lon' => $point->lon, 'lat' => $point->lat));
            $i++;
        }

        return array(array(
            'x' => $x,
            'y' => $y,
            'customdata' => $customdata,
            'srid' => 4326,
            'altisource' => $this->config->getAltisource(),
            'source' => 'IGN',
        ));
    }

    /**
     * IGN no-data value -> null.
     */
    private function elevationOrNull($z)
    {
        return ($z === null || (float) $z <= self::NO_DATA) ? null : $z;
    }

    /**
     * Build a full IGN URL
     */
    private function buildUrl($path, array $parameters)
    {
        return self::SERVICE_URL.$path.'?'.http_build_query($parameters, '', '&');
    }

    /**
     * @return object the decoded JSON body
     *
     * @throws ServiceException on transport error, non-200 status or invalid JSON
     */
    private function fetchJson($url)
    {
        list($body, $mime, $code) = \Lizmap\Request\Proxy::getRemoteData($url);

        if ($code != 200) {
            throw new ServiceException('IGN request failed (HTTP '.$code.')');
        }

        $decoded = json_decode($body);
        if ($decoded === null) {
            throw new ServiceException('IGN returned an invalid JSON body');
        }

        return $decoded;
    }
}
