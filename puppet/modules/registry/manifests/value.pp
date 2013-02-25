# Manage a registry value.
#
# This type is very similar to registry::value from the puppetlabs-registry module
# ( https://github.com/puppetlabs/puppetlabs-registry ).
# It supports two additional features which we need and which the above module doesn't
# support:
#
#  HKEY_USERS - http://projects.puppetlabs.com/issues/14555
#  mixed 32-bit/64-bit support - http://projects.puppetlabs.com/issues/16056
#
# Hopefully, if puppetlabs-registry gains support for the above features, this module
# can be eliminated.
#
# On 64-bit Windows, the 64-bit registry view is accessed by default.  This is appropriate
# for system-wide values or for values used by 64-bit applications.  Use 'view => 32' to
# access the 32-bit registry view instead.
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
    $type = 'string',
    $view = $::architecture ? {
        x64 => 64,
        default => ''
    }
) {
    include registry

    $perl = "C:\\utils\\strawberryperl_portable\\perl\\bin\\perl.exe"
    $script = $registry::script

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

    # whether we are in 32-bit, 64-bit or native node
    $view_args = $view ? {
        32      =>  '-view32',
        64      =>  '-view64',
        default =>  ''
    }

    $path = "$key\\$value"

    $check_content_cmd = "$perl $script check $view_args -path \"$path\" -data \"$data\" -type \"$real_type\""
    $check_present_cmd = "$perl $script check $view_args -path \"$path\""
    $write_cmd = "$perl $script write $view_args -path \"$path\" -data \"$data\" -type \"$real_type\""
    $delete_cmd = "$perl $script delete $view_args -path \"$path\""

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
