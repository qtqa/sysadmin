# For fetching of files too sensitive for any source control
include secret_file

class baselayout (
    $testuser = '',
    $testgroup = $::operatingsystem ? {
        Darwin  =>  "staff",
        Solaris =>  "other",
        default =>  "users",
    },
    $tempdir = $::operatingsystem ? {
        windows =>  'C:\Windows\Temp',
        default =>  '/tmp'
    }
) {
    file { $tempdir:
        ensure => directory
    }

    case $::operatingsystem {
        Darwin:     { include baselayout::mac }
        Ubuntu:     { include baselayout::ubuntu }
        CentOS:     { include baselayout::centos }
        Linux:      { include baselayout::linux }
        Solaris:    { include baselayout::solaris }
        windows:    { include baselayout::windows }
    }
}

