d <?php

  use PHPUnit\Framework\TestCase;
  use AltiProfil\Elevation\ProfileParams;
  use AltiProfil\Elevation\InvalidRequestException;

  class ProfileParamsTest extends TestCase
  {
      /**
       * Cas nominal : des chaînes valides (comme les params HTTP du contrôleur)
       * doivent être castées et exposées par les getters.
       */ 
      public function testValidInputIsCastAndExposed(): void
      {   
          // Arrange + Act
          $params = new ProfileParams('55.373', '-21.058', '55.457', '-21.047', '356', '8902');
          
          // Assert - assertSame vérifie la valeur ET le type
          $this->assertSame(55.373,  $params->getP1Lon());    // string -> float
          $this->assertSame(-21.058, $params->getP1Lat());
          $this->assertSame(55.457,  $params->getP2Lon());
          $this->assertSame(-21.047, $params->getP2Lat());
          $this->assertSame(356,     $params->getSampling()); // string -> int
          $this->assertSame(8902.0,  $params->getDistance()); // string -> float
      }

    public function testZeroDistanceIsAccepted(): void
    {
        $params = new ProfileParams('55.3', '-21.0', '55.4', '-21.0', '356', '0');
        $this->assertSame(0.0, $params->getDistance());   // 0 est valide, casté en float
    }

    public function testAcceptsNumericTypesNotOnlyStrings(): void
    {
        $params = new ProfileParams(55.3, -21.0, 55.4, -21.0, 356, 8902);  // vrais nombres
        $this->assertSame(55.3, $params->getP1Lon());
        $this->assertSame(356,  $params->getSampling());
    }

      
      /**                                                                                                                                                                                     
       * Chaque jeu de paramètres INVALIDE doit lever InvalidRequestException.                                                                                                                                                                                                                                              
       */                                                                                                                                                                                     
      public static function invalidInputProvider(): array                                                                                                                                    
      {                                                                                                                                                                                       
          return [                                                                                                                                                                          
              //                                p1Lon    p1Lat    p2Lon   p2Lat  sampling distance                                                                                                    
              'Point 1 lon non numérique'   => ['abcd', '-21.0', '55.4', '-21.0', '356', '8902'],
              'Point 1 lat non numérique'   => ['55.3', 'abcde', '55.4', '-21.0', '356', '8902'],   
              'Point 2 lon non numérique'   => ['55.3', '-21.0', 'abcd', '-21.0', '356', '8902'],
              'Point 2 lat non numérique'   => ['55.3', '-21.0', '55.4', 'abcde', '356', '8902'],                                                                                                                                                                                                  
              'sampling inférieur à 1'      => ['55.3', '-21.0', '55.4', '-21.0', '0.5', '8902'],                                                                                                  
              'sampling négatif'            => ['55.3', '-21.0', '55.4', '-21.0',  '-5', '8902'],                                                                                                  
              'distance négative'           => ['55.3', '-21.0', '55.4', '-21.0', '356', '-1'],                                                                                                    
          ];                                                                                                                                                                                  
      }                                                                                                                                                                                       
                                                                                                                                                                                              
      /**                                                                                                                                                                                     
       * @dataProvider invalidInputProvider                                                                                                                                                   
       */                                                                                                                                                                                     
      public function testInvalidInputThrows($p1Lon, $p1Lat, $p2Lon, $p2Lat, $sampling, $distance): void                                                                                      
      {                                                                                                                                                                                       
          // Assert expectException : on DÉCLARE qu'une exception est attendue...                                                                                                                             
          $this->expectException(InvalidRequestException::class);                                                                                                                                                                                                                                                               
          new ProfileParams($p1Lon, $p1Lat, $p2Lon, $p2Lat, $sampling, $distance);                                                                                                            
      }                                                                                                                                                                                       
  }