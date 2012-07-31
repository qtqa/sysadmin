class kdesettings ($user = $baselayout::testuser, $group = $baselayout::testgroup) {
    File {
        owner   =>  "$user",
        group   =>  "$group",
        mode    =>  0644,
    }
    file {
        "/home/$user/.kde":
            ensure  =>  directory,
            mode    =>  0755,
        ;
        "/home/$user/.kde/share":
            ensure  =>  directory,
            mode    =>  0755,
            require =>  File["/home/$user/.kde"],
        ;
        "/home/$user/.kde/share/config":
            ensure  =>  directory,
            mode    =>  0755,
            require =>  File["/home/$user/.kde/share"],
        ;

        # Disable session management
        "/home/$user/.kde/share/config/ksmserverrc":
            ensure  =>  present,
            source  =>  "puppet:///modules/kdesettings/ksmserverrc",
            require =>  File["/home/$user/.kde/share/config"],
        ;

        # Disable screen saver
        "/home/$user/.kde/share/config/kscreensaverrc":
            ensure  =>  present,
            source  =>  "puppet:///modules/kdesettings/kscreensaverrc",
            require =>  File["/home/$user/.kde/share/config"],
        ;

        # Disable notification of updates available
        "/home/$user/.kde/share/config/update-notifier-kderc":
            ensure  =>  present,
            source  =>  "puppet:///modules/kdesettings/update-notifier-kderc",
            require =>  File["/home/$user/.kde/share/config"],
        ;
        "/home/$user/.kde/share/config/KPackageKit":
            ensure  =>  present,
            source  =>  "puppet:///modules/kdesettings/KPackageKit",
            require =>  File["/home/$user/.kde/share/config"],
        ;
        
        # Disable blanking of display
        "/home/$user/.kde/share/config/powerdevilprofilesrc":
            ensure  =>  present,
            source  =>  "puppet:///modules/kdesettings/powerdevilprofilesrc",
            require =>  File["/home/$user/.kde/share/config"],
        ;
    }
}

