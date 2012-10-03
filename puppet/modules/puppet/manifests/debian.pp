class puppet::debian inherits puppet::unix {

    # On Debian Squeeze, use backports to get a newer puppet:
    #  squeeze: 2.6.2 is affected by http://projects.puppetlabs.com/issues/5022
    if ($::lsbdistcodename == 'squeeze') {
        include apt_backports

        file { "/etc/apt/preferences.d/$::lsbdistcodename-backports-puppet.pref":
            content => template("puppet/backports-puppet.pref.erb"),
        }

        Package {
            require => File[ "/etc/apt/preferences.d/$::lsbdistcodename-backports-puppet.pref" ]
        }

        package {
            "puppet": ensure => latest;
            "puppet-common": ensure => latest;
        }
    }

}
