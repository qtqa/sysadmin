class enginio ($user = $baselayout::testuser, $group = $baselayout::testgroup) {

    if $user {
        $homepath = $::operatingsystem ? {
            Darwin  =>  "/Users/$user",
            Solaris =>  "/export/home/$user",
            windows =>  "C:\\Users\\$user",
            default =>  "/home/$user",
        }

        $enginiodir = "$homepath/enginio"

        File {
            owner   =>  $user,
            group   =>  $group,
        }

        file {
            "$enginiodir":
                ensure  =>  directory,
                mode    =>  $::operatingsystem ? {
                    windows => 0777,
                    default => 0700,
                }
        }

        # enginio credentials file on test farm.
        secret_file {
            "$enginiodir/credentials":
                source  =>  "test_farm_enginio_credentials",
                require =>  File["$enginiodir"],
                ;
        }
    }
}
