# Run a command at startup.
define baselayout::startup($path, $arguments="", $user, $terminal=false) {

    if $operatingsystem == "windows" {
        # FIXME: assumes path to the Startup folder, because getting the real
        # path for a particular user is cumbersome (not directly supported by
        # puppet or facter)
        $manage_lnk = "c:\\qtqa\\bin\\qtqa-manage-lnk.pl"
        $lnk = "C:\\Users\\$user\\AppData\\Roaming\\Microsoft\\Windows\\Start Menu\\Programs\\Startup\\$name.lnk"
        $attrs = "\"Path=$path\" \"Arguments=$arguments\""

        # FIXME: we allow qtqa-manage-lnk.pl to use the destination user's local::lib
        # to increase the chance qtqa-manage-lnk.pl can find the needed modules (Win32::Shortcut).
        # This is a workaround - the correct solution is to enforce in puppet that the
        # prerequisite modules of qtqa-manage-lnk.pl are globally installed.
        $perl = "c:\\strawberry\\perl\\bin\\perl.exe -Mlocal::lib=C:\\Users\\$user\\perl5"

        exec { "enforce startup lnk $name":
            command => "$perl $manage_lnk --write $attrs \"$lnk\"",
            unless  => "$perl $manage_lnk --check $attrs \"$lnk\"",
            logoutput => true,
            require => File[$manage_lnk],
        }
    }

    if $kernel == "Linux" {
        # Uses freedesktop $HOME/.config/autostart, which seems to be supported on
        # most Linux for several years
        # Additional $terminal variable to fix execution issue on Ubuntu 11.10,
        # as it is broken on 11.10 (according to apt-file search nothing provides
        # xdg-terminal).
        file { "/home/$user/.config/autostart/$name.desktop":
            ensure  =>  present,
            owner   =>  $user,
            mode    =>  0755,
            content =>  template("baselayout/xdg-autostart.desktop.erb"),
            require =>  File["/home/$user/.config/autostart"],
        }
    }

    if $operatingsystem == "Darwin" {
        file { "/Users/$user/startup-$name.command":
            ensure  =>  present,
            owner   =>  $user,
            mode    =>  0755,
            content =>  template("baselayout/mac-startup.command.erb"),
        }

        exec { "$name login item":
            command => "/usr/bin/sudo -u $user /bin/sh -c \" \
                        \
                        defaults write loginwindow AutoLaunchedApplicationDictionary -array-add \
                        '<dict><key>Hide</key><false/><key>Path</key><string>/Users/$user/startup-$name.command</string></dict>' \
                        \
            \"",
            unless  => "/usr/bin/sudo -u $user /bin/sh -c \" \
                        \
                        defaults read loginwindow AutoLaunchedApplicationDictionary | grep -q /Users/$user/startup-$name.command
                        \
            \"",
            logoutput => true,
            require => File["/Users/$user/startup-$name.command"],
        }
    }
}
