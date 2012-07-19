# selftest::expect is a test helper used from tests/*.pp files
# to encode some expected output from puppet --noop.
#
# t/20-puppet-tests.t in this repository parses the output and uses
# it when testing.
#
# The $output parameter is a pattern or an array of patterns which are expected
# to appear in the output from 'puppet --noop' on the file in which selftest::expect is called.
# If an array is passed, the patterns must match in the order given in the array.

define selftest::expect($output) {

    # $output may be an array, in which case we expect all of the array elements to appear
    # in the specified order; or just a single expression.
    # Note the usage of (?s:) is to allow ".*" to match across line boundaries.
    $pattern = inline_template( '<%= (output.instance_of? Array) ? output.join("(?s:.*)") : output %>' )

    # we use : as name delimiter, so strip any : from the name
    $test_name = inline_template( '<%= name.gsub(":", " ") %>' )

    notice( "test-expect: $test_name: $pattern" )
}
