class jenkins_server::debian inherits jenkins_server
{

    # ================= jenkins package, service ================================

    exec { "install jenkins-ci apt-key":
        command => "/usr/bin/wget -O - http://pkg.jenkins-ci.org/debian/jenkins-ci.org.key | /usr/bin/apt-key add -",
        unless => "/usr/bin/apt-key list | /bin/grep -q D50582E6",
        logoutput => true,
    }

    file { "/etc/apt/sources.list.d/jenkins.list":
        ensure => present,
        content => "deb http://pkg.jenkins-ci.org/debian binary/\n",
        require => Exec["install jenkins-ci apt-key"],
        notify => Exec["apt-get update for jenkins-ci"],
    }

    exec { "apt-get update for jenkins-ci":
        command => "/usr/bin/apt-get update",
        refreshonly => true,
        logoutput => true,
    }

    package { "jenkins":
        ensure => present,
        require => Exec["apt-get update for jenkins-ci"],
    }

    service { "jenkins":
        ensure => running,
        require => [
            Package["jenkins"],
        ],
    }

    # ============================= jenkins plugins =====================================

    file { "/var/lib/jenkins/plugins":
        ensure => directory,
        owner => 'jenkins',
        group => 'nogroup',
        require => Package["jenkins"],
    }

    jenkins_server::plugin {
        "git":;                       # git SCM support
        "build-publisher":;           # publish build results to another Jenkins
        "conditional-buildstep":;     # make commands conditional on OS or on master vs slave
        "envfile":;                   # send CI-related env vars from master to slave
        "extended-read-permission":;  # enables the extended read permission
        "groovy-postbuild":;          # offline / reboot hook
        "next-build-number":;         # allow manual set of build numbers
        "postbuildscript":;           # CI post-build task
        "preSCMbuildstep":;           # CI pre-build task
        "project-health-report":;     # shows how many build failed, and which testcases are the top breakers
        "publish-over-ssh":;          # enables artifact uploading to remote server
        "run-condition":;             # conditional-buildstep prereq
        "timestamper":;               # timestamps in build logs
        "token-macro":;               # expand macros in various places
        "email-ext":;                 # extended email plugin to allow direct e-mails from jenkins
    }


    # ============================= apache2 -> jenkins setup ===========================

    if $jenkins_server::apache2_frontend {
        package { "apache2":
            ensure => present,
        }

        service { "apache2":
            ensure => running,
            require => Package["apache2"],
        }

        file { "/etc/apache2/sites-available/jenkins":
            ensure => present,
            content => template("jenkins_server/apache-jenkins-site.conf.erb"),
            require => Package["apache2"],
        }

        exec { "/usr/sbin/a2enmod proxy":
            creates => "/etc/apache2/mods-enabled/proxy.load",
            require => Package["apache2"],
        }

        exec { "/usr/sbin/a2enmod proxy_http":
            creates => "/etc/apache2/mods-enabled/proxy_http.load",
            require => Package["apache2"],
        }

        exec { "/usr/sbin/a2enmod vhost_alias":
            creates => "/etc/apache2/mods-enabled/vhost_alias.load",
            require => Package["apache2"],
        }

        exec { "/usr/sbin/a2ensite jenkins":
            creates => "/etc/apache2/sites-enabled/jenkins",
            require => [
                File["/etc/apache2/sites-available/jenkins"],
                Exec["/usr/sbin/a2dissite default"],
            ],
            notify => Service["apache2"],
        }

        # 'default' site is sometimes linked as 'default', sometimes as '000-default' - not sure why
        exec { "/usr/sbin/a2dissite default":
            onlyif => "/usr/bin/test -e /etc/apache2/sites-enabled/default || \
                       /usr/bin/test -e /etc/apache2/sites-enabled/000-default",
            require => Package["apache2"],
        }
    }
}
