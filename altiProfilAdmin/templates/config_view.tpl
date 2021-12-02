{jmessage_bootstrap}

<h1>{@altiProfilAdmin~admin.configuration.label@}</h1>

{formdatafull $form}

<!-- Modify -->
{ifacl2 'lizmap.admin.services.update'}
<div class="form-actions">
    <a class="btn" href="{jurl 'altiProfilAdmin~config:modify'}">
        {@admin~admin.configuration.button.modify.service.label@}
    </a>
</div>
{/ifacl2}
