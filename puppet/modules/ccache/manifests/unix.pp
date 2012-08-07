class ccache::unix
{
    if $ccache::user {
        $su = $::operatingsystem ? {
            Darwin => "/usr/bin/su",
            default => "/bin/su",
        }

        $ccache = $::operatingsystem ? {
            Darwin => "/opt/local/bin/ccache",
            default => "/usr/bin/ccache",
        }

        $egrep = $::operatingsystem ? {
            Darwin => "/usr/bin/egrep",
            default => "/bin/egrep",
        }

        exec { "$su -c \"$ccache -M 4G\" - $ccache::user":
            unless => "$su -c \"$ccache -s | $egrep -q 'max cache size +4\\.0 Gbytes'\" - $ccache::user",
            require => Package["ccache"],
        }
    }
}
