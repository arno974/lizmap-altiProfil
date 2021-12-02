{jmessage_bootstrap}

<h1>{@altiProfilAdmin~admin.configuration.label@}</h1>

{formfull $form, 'altiProfilAdmin~config:save', array(), 'htmlbootstrap'}

<div>
  <a class="btn" href="{jurl 'altiProfilAdmin~config:index'}">{@admin~admin.configuration.button.back.label@}</a>
</div>

{meta_htmlmodule js  'altiProfilAdmin', 'js/alti_profil_config.js'}
