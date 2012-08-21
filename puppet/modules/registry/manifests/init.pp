# resources and variables shared among registry defined types
class registry {
    $script = "C:\\qtqa\\bin\\qtqa-reg.pl"
    file { $script:
        ensure => present,
        source => "puppet:///modules/registry/qtqa-reg.pl",
        mode => 0755
    }
}
