class android {
    case $::operatingsystem {
        Ubuntu:     { include android::linux }
    }
}

