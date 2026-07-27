<?php

namespace AltiProfil\Elevation;

/**
 * Contract shared by every elevation backend (IGN API, PostGIS raster, …).
 */
interface ProviderInterface
{
    /**
     * Elevation of a single point.
     *
     * @param float|string $lon longitude in EPSG:4326
     * @param float|string $lat latitude  in EPSG:4326
     *
     * @return array{elevations: array<int, array<string, mixed>>}
     *         e.g. ['elevations' => [ ['z' => 848.85] ]]
     *         z may be null when the point is outside the DEM coverage.
     */
    public function getElevation($lon, $lat);

    /**
     * Elevation profile along the segment carried by $profilParams.
     *
     * @return array<int, array<string, mixed>> the single-element dataset the
     *         front reads as data[0]: keys x, y, customdata, srid, altisource
     *         (+ resolution, slope for the database provider).
     */
    public function getProfile(ProfileParams $profilParams);
}
