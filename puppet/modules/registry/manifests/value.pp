# Manage a registry value.
#
# This type is very similar to registry::value from the puppetlabs-registry module
# ( https://github.com/puppetlabs/puppetlabs-registry ),
# with one key difference: HKEY_USERS are supported.
# This is the primary reason for creation of this module.
# Hopefully, if puppetlabs-registry gains support for HKEY_USERS, this module can be
# eliminated (see http://projects.puppetlabs.com/issues/14555).
#
# This type makes use of an external script and probably does not work correctly for
# parameters which can't be easily quoted (e.g. parameters containing a " character, or
# newline or null characters). This won't be fixed as long as we don't need such values.
#

define registry::value(
    $key,
    $value,
    $ensure = 'present',
    $data = '',
    $type = 'string'
) {
    $perl = "C:\\Strawberry\\perl\\bin\\perl.exe"
    $script = "C:\\qtqa\\bin\\qtqa-reg.pl"
    if ! defined(File[$script]) {
        file { $script:
            path => "C:\\qtqa\\bin\\qtqa-reg.pl",
            ensure => present,
            source => "puppet:///modules/registry/qtqa-reg.pl",
        }
    }

    Exec { require => File[$script] }

    # these types should match those understood by the puppetlabs-registry implementation,
    # for forward-compatibility
    $real_type = $type ? {
        'string' => 'REG_SZ',
        'array'  => 'REG_MULTI_SZ',
        'expand' => 'REG_EXPAND_SZ',
        'dword'  => 'REG_DWORD',
        'qword'  => 'REG_QWORD',
        'binary' => 'REG_BINARY',
        default  => $type
    }

    $path = "$key\\$value"

    $check_content_cmd = "$perl $script check -path \"$path\" -data \"$data\" -type \"$real_type\""
    $check_present_cmd = "$perl $script check -path \"$path\""
    $write_cmd = "$perl $script write -path \"$path\" -data \"$data\" -type \"$real_type\""
    $delete_cmd = "$perl $script delete -path \"$path\""

    case $ensure {
        'absent': {
            exec { $delete_cmd:
                onlyif => $check_present_cmd,
                logoutput => true,
            }
        }
        'present': {
            exec { $write_cmd:
                unless => $check_content_cmd,
                logoutput => true,
            }
        }
        default: {
            fail("invalid parameter ensure => $ensure, expected 'present' or 'absent'")
        }
    }
}
