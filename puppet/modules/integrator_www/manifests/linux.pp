class integrator_www::linux {

    if ($operatingsystem == "SuSE") {
        Package {
            provider => "zypper",
        }
    }

    package { "lighttpd":
        ensure => present,
    }

    if ($operatingsystem == "CentOS") {
        package { "lighttpd-fastcgi":
            ensure => present,
        }
    }

    file { "/etc/lighttpd/lighttpd.conf":
        ensure => present,
        content => template("integrator_www/lighttpd.conf.erb"),
        require => Package["lighttpd"],
    }

    file { "/var/www":
        ensure => directory,
    }

    file { "/var/www/lighttpd":
        ensure => directory,
        require => File["/var/www"],
        owner => "lighttpd",
        group => "lighttpd",
    }

    file { "/var/run/lighttpd":
        ensure => directory,
        owner => "lighttpd",
        group => "lighttpd",
        mode  => 0777,
    }

    service { "lighttpd":
        subscribe => File["/etc/lighttpd/lighttpd.conf", "/var/www/lighttpd"],
        ensure    => running,
        enable    => true,
    }

    file { "/etc/init.d/integrator-www":
        ensure => present,
        source => "puppet:///modules/integrator_www/integrator-www.init",
        mode   => 0755,
        require => Service["lighttpd"],
    }

    service { "integrator-www":
        subscribe => File["/etc/init.d/integrator-www"],
        enable    => true,
    }

    secret_file { "/etc/lighttpd/$fqdn.pem":
        source  =>  "integrator.test.qt.nokia.com.pem",
    }

    # Needed to build IO::Socket::SSL
    package {
        "openssl-devel":    ensure  =>  present;
    }

    # Perl modules used by the catalyst app
    include cpan
    cpan_package {
        # NOTE: these two are just enough to get Makefile.PL working...
        "inc::Module::Install":;
        "Module::Install::Catalyst":;

        # ... and this is everything else - well, hopefully everything, but
        # in practice it might be necessary to manually run Makefile.PL
        "Catalyst::Model::DBIC::Schema":;
        "Catalyst::Plugin::Authentication":;
        "Catalyst::Plugin::Session":;
        "Catalyst::Plugin::Session::State::Cookie":;
        "Catalyst::Plugin::Session::Store::FastMmap":;
        "Catalyst::View::JSON":;
        "Catalyst::View::TT":;
        "DBICx::TestDatabase":;
        "DateTime::Format::SQLite":;
        "FCGI":;
        "FCGI::ProcManager":;
        "IO::Socket::SSL": require => Package["openssl-devel"];
        "MooseX::NonMoose":;
        "Net::LDAP":;
    }
}

