class icecc(
    $scheduler_host = ''    # icecc scheduler; use autodiscovery if unset
) {
    case $::operatingsystem {
        Ubuntu:    { include icecc::ubuntu }
    }
}

