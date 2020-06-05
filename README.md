# lizmap-altiProfil

## Description et utilisation

Ce Module Lizmap permet la création de profils topographiques à partir du web service de l'IGN ou d'une base postgis disposant d'une table raster avec un MNT.

![](https://github.com/arno974/lizmap-altiProfil/blob/master/altiProfil.jpeg?raw=true)

Une fois le module téléchargé, renommez le dossier lizmap-altiProfil pour simplement altiProfil.

Il est ensuite nécessaire  dans le fichier localconfig.ini.php de Lizmap (situé dans lizmap/var/config) le bloc ci-dessous :

```
[altiProfil]
;altiProfileProvider= database ou ign

altisource= Source des données // ex. SCHOM LITTO3D®

;si cas IGN
ignServiceKey=votre clé IGN
ignServiceUrl=https://wxs.ign.fr/

;si cas database
altiProfileProvider=database
altiProfileTable=reunion_mnt
srid=2975 
```

## Cas de l'utilisation à partir de l'API IGN

En raison de l'absence de continuité de service de l'offre Pro IGN, il n'a pas été possible de tester complément l'intégration de l'API. Les tests effectués ont été réalisés sur la base des exemples donnés dans la documentation. 

Il est possible que l'intégration ne soit pas optimale, mais cela devrait tout de même être fonctionnel.

## Cas de l'utilisation à partir d'une base de données

Pour utiliser ce module en vous connectant à votre base de données, vous devrez disposer d'une base avec une table raster de type MNT. Vous devrez également ajouter au fichier profiles.ini.php (situé dans lizmap/var/config) le bloc ci-dessous :

```
[jdb:altiProfil]
driver=pgsql
database=nom de la base
host=localhost (ou URL)
user=nom de l'utilisateur de la base
password=mot de passe de l'utilisateur
search_path=si la table se trouve dans un schéma particulier sinon mettez simplement public
```
