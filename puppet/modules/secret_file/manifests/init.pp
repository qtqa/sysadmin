# Fetch a file which is too secret to be put under any source control.
# Note: if you only have access to the public version of this module,
# then your best bet is to manually install all secret_files.
define secret_file($source) {
    warning("secret file `$source' is required but I have no way to get it.  Try manually installing an appropriate file at $name")
}

