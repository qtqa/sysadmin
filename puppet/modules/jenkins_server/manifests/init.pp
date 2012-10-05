class jenkins_server (
    $hostname = $::hostname,
    $fqdn = $::fqdn,
    $apache2_frontend = true
) {
    case $::operatingsystem {
        Ubuntu: { include jenkins_server::debian }
        Debian: { include jenkins_server::debian }
        default: {
            alert( "No implementation for jenkins_server on $::operatingsystem" )
        }
    }
}
