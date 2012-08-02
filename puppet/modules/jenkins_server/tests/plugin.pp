if $::operatingsystem == 'windows' {
    selftest::skip_all { "jenkins_server::plugin is not supported on Windows": }
}

selftest::expect_no_warnings { "no warnings from jenkins_server::plugin": }

# mock dependencies normally provided by jenkins_server
if $::operatingsystem != 'windows' {
    file { "/var/lib/jenkins/plugins": }
    package { "jenkins": }
}

jenkins_server::plugin { "quux": }

jenkins_server::plugin { "foo": ensure => absent; }

jenkins_server::plugin { "baz": ensure => '1.2.3'; }

selftest::expect { 'latest quux installed':
    output => 'Exec\[install jenkins plugin http://updates.jenkins-ci.org/latest/quux.hpi -> /var/lib/jenkins/plugins/quux.jpi\]',
}

selftest::expect { 'baz 1.2.3 installed':
    output => 'Exec\[install jenkins plugin http://updates.jenkins-ci.org/download/plugins/baz/1.2.3/baz.hpi -> /var/lib/jenkins/plugins/baz.jpi\]',
}
