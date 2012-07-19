node default {
    $testuser = 'testuser'
    $testgroup = 'users'
    $qtgitreadonly = 'fakeqtgitreadonly'
    $qtgitreadonly_local = 'fakeqtgitreadonlylocal'
    $location = 'fakelocation'
}

class { 'baselayout': }
