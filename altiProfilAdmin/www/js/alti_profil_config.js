function initAltiProfilConfig() {
      const selectProvider = document.getElementById('jforms_altiProfilAdmin_config_altiProfileProvider');
      if (!selectProvider) {
          return;
      }
      altiProfilOptionsProvider(selectProvider.value);
      selectProvider.addEventListener('change', function () {
          altiProfilOptionsProvider(selectProvider.value);
      });
}

function altiProfilOptionsProvider(selectedProvider) {
    const aOptions = ['database', 'ign'];
    for (const provider of aOptions) {
        const el = document.getElementById('jforms_altiProfilAdmin_config_' + provider);
        if (el && el.parentElement) {
            el.parentElement.style.display = (provider === selectedProvider) ? '' : 'none';
        }
    }
}

if (document.readyState !== 'loading') {
    initAltiProfilConfig();
} else {
    document.addEventListener('DOMContentLoaded', initAltiProfilConfig);
}