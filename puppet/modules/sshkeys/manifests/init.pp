import "*"

import "private_sshkeys"

class sshkeys {

    if $testuser {
        $homepath = $operatingsystem ? {
            Darwin  =>  "/Users/$testuser",
            Solaris =>  "/export/home/$testuser",
            default =>  "/home/$testuser",
        }

        $sshdir = "$homepath/.ssh"

        File {
            owner   =>  $testuser,
            group   =>  $testgroup,
        }

        file {
            "$sshdir":
                ensure  =>  directory,
                mode    =>  0700,
            ;
            "$sshdir/config":
                source  =>  $operatingsystem ? {
                    # Solaris ssh does not understand SendEnv
                    Solaris =>  "puppet:///modules/sshkeys/config.basic",
                    default =>  "puppet:///modules/sshkeys/config",
                },
                mode    =>  0644,
                require =>  File["$sshdir"],
            ;
        }

        # public, private ssh keys for machines on test farm.
        secret_file {
            "$sshdir/id_rsa.pub":
                source  =>  "test_farm_id_rsa.pub",
                require =>  File["$sshdir"],
            ;
            "$sshdir/id_rsa":
                source  =>  "test_farm_id_rsa",
                require =>  File["$sshdir"],
            ;
        }

        # Let all trusted users (e.g. test farm sysadmins) log into $testuser account
        trusted_authorized_keys { "authorized_keys for $testuser":
            user    =>  $testuser,
        }
    }

    # Let all trusted users (e.g. test farm sysadmins) log into root account
    trusted_authorized_keys { "authorized_keys for root":
        user    =>  "root",
    }
}
