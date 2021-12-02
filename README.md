# lizmap-altiProfil


Ce Module Lizmap permet la création de profils topographiques à partir du web 
service de l'IGN ou d'une base postgis disposant d'une table raster avec un MNT.

![](https://github.com/arno974/lizmap-altiProfil/blob/master/altiProfil.jpeg?raw=true)


## Installation

Depuis la version 0.2.2 du module, il est souhaitable de l'installer avec
[Composer](https://getcomposer.org), le système de paquet pour PHP. 
Si vous ne pouvez pas, ou si vous utilisez lizmap 3.3 ou inférieur, passez à la 
section sur l'installation manuelle.

### Installation automatique avec Composer et lizmap 3.4 ou plus

* dans `lizmap/my-packages`, créer le fichier `composer.json` s'il n'existe pas
  déjà, en copiant le fichier `composer.json.dist`, qui s'y trouve. Puis lancez
  Composer pour installer les fichiers de AltiProfil.

```bash
cp -n lizmap/my-packages/composer.json.dist lizmap/my-packages/composer.json
composer require --working-dir=lizmap/my-packages "lizmap/lizmap-altiprofil"
```

* Ensuite lancez les scripts d'installation de Lizmap :

```bash
php lizmap/install/installer.php
./lizmap/install/clean_vartmp.sh
./lizmap/install/set_rights.sh
```
`
Passez à la section sur la configuration.

### Installation manuelle dans lizmap 3.3 ou 3.4 sans Composer

* Téléchargez l'archive zip à partir de la [page release de github](https://github.com/arno974/lizmap-altiProfil/releases).
* Désarchivez le zip et copiez les répertoires `AltiProfil`, et `AltiProfilAdmin`
  dans le dossier `lizmap/lizmap-module/`
* Il faut ensuite activer les modules dans lizmap, en éditant des fichiers
  de configuration situés dans  `lizmap/var/config`.

Ajouter dans le fichier `lizmap/var/config/localconfig.ini.php`, sous la section `[module]`, la référence à ces 2 modules. Ne pas supprimer les références aux autres modules pour cette section.

```ini
[modules]

altiProfil.access=2
altiProfilAdmin.access=2

```

* Puis lancer l'installation des modules via

```bash
php lizmap/install/installer.php
lizmap/install/clean_vartmp.sh
lizmap/install/set_rights.sh
```

## Configuration


Il est ensuite nécessaire de se rendre à la page d'administration de Lizmap Web Client, et de configurer le module. 

![](https://github.com/arno974/lizmap-altiProfil/blob/master/altiProfilAdmin.png?raw=true)

Cette configuration crée ou modifie le fichier `lizmap/var/config/altiProfil.ini.php`, qui contiendra par exemple:

```ini
[altiProfil]
altisource= Source des données

;si cas database
altiProfileProvider=database
altiProfileTable=dem_table
srid=3957
; profilUnit = PERCENT or DEGREES - choix de l'unité de calcul du profil
profilUnit= PERCENT
; dock panel = dock or minidock or rightdock
dock=dock
altiresolution= résolution du MNT

;si cas IGN
;altiProfileProvider= ign
ignServiceKey=votre clé IGN
ignServiceUrl=https://wxs.ign.fr/

```

Vous pouvez ainsi définir et configurer la source de vos données. Si vous souhaitez vous connecter au web service de l'IGN (altiProfileProvider=ign) ou a des données provenant de votre base (altiProfileProvider=database). En fonction de la source de données des options complémentaires doivent être précisées.

## Surcharge de la configuration par projet

Pour chaque projet QGIS publié dans Lizmap, par exemple `my_project.qgs` vous pouvez ajouter un fichier avec une extension en plus `.alti` à la fin du nom, ce qui donne par exemple `my_project.qgs.alti`. Ce fichier permet de surcharger, s'il est présent, certains paramètres.

Par exemple

```ini
[altiProfil]
altisource="SRTM Montpellier high-resolution"
altiProfileTable=srtm_montpellier_high_resolution
srid=3857
```

## Cas de l'utilisation à partir de l'API IGN

En raison de l'absence de continuité de service de l'offre Pro IGN, il n'a pas été possible de tester complément l'intégration de l'API. Les tests effectués ont été réalisés sur la base des exemples donnés dans la documentation.

Il est possible que l'intégration ne soit pas optimale, mais cela devrait tout de même être fonctionnel.

## Cas de l'utilisation à partir d'une base de données

Pour utiliser ce module en vous connectant à votre base de données, vous devrez disposer d'une base avec une table raster de type MNT. Vous devrez également ajouter au fichier `profiles.ini.php` (situé dans `lizmap/var/config`) le bloc ci-dessous :

```ini
[jdb:altiProfil]
driver=pgsql
database=nom de la base
host=localhost (ou URL)
user=nom de l'utilisateur de la base
password=mot de passe de l'utilisateur
search_path=si la table se trouve dans un schéma particulier sinon mettez simplement public
```
