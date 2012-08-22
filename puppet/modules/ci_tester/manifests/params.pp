class ci_tester::params {
    $testuser = 'qt'
    $pulseagent_enabled = $::kernel ? {
        'windows' => false,
        default => true
    }

    if ($::operatingsystem == 'Ubuntu') and ($::operatingsystemrelease == '10.04') {
        $pulseagent_short_datadir = false
    } else {
        $pulseagent_short_datadir = true
    }

    $icecc_enabled = true
    $icecc_scheduler_host = ''

    if ($::operatingsystem == 'Ubuntu') and ($::operatingsystemrelease == '11.10') {
        $testcocoon_enabled = true
        if $::architecture == 'i386' {
            $armel_cross_enabled = true
        } else {
            $armel_cross_enabled = false
        }
    } else {
        $testcocoon_enabled = false
        $armel_cross_enabled = false
    }
}
