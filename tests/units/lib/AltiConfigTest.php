<?php

use PHPUnit\Framework\TestCase;
use AltiProfil\AltiConfig;

/**
 * Tests du validateur critique (sécurité) AltiConfig::isValidValue().
 * Chaque ligne = un scénario. PHPUnit appelle testIsValidValue() 
 * Une fois par ligne, en injectant les 3 valeurs comme arguments
 * affiche l'étiquette en cas d'échec.
 */
class AltiConfigTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: mixed, 2: bool}>
     */
    public static function valueProvider(): array
    {
        return [
            // étiquette                   => [clé,                    valeur,                    attendu]

            // srid : entier positif uniquement (ctype_digit)
            'srid entier'                  => ['srid',                 '3857',                    true],
            'srid typé int (piège ini)'    => ['srid',                  3857,                     true],  // INI_SCANNER_TYPED
            'srid négatif'                 => ['srid',                 '-1',                      false],
            'srid décimal'                 => ['srid',                 '28.5',                    false],
            'srid non numérique'           => ['srid',                 'abc',                     false],
            'srid vide'                    => ['srid',                 '',                        false],

            // altiresolution : réel strictement positif
            'resolution submétrique'       => ['altiresolution',       '0.5',                     true],
            'resolution typé int (ini)'    => ['altiresolution',        25,                       true],
            'resolution zéro'              => ['altiresolution',       '0',                       false],
            'resolution négative'          => ['altiresolution',       '-3',                      false],
            'resolution texte'             => ['altiresolution',       'x',                       false],

            // enums STRICTS (sensibles à la casse)
            'provider ign'                 => ['altiProfileProvider',  'ign',                     true],
            'provider database'            => ['altiProfileProvider',  'database',                true],
            'provider inconnu'             => ['altiProfileProvider',  'wcs',                     false],
            'provider mauvaise casse'      => ['altiProfileProvider',  'IGN',                     false],
            'unit percent'                 => ['profilUnit',           'PERCENT',                 true],
            'unit mauvaise casse'          => ['profilUnit',           'percent',                 false],
            'dock minidock'                => ['dock',                 'minidock',                true],
            'dock inconnu'                 => ['dock',                 'topdock',                 false],

            // identifiants SQL : ^[a-zA-Z0-9_]+$ (barrière anti-injection)
            'schema propre'                => ['altiProfileSchema',    'dem_schema',              true],
            'schema tentative injection'   => ['altiProfileSchema',    'schema; DROP TABLE x',    false],
            'schema vide'                  => ['altiProfileSchema',    '',                        false],
            'table propre'                 => ['altiProfileTable',     'reunion_mnt',             true],

            // URL IGN : n'importe quelle URL valide est acceptée
            'url valide'                   => ['ignServiceUrl',        'https://data.geopf.fr/x', true],
            'url invalide'                 => ['ignServiceUrl',        'pas-une-url',             false],

            // altisource : chaîne SANS balise HTML (barrière anti-XSS)
            'altisource simple'            => ['altisource',           'GEOLAB',                  true],
            'altisource avec ©'            => ['altisource',           'Source © IGN',            true],
            'altisource avec balise HTML'  => ['altisource',           '<b>x</b>',                false],
            'altisource non-string'        => ['altisource',            123,                      false],

            // clé hors whitelist -> toujours false (branche default)
            'clé inconnue'                 => ['clef_bidon',           'peu importe',             false],
        ];
    }

    /**
     * @dataProvider valueProvider
     *
     * @param mixed $value
     */
    public function testIsValidValue(string $key, $value, bool $expected): void
    {
        // assertSame : compare valeur ET type -> on vérifie un vrai booléen
        $this->assertSame($expected, AltiConfig::isValidValue($key, $value));
    }
}
