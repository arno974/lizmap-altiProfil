$( document ).ready(function() {
    const selectProvider = "#jforms_altiProfilAdmin_config_altiProfileProvider";
    altiProfilOptionsProvider($(selectProvider).val());
    $( selectProvider ).change(function() {
        altiProfilOptionsProvider($(selectProvider).val());
    });

});

function altiProfilOptionsProvider(selectedProvider){
    const aOptions = ["database","ign"];
    for (const provider of aOptions){
        if(provider === selectedProvider){
            $("#jforms_altiProfilAdmin_config_" + selectedProvider).parent().show();
        }else{
            console.log(provider);
            $("#jforms_altiProfilAdmin_config_" + provider).parent().hide();
        }
    }
}
