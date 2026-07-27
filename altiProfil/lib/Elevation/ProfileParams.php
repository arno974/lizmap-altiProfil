<?php

namespace AltiProfil\Elevation;

class ProfileParams
{
    /** @var float */
    private $p1Lon;
    /** @var float */
    private $p1Lat;
    /** @var float */
    private $p2Lon;
    /** @var float */
    private $p2Lat;
    /** @var int */
    private $sampling;
    /** @var float */
    private $distance;

    /**
     * @throws InvalidRequestException when a value is missing or out of range
     */
    public function __construct($p1Lon, $p1Lat, $p2Lon, $p2Lat, $sampling, $distance)
    {
        foreach (array($p1Lon, $p1Lat, $p2Lon, $p2Lat) as $coord) {
            if (!is_numeric($coord)) {
                throw new InvalidRequestException('Wrong lon/lat values');
            }
        }
        // sampling/distance are only meaningful for the IGN provider, but they
        // are validated unconditionally: a single, provider-agnostic contract.
        if (!is_numeric($sampling) || (float) $sampling <= 0) {
            throw new InvalidRequestException('Wrong sampling value');
        }
        if (!is_numeric($distance) || (float) $distance < 0) {
            throw new InvalidRequestException('Wrong distance value');
        }

        $this->p1Lon = (float) $p1Lon;
        $this->p1Lat = (float) $p1Lat;
        $this->p2Lon = (float) $p2Lon;
        $this->p2Lat = (float) $p2Lat;
        $this->sampling = (int) $sampling;
        $this->distance = (float) $distance;
    }

    public function getP1Lon()
    {
        return $this->p1Lon;
    }

    public function getP1Lat()
    {
        return $this->p1Lat;
    }

    public function getP2Lon()
    {
        return $this->p2Lon;
    }

    public function getP2Lat()
    {
        return $this->p2Lat;
    }

    public function getSampling()
    {
        return $this->sampling;
    }

    public function getDistance()
    {
        return $this->distance;
    }
}
