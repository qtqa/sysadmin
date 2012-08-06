class testusers::ubuntu
{
    # create testgroup
    group { "testgroup":
        gid         => 1100,
        ensure      => present,
    }

    # create testuser1
    user { "testuser1":
        ensure      => present,
        uid         => 1101,
        gid         => "testgroup",
        comment     => "testuser1 created by puppet",
        home        => "/home/testuser1",
        managehome  => true,
        password    => 'testuser1',
        require     => Group["testgroup"],
    }

    # create testuser2
    user { "testuser2":
        ensure      => present,
        uid         => 1102,
        gid         => "testgroup",
        comment     => "testuser2 created by puppet",
        home        => "/home/testuser2",
        managehome  => true,
        password    => 'testuser2',
        require     => Group["testgroup"],
    }

    # Enables the $user (e.g. "qt") to run any command as testuser1
    # or testuser2 without using any password
    $user = $testusers::user
    file { "/etc/sudoers.d/testusers":
        ensure      =>  present,
        content     =>  template("testusers/testusers.erb"),
        mode        =>  0440,
        require     =>  Exec["Ensure sudoers.d is enabled"]
    }
}

