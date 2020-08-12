# lizmap-altiProfil

## Description et utilisation

Ce Module Lizmap permet la création de profils topographiques à partir du web service de l'IGN ou d'une base postgis disposant d'une table raster avec un MNT.

![](https://github.com/arno974/lizmap-altiProfil/blob/master/altiProfil.jpeg?raw=true)

Une fois le module téléchargé, il est nécessaire de l'installer à l'aide des scripts Lizmap:

* module altiProfil
* module altiProfilAdmin

Ajouter dans le fichier `lizmap/var/config/localconfig.ini.php`, sous la section `[module]`, la référence à ces 2 modules. Ne pas supprimer les références aux autres modules pour cette section.

```ini
[modules]

altiProfil.access=2
altiProfilAdmin.access=2

```

Puis lancer l'installation des modules via

```bash
php lizmap/install/installer.php
lizmap/install/clean_vartmp.sh
lizmap/install/set_rights.sh
```

Il est ensuite nécessaire de se rendre à la page d'administration de Lizmap Web Client, et de configurer le module. Cette configuration crée ou modifie le fichier `lizmap/var/config/altiProfil.ini.php`, qui contiendra par exemple:

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

;si cas IGN
;altiProfileProvider= ign
ignServiceKey=votre clé IGN
ignServiceUrl=https://wxs.ign.fr/

```

Vous pouvez ainsi définir et configurer la source de vos données. Si vous souhaitez vous connecter au web service de l'IGN (altiProfileProvider=ign) ou a des données provenant de votre base (altiProfileProvider=database). En fonction de la source de données des options complémentaires doivent être précisées.

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
