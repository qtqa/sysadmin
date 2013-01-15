class packaging_server::debian inherits packaging_server
{
    # ============================= jenkins plugins =====================================

    jenkins_server::zip_plugin {
        "SquishPlugin":
        url => "http://download.froglogic.com/resources/squish-hudson-jenkins-plugin_latest.zip",                     # Squish plugin
    }
}
