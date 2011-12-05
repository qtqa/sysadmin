class pulseserver::unix {
    $user          = "pulseserver"
    $pulse_version = "2.3.12"

    File {
        owner   =>  $user,
        group   =>  "users",
    }

    user { $user:
        ensure      =>  present,
        uid         =>  1100,
        gid         =>  "users",
        home        =>  "/home/$user",
        managehome  =>  true,
    }

    $homedir = $operatingsystem ? {
        default =>  "/home/$user",
    }

    $pulseserver_dir = "$homedir/pulse"

    file { "pulse server directory":
        name    =>  $pulseserver_dir,
        ensure  =>  directory,
    }

    exec { "install pulse server":
        subscribe   =>  File["pulse server directory"],
        # NOTE! tar might use --strip-path or --strip-components, we don't know which in advance.
        # Just try both.
        command     =>  "/bin/sh -c 'wget -O - $input/pulse-$pulse_version.tar.gz > $homedir/pulse-server-install.tar.gz && { tar -xvzf $homedir/pulse-server-install.tar.gz -C $pulseserver_dir --strip-component 1 || tar -xvzf $homedir/pulse-server-install.tar.gz -C $pulseserver_dir --strip-path 1; } && chown -R $user:users $pulseserver_dir'",
        creates     =>  "$pulseserver_dir/bin/pulse",
    }

    file { "pulse server script":
        name    =>  "$homedir/pulse-server.sh",
        ensure  =>  present,
        source  =>  "puppet:///modules/pulseserver/pulse-server.sh",
        mode    =>  0755,
    }

    file { "$homedir/bin":
        ensure  =>  directory,
        mode    =>  0755,
    }

    # Scripts supporting the log synchronization to testresults.qt-project.org
    file {
        "$homedir/bin/publish_log_hook":
            ensure  =>  present,
            source  =>  "puppet:///modules/pulseserver/publish_log_hook",
            mode    =>  0755,
            require =>  File["$homedir/bin"],
        ;
        "$homedir/bin/publish_log_hook.DEV":
            ensure  =>  present,
            source  =>  "puppet:///modules/pulseserver/publish_log_hook.DEV",
            mode    =>  0755,
            require =>  File["$homedir/bin/publish_log_hook"],
        ;
        "$homedir/bin/publish_log_hook.PROD":
            ensure  =>  present,
            source  =>  "puppet:///modules/pulseserver/publish_log_hook.PROD",
            mode    =>  0755,
            require =>  File["$homedir/bin/publish_log_hook"],
        ;
    }

    file { "$homedir/bin/pulseserver-git":
        ensure  =>  present,
        source  =>  "puppet:///modules/pulseserver/pulseserver-git",
        mode    =>  0755,
        require =>  File["$homedir/bin"],
    }

    # symlink $HOME/bin/git to our wrapper script.
    file { "$homedir/bin/git":
        ensure  =>  "$homedir/bin/pulseserver-git",
        require =>  File["$homedir/bin/pulseserver-git"],
    }

    # maintain the git cache regularly
    cron { "pulseserver-git maintain-cache":
        command =>  "$homedir/bin/pulseserver-git maintain-cache",
        user    =>  $user,
        hour    =>  [ 3, 15 ],
        minute  =>  [ 10 ],
        require =>  File["$homedir/bin/pulseserver-git"],
    }

    package { "postgresql-server":
        ensure  => installed,
    }

    exec { "initdb":
        command => "/bin/su -l postgres -c \"/usr/bin/initdb --pgdata='/var/lib/pgsql/data' --auth='ident sameuser'\"",
        creates => "/var/lib/pgsql/data/PG_VERSION",
    }

    file {
        "/var/lib/pgsql/data/pg_hba.conf":
            source  =>  "puppet:///modules/pulseserver/pg_hba.conf",
            owner   =>  "postgres",
            group   =>  "postgres",
            require =>  Exec["initdb"],
        ;
        "/var/lib/pgsql/data/postgresql.conf":
            source  =>  "puppet:///modules/pulseserver/postgresql.conf",
            owner   =>  "postgres",
            group   =>  "postgres",
            require =>  Exec["initdb"],
        ;
    }

    exec {
        "createuser":
            command => "/bin/su -l postgres -c \"createuser --no-superuser --no-createdb --no-createrole pulsemaster && touch /var/lib/pgsql/created_pulsemaster_user\"",
            creates => "/var/lib/pgsql/created_pulsemaster_user",
            require => Service["postgresql"],
        ;
        "createdb":
            command => "/bin/su -l postgres -c \"createdb --owner=pulsemaster pulse && touch /var/lib/pgsql/created_pulse_db\"",
            creates => "/var/lib/pgsql/created_pulse_db",
            require => Exec["createuser"],
        ;
    }

    service { "postgresql":
        ensure    => running,
        enable    => true,
        hasstatus => true,
        subscribe => File["/var/lib/pgsql/data/postgresql.conf"],
    }

    # create directory hierarchy for Pulse configuration file(s)
    file {
        "$homedir":
            ensure  =>  directory,
            mode    =>  0755,
        ;
        "$homedir/.pulse2":
            ensure  =>  directory,
            mode    =>  0755,
            require => File["$homedir"],
        ;
        "$homedir/.pulse2/data":
            ensure  =>  directory,
            mode    =>  0755,
            require => File["$homedir/.pulse2"],
        ;
        "$homedir/.pulse2/data/driver":
            ensure  =>  directory,
            mode    =>  0755,
            require => File["$homedir/.pulse2/data"],
        ;
        "$homedir/.pulse2/data/config":
            ensure  =>  directory,
            mode    =>  0755,
            require => File["$homedir/.pulse2/data"],
        ;
    }

    file {
        "$homedir/.pulse2/config.properties":
            ensure  =>  present,
            content =>  template("pulseserver/config.properties.erb"),
            require =>  File["$homedir/.pulse2"],
        ;
        "$homedir/.pulse2/data/config/database.properties":
            ensure  =>  present,
            source  =>  "puppet:///modules/pulseserver/pulseconfig/database.properties",
            require =>  File["$homedir/.pulse2/data/config"],
        ;
        "$homedir/.pulse2/data/config/database.user.properties":
            ensure  =>  present,
            source  =>  "puppet:///modules/pulseserver/pulseconfig/database.user.properties",
            require =>  File["$homedir/.pulse2/data/config"],
        ;
    }

    $postgres_jdbc = "postgresql-8.4-702.jdbc3.jar"

    exec { "fetch postgresql jdbc driver":
        require     =>  File["$homedir/.pulse2/data/driver"],
        command     =>  "/usr/bin/wget -O $homedir/.pulse2/data/driver/$postgres_jdbc $input/java/$postgres_jdbc",
        creates     =>  "$homedir/.pulse2/data/driver/$postgres_jdbc",
    }
}

