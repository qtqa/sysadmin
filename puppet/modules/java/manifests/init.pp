class java {
    case $::operatingsystem {
        Ubuntu:     { include java::ubuntu }
        Windows:    { include java::windows }
    }
}
