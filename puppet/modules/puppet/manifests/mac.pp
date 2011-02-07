class puppet::mac inherits puppet::unix {
    service { "com.reductivelabs.puppet":
        ensure      =>  stopped,
        enable      =>  false,
    }

    # This is a workaround for http://projects.puppetlabs.com/issues/2331 .
    # Without this, puppet will attempt to run `port upgrade' for packages which
    # aren't yet installed, which does not work.
    $remote_macports_rb = "http://projects.puppetlabs.com/attachments/download/882/macports.rb"
    $local_macports_rb = "/opt/local/lib/ruby/site_ruby/1.8/puppet/provider/package/macports.rb"
    exec { "patch puppet for bug 2331":
        command     =>  "/usr/bin/curl $remote_macports_rb -o $local_macports_rb",
        creates     =>  $local_macports_rb,
    }
}

