# lizmap-altiProfil

## Description et utilisation

Ce Module Lizmap permet la création de profils topographiques à partir du web service de l'IGN ou d'une base postgis disposant d'une table raster avec un MNT.

![](https://github.com/arno974/lizmap-altiProfil/blob/master/altiProfil.jpeg?raw=true)

Une fois le module téléchargé, il est nécessaire de l'installer à l'aide des scripts Lizmap:

```
php lizmap/install/installer.php
lizmap/install/clean_vartmp.sh
lizmap/install/set_rights.sh
```

Il est ensuite nécessaire d'ajouter dans le fichier localconfig.ini.php de Lizmap (situé dans lizmap/var/config) le bloc ci-dessous :

```ini
[altiProfil]
;altiProfileProvider= database ou ign

;altisource = ex. SCHOM LITTO3D®
altisource= Source des données 

;si cas IGN
ignServiceKey=votre clé IGN
ignServiceUrl=https://wxs.ign.fr/

;si cas database
altiProfileProvider=database
altiProfileTable=reunion_mnt
srid=2975
;; profilUnit = PERCENT or DEGREES - choix de l'unité de calcul du profil
profilUnit= PERCENT
```

Ce bloc permet de définir la source de vos données. Si vous souhaitez vous connecter au web service de l'IGN (altiProfileProvider=ign) ou a des données provenant de votre base (altiProfileProvider=database). En fonction de la source de données des options complémentaires doivent être précisées.

## Cas de l'utilisation à partir de l'API IGN

En raison de l'absence de continuité de service de l'offre Pro IGN, il n'a pas été possible de tester complément l'intégration de l'API. Les tests effectués ont été réalisés sur la base des exemples donnés dans la documentation.

Il est possible que l'intégration ne soit pas optimale, mais cela devrait tout de même être fonctionnel.

## Cas de l'utilisation à partir d'une base de données

Pour utiliser ce module en vous connectant à votre base de données, vous devrez disposer d'une base avec une table raster de type MNT. Vous devrez également ajouter au fichier profiles.ini.php (situé dans lizmap/var/config) le bloc ci-dessous :

```ini
[jdb:altiProfil]
driver=pgsql
database=nom de la base
host=localhost (ou URL)
user=nom de l'utilisateur de la base
password=mot de passe de l'utilisateur
search_path=si la table se trouve dans un schéma particulier sinon mettez simplement public
```
