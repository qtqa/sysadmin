class mesa3d {
    case $::operatingsystem {
        Windows:    { include mesa3d::windows }
    }
}