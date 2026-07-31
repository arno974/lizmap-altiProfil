<?php

use PHPUnit\Framework\TestCase;
use AltiProfil\AltiConfig;
use AltiProfil\Elevation\ProviderFactory;
use AltiProfil\Elevation\IgnProvider;
use AltiProfil\Elevation\DatabaseProvider;
use AltiProfil\Elevation\ServiceException;

/**
 * Tests de ProviderFactory::create().
 *
 * PROBLÈME : la factory a besoin d'un AltiConfig, mais `new AltiConfig()` lit un
 * fichier .ini via Jelix (jApp) -> impossible à instancier dans un test pur.
 *
 * SOLUTION : une DOUBLURE (mock). $this->createMock(AltiConfig::class) fabrique
 * une fausse sous-classe d'AltiConfig :
 *   - son constructeur d'origine n'est PAS appelé (donc pas de Jelix) ;
 *   - toutes ses méthodes publiques deviennent des stubs neutres (retour null)
 *     que l'on peut piloter avec ->method(...)->willReturn(...).
 *
 * On force getProvider() pour tester l'aiguillage de la factory EN ISOLATION,
 * sans vrai fichier de config, sans réseau, sans base.
 */
class ProviderFactoryTest extends TestCase
{
    /**
     * Fabrique une fausse config qui déclare le provider voulu.
     */
    private function configReturning(string $providerName): AltiConfig
    {
        $config = $this->createMock(AltiConfig::class); //fausse sous-classe d'AltiConfig
        $config->method('getProvider')->willReturn($providerName);

        return $config;
    }

    public function testCreatesIgnProvider(): void
    {
        $provider = ProviderFactory::create($this->configReturning('ign'), 'repo', 'proj');

        $this->assertInstanceOf(IgnProvider::class, $provider);
    }

    public function testCreatesDatabaseProvider(): void
    {
        $provider = ProviderFactory::create($this->configReturning('database'), 'repo', 'proj');

        $this->assertInstanceOf(DatabaseProvider::class, $provider);
    }

    public function testUnknownProviderThrows(): void
    {
        $this->expectException(ServiceException::class);

        ProviderFactory::create($this->configReturning('wcs'), 'repo', 'proj');
    }
}
