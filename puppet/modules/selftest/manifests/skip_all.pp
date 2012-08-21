# skip all (remaining) tests in the calling .pp file (including puppet exit code test)
define selftest::skip_all() {
    notice( "test-skip-all: $name" )
}
