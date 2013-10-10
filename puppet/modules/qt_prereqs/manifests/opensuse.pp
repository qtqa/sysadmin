class qt_prereqs::opensuse inherits qt_prereqs::unix {

    package {
        # for android:
        "java-1_7_0-openjdk-devel": ensure => installed;

        "gcc-c++": ensure => installed;
        "make": ensure => installed;

        # List from http://qt-project.org/wiki/Building-Qt-5-from-Git
        # libxcb
#        "xorg-x11-libxcb-devel":        ensure => installed; # does not exist
        "xcb-util-devel":               ensure => installed;
        "xcb-util-image-devel":         ensure => installed;
        "xcb-util-keysyms-devel":       ensure => installed;
        "xcb-util-renderutil-devel":    ensure => installed;
        "xcb-util-wm-devel":            ensure => installed;
        "xorg-x11-devel":               ensure => installed;
        "libxkbcommon-devel":           ensure => installed;
        # Qt WebKit
        "flex":                         ensure => installed;
        "bison":                        ensure => installed;
        "gperf":                        ensure => installed;
        "libicu-devel":                 ensure => installed;

        # DBus development libraries
        "dbus-1-devel":                 ensure => installed;
    }
}

