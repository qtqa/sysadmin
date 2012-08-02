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
            File["/var/lib/jenkins/.profile"],  # don't run until .profile set up
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
        "git":;                     # git SCM support
        "conditional-buildstep":;   # make commands conditional on OS or on master vs slave
        "envfile":;                 # send CI-related env vars from master to slave
        "groovy-postbuild":;        # offline / reboot hook
        "next-build-number":;       # allow manual set of build numbers
        "postbuildscript":;         # CI post-build task
        "preSCMbuildstep":;         # CI pre-build task
        "run-condition":;           # conditional-buildstep prereq
        "timestamper":;             # timestamps in build logs
        "token-macro":;             # expand macros in various places
    }

    # ============================== qtqa setup in jenkins homedir ======================

    file { "/var/lib/jenkins/.profile":
        ensure => present,
        source => "puppet:///modules/jenkins_server/dot.profile",
        require => Package["jenkins", "liblocal-lib-perl"],
        owner => "jenkins",
        group => "nogroup",
    }

    # all packages needed for qtqa repo setup.
    # most of these are to support a working homedir CPAN setup
    # (able to compile and install XS modules)
    $qtqa_packages = [
        "git",
        "libwww-perl",
        "liblocal-lib-perl",
        "libc6-dev",
        "libexpat1-dev",
        "make"
    ]

    package { $qtqa_packages: ensure => installed; }

    exec { "clone qtqa into jenkins homedir":
        command => "/bin/su -c \"   \
            \
            rm -rf qtqa.cloning && \
            git clone git://qt.gitorious.org/qt/qtqa qtqa.cloning && \
            mv -v qtqa.cloning qtqa && \
            eval \$(perl -Mlocal::lib) && \
            qtqa/scripts/setup.pl --install \
            \
            \" - jenkins",
        require => Package[
            $qtqa_packages,
            "jenkins"       # jenkins package creates jenkins user
        ],
        timeout => 360,     # allow 1 hour for installation (can be slow)
        creates => "/var/lib/jenkins/qtqa/scripts/setup.pl",
        logoutput => true,
    }

    cron { "update qtqa":
        command =>
            "( \
                source \$HOME/.profile && \
                cd qtqa && \
                git fetch --quiet origin && \
                git reset --quiet --hard origin/master && \
                git clean -dqffx . \
            ) 2>&1 | logger -t jenkins-qtqa-update",
        user => "jenkins",
        hour => "*/2",
        minute => "20",
        require => Exec["clone qtqa into jenkins homedir"],
    }

    # ======================== 'reliable' versions of git, scp =========================
    $reliable = "/var/lib/jenkins/qtqa/scripts/generic/reliable.pl"
    $reliable_bin = "/var/lib/jenkins/reliable-bin"

    file {
        $reliable_bin:
            ensure => directory,
            mode => 0755,
            owner => "jenkins",
            group => "nogroup",
        ;

        # currently we don't deploy a reliable ssh because we'll want to run some ssh
        # commands which read from STDIN and we don't have a decent way to automatically
        # retry those
        ["$reliable_bin/git", "$reliable_bin/scp"]:
            ensure => link,
            target => $reliable,
            owner => "jenkins",
            group => "nogroup",
            require => [
                File[$reliable_bin],
                Exec["clone qtqa into jenkins homedir"],
            ],
        ;
    }


    # ======================= ssh setup ================================================

    Sshkey { type => "ssh-rsa" }

    sshkey { "[codereview.qt-project.org]:29418":
        key => "AAAAB3NzaC1yc2EAAAADAQABAAAAgQCvXdApmCFiAyXDiYU5+z6762Qv8+vrmM3+9YrxDKByyphaxblLJC9txPv3D/w7rzSyiMMHL/5ssCemwz+6QBqnemFl4B+FNv81fpZFsqCg5afrTi62WFllGWIQAiYb2JZmkmSAbxm+sAxLE1ritp+Syxz8Gb8WR27G/3TSHerdBQ==",
    }
    sshkey { "[dev-codereview.qt-project.org]:29418":
        key => "AAAAB3NzaC1yc2EAAAADAQABAAAAgQDSl0SfLVrmQf5lxz8/Xo5IYa8DSymJkc8lNDQx0ZHySzveR5RxLtAqhxKN8HXYyz22xImOkr9Lu8tt4OKx7+SsN/LXV9zARdK9enJk7pEatmD/9GhwhhgKLtCKGuGrSxiTvDyesg6TVL59pdyXom+E8lU/fOhf2Qv6+8+Ow7EGow==",
    }
    sshkey { "testresults.qt-project.org":
        key => "AAAAB3NzaC1yc2EAAAABIwAAAQEAtrQv3v3jueb2+yDB92ZIpoFyKCCOVOS7glVSqcfZrbLkbjZFIEPc64FxQ9p8G3NBBoHiL6gDtuI+Gyx5xCOm+5OP/dfM/JDnbAM8cqEGgNvrOIM80p3xB/rCpSrLwsvPy5Rb49L+MvAsYJXuR08yOSCNC+Zj+szUe2bkgGufk+DgOI/NTWwaIWEhY3bkf6ECdR+itS22/pnF/dq0BTslt7dxDNqGnNEi7QZ2K2ZsiyjRTna2yr/glqpStS3egBq92x6a7gM1da8vhlyYnzoYZu1JYpQpba8GIIbBI5D6inGMwA436AAS4C8NuCxZDcmJ+G/FUdkIDkkPx7qGbD279Q==",
    }
    sshkey { "dev-testresults.qt-project.org":
        key => "AAAAB3NzaC1yc2EAAAABIwAAAQEA95lhjFPV9U+b1K3U3tZAZ7ARgW/oWCRc0f2CnW5IJ1SHDOztt0moKfREPt5UFHk3zugOYF3aPKXE/gdCyhFvkqOUYzdd7rAOwOHUvFVUZRU3ffw/j9n9i2MoQEC9qORM0NzUEHFtjv1MtoFV95ZW1RugMC9MXvkZ/LvQvRjXX9JBB6mn8iyvYbRcKXBzFDm/KS5ICqhBsOx27iR9gbLSvzo0l/iaj25CMxNZd3YVsJhlAFJviFvWpY5U+UM0GHrsZ/B2KyRJ2aDpPSfKEAGr7rTdredpwpTeKdZvbqZhFxSGL8+vmLQzGPUzpF9k3H6a6jRPkS8x1cPZQzKoxdoufw==",
    }

    # make sure ssh_known_hosts is world-readable
    file { "/etc/ssh/ssh_known_hosts":
        mode => 0644,
    }

    file {
        "/var/lib/jenkins/.ssh":
            ensure => directory,
            mode => 0755,
            owner => "jenkins",
            group => "nogroup",
            require => Package["jenkins"],
        ;
        "/var/lib/jenkins/.ssh/config":
            ensure => present,
            source => "puppet:///modules/jenkins_server/ssh_config",
            mode => 0644,
            owner => "jenkins",
            group => "nogroup",
            require => File["/var/lib/jenkins/.ssh"],
        ;
    }

    # generate a warning each time we are run until this key is set up.
    $pubkey = 'AAAAB3NzaC1yc2EAAAABIwAAAQEAyjPYlQyr2HIvREjuTk3kqY7vLGa6C66F8ykChSw7tEtEUFC6vFByrmL8AiaWrLaEuHXsh1adsoOjwxi7UVBJML05kGXo0B6HyVhM+2NuixyBTQQrQUZaWX56aWnSlDKfJacVEVQZlXwk2bpDX9wbpJ5yr/b95rnOz327eU1cscReJT4GZ79lOMtYdMUYssh6s9DEBDPl3iqELhTDMOaWpXihqrVrbI9PWngo1muQKfBTeTuTk9/fLFI+BwXRv6iqmI5DdWbmEfQqeGDgBfh6XR2Wz6GjRQX2IHZIPeT9VxgSohKX41F4qjeYGwOY2iSFwFviiy+hugAJMuJR3lMzUQ=='

    exec { "warn about jenkins ssh key":
        command => "/bin/echo 'WARNING: manual installation of Jenkins ssh key is required, matching public key: $pubkey'",
        logoutput => true,
        require => File["/var/lib/jenkins/.ssh"],
        unless => "/bin/grep -q -F '$pubkey' /var/lib/jenkins/.ssh/id_rsa.pub",
    }

    # ================================= git setup ======================================

    Git::Config {
        require => Package["git","jenkins"],
        file => "/var/lib/jenkins/.gitconfig",
    }

    git::config { "jenkins user.name":
        key => "user.name",
        content => "Qt Project Jenkins",
        user => "jenkins",
    }

    git::config { "jenkins user.email":
        key => "user.email",
        content => "jenkins@qt-project.org",
        user => "jenkins",
    }

    # ================================= gerrit -> jenkins notify trigger(s) ============
    # environment; warnings and worse go to syslog
    $env = "/usr/bin/env PERL_ANYEVENT_VERBOSE=5 PERL_ANYEVENT_LOG=log=syslog"

    # start-stop-daemon base cmd
    $start_stop_daemon = "start-stop-daemon --chuid jenkins:nogroup --background --user jenkins --exec /usr/bin/perl --make-pidfile --startas /bin/sh"

    # script base cmd (--gerrit-url part omitted)
    $sh_args = "exec perl /var/lib/jenkins/qtqa/scripts/jenkins/gerrit-notify-jenkins.pl --jenkins-url http://127.0.0.1:8080/"

    # pid file base
    $pidfile_base = "/var/run/gerrit-notify-jenkins"

    exec { "gerrit-notify-jenkins for codereview":
        command => "$env $start_stop_daemon --pidfile $pidfile_base-codereview.pid --start -- -l -c '$sh_args --gerrit-url ssh://qtintegration@codereview.qt-project.org:29418/'",
        onlyif => "$env $start_stop_daemon --pidfile $pidfile_base-codereview.pid --test --start",
        require => Cron["update qtqa"],
    }

    exec { "gerrit-notify-jenkins for dev-codereview":
        command => "$env $start_stop_daemon --pidfile $pidfile_base-dev-codereview.pid --start -- -l -c '$sh_args --gerrit-url ssh://qtintegration@dev-codereview.qt-project.org:29418/'",
        onlyif => "$env $start_stop_daemon --pidfile $pidfile_base-dev-codereview.pid --test --start",
        require => Cron["update qtqa"],
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
