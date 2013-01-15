# Qt Project packaging server
class packaging_server {
    include puppet
    include jenkins_server

    case $::operatingsystem {
        Ubuntu: { include packaging_server::debian }
        Debian: { include packaging_server::debian }
        default: {
            alert( "No implementation for packaging_server on $::operatingsystem" )
        }
    }


}
