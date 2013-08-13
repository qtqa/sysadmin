class packaging_tester::windows inherits packaging_tester::base {
    include mesa3d
    include activepython
    include mingw
    include mingw48
    include openssl
    include strawberryperl
    include strawberryperl_portable
    include jom
    include dependencywalker
    include virtual_clone_drive
    include postgresql
    include mysql
}
