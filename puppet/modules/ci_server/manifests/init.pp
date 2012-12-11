# Qt Project CI server
class ci_server {
    include puppet
    include jenkins_server

    case $::operatingsystem {
        Ubuntu: { include ci_server::debian }
        Debian: { include ci_server::debian }
        default: {
            alert( "No implementation for ci_server on $::operatingsystem" )
        }
    }
}

