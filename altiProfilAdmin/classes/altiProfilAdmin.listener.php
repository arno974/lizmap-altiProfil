<?php
class altiProfilAdminListener extends jEventListener{

  function onmasteradminGetMenuContent ($event) {
      // Create the "lizmap" parent menu item
      if (jAcl2::check("lizmap.admin.access")) {
        $bloc = new masterAdminMenuItem('altiProfilAdmin', 'AltiProfil', '', 200);

        // Child for the configuration of Mascarine forms
        $bloc->childItems[] = new masterAdminMenuItem(
          'altiProfilAdmin_config',
          'AltiProfil',
          jUrl::get('altiProfilAdmin~config:index'),
          210
        );

        // Add the bloc
        $event->add($bloc);
      }

  }

}
