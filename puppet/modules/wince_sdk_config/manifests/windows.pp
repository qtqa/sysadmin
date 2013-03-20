# Makes wince SDK configuration file puppet controlled.
#
# When cross-compiling Qt for WEC7, qmake reads WCE.VCPlatform.config file
# to determine correct environment variables for cross-compilation.
#
class wince_sdk_config::windows {
    $config = $::architecture ? {
        x64     => 'c:\Program Files (x86)\Microsoft Visual Studio 9.0\VC\vcpackages\WCE.VCPlatform.config',
        default => 'c:\Program Files\Microsoft Visual Studio 9.0\VC\vcpackages\WCE.VCPlatform.config'
    }

    file { "$config":
        source  => "puppet:///modules/wince_sdk_config/windows/WCE.VCPlatform.config",
        ensure  => present,
        mode    => 0755,
    }
}
