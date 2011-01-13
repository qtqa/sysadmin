class pulseserver_token::unix {
    $user = "pulseserver"
    $homedir = "/home/$user",

    # This module is a no-op by default.
    # If you want to enforce a particular service.token you can do it here
    #file {
    #    "$homedir/.pulse2/data/config/service.token":
    #        ensure  =>  present,
    #        source  =>  "puppet:///modules/pulseserver_token/pulseconfig/service.token",
    #        require =>  File["$homedir/.pulse2/data/config"],
    #    ;
    #}
}

