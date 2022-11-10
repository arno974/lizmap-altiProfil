[modules]
altiProfil.access=2
altiProfilAdmin.access=2

[jResponseHtml]
plugins = debugbar


[simple_urlengine_entrypoints]
admin="jacl2db~*@classic,jacl2db_admin~*@classic,jauthdb_admin~*@classic,master_admin~*@classic,admin~*@classic,jcommunity~*@classic,altiProfilAdmin~*@classic"

[mailer]
webmasterEmail="tests@lizmap.com"
webmasterName="Lizmap Docker"
mailerType=file

