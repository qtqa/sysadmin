class ccache::unix
{
    if $ccache::user {
        $su = $::operatingsystem ? {
            default => "/bin/su",
        }

        $ccache = $::operatingsystem ? {
            default => "/usr/bin/ccache",
        }

        $egrep = $::operatingsystem ? {
            default => "/bin/egrep",
        }

        exec { "$su -c \"$ccache -M 4G\" - $ccache::user":
            unless => "$su -c \"$ccache -s | $egrep -q 'max cache size +4\\.0 Gbytes'\" - $ccache::user",
            require => Package["ccache"],
        }
    }
}
