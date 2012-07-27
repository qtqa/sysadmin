class puppet::ubuntu inherits puppet::unix {

    # On Lucid, use backports to get a newer puppet.
    if $::lsbdistcodename == 'lucid' {
        include ubuntu_backports

        file { "/etc/apt/preferences.d/lucid-backports-puppet.conf":
            source => "puppet:///modules/puppet/lucid-backports-puppet.conf"
        }

        Package {
            require => File[ "/etc/apt/preferences.d/lucid-backports-puppet.conf" ]
        }

        package {
            "puppet": ensure => latest;
            "puppet-common": ensure => latest;
        }
    }

}
