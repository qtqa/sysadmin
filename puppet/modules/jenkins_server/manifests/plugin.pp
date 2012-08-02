# installs (or removes) a Jenkins plugin.
# Doesn't automatically restart Jenkins to load the plugin, so the plugin won't appear
# until the next restart.
#
# $name is the short name of the Jenkins plugin (as observed under
# http://updates.jenkins-ci.org/download/plugins/ ).
#
# $ensure may be one of 'present', 'absent', or a version string.
# 'present' implies the latest available version.
#
# Note that it is currently not possible to change the version of a plugin which is
# already installed (e.g. changing 'ensure => present' to 'ensure => "0.0.1"' will not
# downgrade an already-installed plugin).
#
define jenkins_server::plugin(
    $ensure = 'present'
) {

    $url = $ensure ? {
        'present' => "http://updates.jenkins-ci.org/latest/$name.hpi",
        default => "http://updates.jenkins-ci.org/download/plugins/$name/$ensure/$name.hpi",
    }

    $plugins_dir = "/var/lib/jenkins/plugins"

    # note: although the URL above uses .hpi (hudson plug-in) as the file extension,
    # the Jenkins plugin center renames .hpi to .jpi when installing, so we will do the same
    # for compatibility.
    $filename = "$plugins_dir/$name.jpi"

    case $ensure {

        'absent': {
            file { $filename:
                ensure => absent,
            }
        }

        default: {
            # note: --no-check-certificate because updates.jenkins-ci.org SSL setup is broken. Bad!
            exec { "install jenkins plugin $url -> $filename":
                command =>
                    "/bin/su -c '\
                        \
                        wget --no-check-certificate -O $name.jpi.downloading $url && \
                        mv $name.jpi.downloading $filename \
                        \
                    ' - jenkins"
                ,
                require => [
                    File[$plugins_dir],
                    Package["jenkins"],
                ],
                creates => $filename,
                logoutput => true,
            }
        }
    }

}
