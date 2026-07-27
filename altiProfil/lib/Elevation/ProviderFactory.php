<?php

namespace AltiProfil\Elevation;

/**
 * Single place that knows how to turn the configured provider name into a
 * concrete ProviderInterface. This is the ONLY spot the controller's
 * old "if ($provider == 'ign') … elseif ($provider == 'database') …"
 *
 * Easier if we want to add new case.
 */
class ProviderFactory
{
    /**
     * @throws ServiceException when the provider name is unknown/unset
     *
     * @return ProviderInterface
     */
    public static function create(\AltiProfil\AltiConfig $config, $repository, $project)
    {
        $provider = $config->getProvider();

        switch ($provider) {
            case 'ign':
                return new IgnProvider($config);

            case 'database':
                return new DatabaseProvider($config, $repository, $project);

            default:
                throw new ServiceException('Unknown or unset elevation provider: '.$provider);
        }
    }
}
