# Unzip and installs a Jenkins plugin.
# Doesn't automatically restart Jenkins to load the plugin, so the plugin won't appear
# until the next restart.
#
define jenkins_server::zip_plugin(
    $url = undef
) {

    $plugins_dir = "/var/lib/jenkins/plugins"

    # note: although the URL above uses .hpi (hudson plug-in) as the file extension,
    # the Jenkins plugin center renames .hpi to .jpi when installing, so we will do the same
    # for compatibility.
    $filename = "$plugins_dir/$name.jpi"

    exec { "install jenkins plugin $url -> $filename":
        command =>
            "/bin/su -c '\
                \
                wget -q -O $name.zip.downloading $url && \
                unzip $name.zip.downloading && rm $name.zip.downloading && \
                mv $name/$name.hpi $filename \
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
