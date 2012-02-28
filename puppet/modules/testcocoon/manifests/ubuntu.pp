class testcocoon::ubuntu
{
    # Note, ~qtqa suffix denotes a slightly forked version of testcocoon.
    $testcocoon_version = '1.6.14~qtqa1'

    package { "testcocoon":
        ensure      =>  $testcocoon_version,
        require     =>  File["/etc/apt/sources.list.d/testcocoon-qtqa.list"],
    }

    # Enables the repository providing the desired version of testcocoon.
    file { "/etc/apt/sources.list.d/testcocoon-qtqa.list":
        ensure      =>  present,
        content     =>  template("testcocoon/testcocoon-qtqa.list.erb"),
        notify      =>  Exec["apt-get update for testcocoon"],
    }

    # Adds testcocoon tools to PATH
    file { "/etc/profile.d/testcocoon-qtqa.sh":
        ensure      =>  present,
        source      =>  "puppet:///modules/testcocoon/testcocoon-qtqa.sh",
    }

    # Runs apt-get update after new .list file provision
    exec { "apt-get update for testcocoon":
        command     =>  "/usr/bin/apt-get update",
        refreshonly =>  true,
    }
}



