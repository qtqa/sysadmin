import "private_sshkeys"

class sshkeys ($user = $baselayout::testuser, $group = $baselayout::testgroup) {

    if $user {
        $homepath = $::operatingsystem ? {
            Darwin  =>  "/Users/$user",
            Solaris =>  "/export/home/$user",
            windows =>  "C:\\Users\\$user",
            default =>  "/home/$user",
        }

        $sshdir = "$homepath/.ssh"

        File {
            owner   =>  $user,
            group   =>  $group,
        }

        file {
            "$sshdir":
                ensure  =>  directory,
                mode    =>  $::operatingsystem ? {
                    # .ssh directory should generally not be accessible to other users
                    # (and ssh may warn about this).  However, on Windows, a mode of 0700
                    # makes the directory unmanagable by puppet.
                    windows => 0770,
                    default => 0700,
                }
            ;
            "$sshdir/config":
                source  =>  $::operatingsystem ? {
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

        if $::operatingsystem != "windows" {
            # ssh will refuse to make use of a world-accessible id_rsa
            # (except on Windows - where a mode of 0600 makes the file unmanageable by puppet)
            file { "$sshdir/id_rsa":
                owner => $user,
                mode => 0600,
                require => Secret_file["$sshdir/id_rsa"]
            }

            # Let all trusted users (e.g. test farm sysadmins) log into $user account
            # (except on Windows - no sshd)
            trusted_authorized_keys { "authorized_keys for $user":
                user    =>  $user,
            }
        }
    }

    # Let all trusted users (e.g. test farm sysadmins) log into root account
    # Windows doesn't run sshd
    if $::operatingsystem != "windows" {
        trusted_authorized_keys { "authorized_keys for root":
            user    =>  "root",
        }
    }
}
