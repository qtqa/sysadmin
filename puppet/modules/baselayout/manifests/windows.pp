class baselayout::windows inherits baselayout::base {
    if $baselayout::testuser {
        # Create the user:
        #  - standard home directory
        #  - in the Administrators group
        #  - password is equal to username
        #
        # Note that the user directory structure (Documents, AppData etc)
        # are created on Windows after the initial login. Therefore, the
        # completion of this resource does _not_ guarantee that "C:\Users\<testuser>\AppData"
        # etc are all existing and usable. The automated reboot is supposed to
        # achieve that.
        #
        user { $baselayout::testuser:
            ensure => present,
            managehome => true,
            home => "C:\\Users\\$baselayout::testuser",
            groups => "Administrators",
            password => $baselayout::testuser
        }

        # We want to automatically reboot and login as the testuser.
        # However, we give a generous timeout period and helpful message to abort the
        # shutdown in case somebody is doing something on the machine.
        exec { "reboot for auto-login":
            command => "C:\\Windows\\system32\\shutdown.exe /r /t 180 /c \"Automated reboot to log in as $baselayout::testuser. To abort, run: shutdown /a\"",
            refreshonly => true
        }

        # Other things here depend on the user
        Git::Config {
            user => $baselayout::testuser,
            require => User[$baselayout::testuser]
        }

        Tidy { require => User[$baselayout::testuser] }
        Registry::Value { require => User[$baselayout::testuser] }

        # automatically log on as this user
        $reg_winlogon_key = 'HKLM\SOFTWARE\Microsoft\Windows NT\CurrentVersion\Winlogon'
        registry::value {
            "autologon enabled":
                key => $reg_winlogon_key,
                value => "AutoAdminLogon",
                data => "1",
                notify => Exec["reboot for auto-login"],
                require => Registry::Value["autologon user", "autologon password"];
            "autologon user":
                key => $reg_winlogon_key,
                value => "DefaultUserName",
                data => $baselayout::testuser;
            "autologon password":
                key => $reg_winlogon_key,
                value => "DefaultPassword",
                data => $baselayout::testuser;
        }


        # clean testuser's temp periodically; if we don't, then nothing will clean
        # up temporary files/directories from crashing/hanging tests
        tidy { "C:\\Users\\$baselayout::testuser\\AppData\\Local\\Temp":
            age => "1w",
            recurse => true,
            rmdirs => true,
        }

        git::config {
            "core.autocrlf": content => "true";
        }

        # avoid screensaver interrupting UI tests
        registry::value { "screensaver off":
            key => "HKU\\$baselayout::testuser\\Control Panel\\Desktop",
            value => "SCRNSAVE.EXE",
            ensure => absent,
        }
    }

    # Avoid system suspend or screen blanking.
    # On Windows, these magic numbers can be determined with the help of the 'powercfg' command,
    # e.g. try 'powercfg -query'.  The GUIDs appear to be portable across systems.
    $high_performance_guid = '8c5e7fda-e8bf-4a96-9a85-a6e23a8c635c'
    $schemes_path = 'HKLM\SYSTEM\CurrentControlSet\Control\Power\User\PowerSchemes'
    $display_path = "$schemes_path\\$high_performance_guid\\7516b95f-f776-4464-8c53-06167f40cc99\\3c0bc021-c8a8-4e07-a973-6b14cbcb2b7e"
    $sleep_path = "$schemes_path\\$high_performance_guid\\238c9fa8-0aad-41ed-83f4-97be242c8f20\\29f6c1db-86da-48c5-9fdb-f2b67b1f44da"

    registry::value { "use high-performance scheme":
        key => $schemes_path,
        value => 'ActivePowerScheme',
        data => $high_performance_guid
    }

    registry::value { "don't blank the screen":
        key => $display_path,
        value => 'ACSettingIndex',
        type => 'dword',
        data => '0x00000000'
    }

    registry::value { "don't sleep":
        key => $sleep_path,
        value => 'ACSettingIndex',
        type => 'dword',
        data => '0x00000000'
    }

    # by default, Windows will pop up interactive crash dialogs, which blocks test suite
    # execution if any test crashes; disable it
    registry::value { "avoid interactive crash dialogs":
        key => 'HKLM\Software\Microsoft\Windows\Windows Error Reporting',
        value => 'ForceQueue',
        type => 'dword',
        data => '0x00000001'
    }
}

