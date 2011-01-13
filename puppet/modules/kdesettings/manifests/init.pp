class kdesettings {
    File {
        owner   =>  "$testuser",
        group   =>  "$testgroup",
        mode    =>  0644,
    }
    file {
        "/home/$testuser/.kde":
            ensure  =>  directory,
            mode    =>  0755,
        ;
        "/home/$testuser/.kde/share":
            ensure  =>  directory,
            mode    =>  0755,
            require =>  File["/home/$testuser/.kde"],
        ;
        "/home/$testuser/.kde/share/config":
            ensure  =>  directory,
            mode    =>  0755,
            require =>  File["/home/$testuser/.kde/share"],
        ;

        # Disable session management
        "/home/$testuser/.kde/share/config/ksmserverrc":
            ensure  =>  present,
            source  =>  "puppet:///modules/kdesettings/ksmserverrc",
            require =>  File["/home/$testuser/.kde/share/config"],
        ;

        # Disable screen saver
        "/home/$testuser/.kde/share/config/kscreensaverrc":
            ensure  =>  present,
            source  =>  "puppet:///modules/kdesettings/kscreensaverrc",
            require =>  File["/home/$testuser/.kde/share/config"],
        ;

        # Disable notification of updates available
        "/home/$testuser/.kde/share/config/update-notifier-kderc":
            ensure  =>  present,
            source  =>  "puppet:///modules/kdesettings/update-notifier-kderc",
            require =>  File["/home/$testuser/.kde/share/config"],
        ;
        "/home/$testuser/.kde/share/config/KPackageKit":
            ensure  =>  present,
            source  =>  "puppet:///modules/kdesettings/KPackageKit",
            require =>  File["/home/$testuser/.kde/share/config"],
        ;
        
        # Disable blanking of display
        "/home/$testuser/.kde/share/config/powerdevilprofilesrc":
            ensure  =>  present,
            source  =>  "puppet:///modules/kdesettings/powerdevilprofilesrc",
            require =>  File["/home/$testuser/.kde/share/config"],
        ;
    }
}

