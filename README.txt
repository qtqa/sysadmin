This repository contains metadata for the administration of test
infrastructure used by the Qt team.

If you are only interested in setting up a network test server,
look at README.network_test_server


HOW TO USE THIS REPOSITORY
==========================

We use a free open source tool called `puppet' to administer test machines.
The primary content of this repository is configuration files written in
puppet's domain-specific language.

The basic setup on each test machine is like this:

 - the test machine has a copy of this repo (usually at /var/qtqa/sysadmin,
   or c:\qtqa\sysadmin on Windows)

 - a script is run by a cron job (unix) / scheduled task (Windows) on the
   test machine which updates the repo and runs puppet (in one-shot mode,
   not as a daemon)

Getting the machine up and running to this point requires that at least git
and puppet are installed, and a clone of this repo has been done.
The steps to do this have been automated for a few different
types of systems - check the `bootstrap' directory for scripts.

The bootstrap process should be straightforward enough that the scripts can
be followed "by hand" if necessary.

So, basically, if you are using one of the OS types which is supported under
`bootstrap', then you need to (1) get the bootstrap script onto the machine
somehow, and (2) run it.  If you are using some other OS type, read one
of the bootstrap scripts and follow the basic high-level steps (and maybe
write up your own bootstrap script as you go :-)

During the bootstrap process, you'll be prompted about the type of host you
are trying to set up and asked to provide some additional information.
Here is an example interactive configuration session:

    Select which of the following best describes the purpose of this host:

      (1) network_test_server - Network test server, used for some QtNetwork autotests (qt-test-server.qt-test-net)
      (2) ci_server - Qt Project CI system server (Jenkins <-> Gerrit integration)
      (3) ci_tester - Qt Project CI tester, performing Qt compilation and autotests

    ? 3
    Configuring a node of class 'ci_tester'

    Username of the account used for all testing;
    this should be an account which is not used for any other
    activities on this machine.
      testuser [qt] ?

    Use icecream distributed compilation tool?
      icecc_enabled [y] ?

      Icecream scheduler hostname; leave empty for autodiscovery,
      which usually works if the scheduler is on the same LAN and IP broadcast
      is working.
        icecc_scheduler_host [] ? icecream-scheduler.example.com


    Configuration completed:

      classes:
        ci_tester:
          icecc_enabled: true
          icecc_scheduler_host: icecream-scheduler.example.com
          testuser: qt

    Save (y/n) [y] ?
    Configuration saved to nodecfg/10-config.yaml.

The interactive configuration can be repeated if necessary by running
'nodecfg.pl -interactive'. The output configuration file can be copied or
shared with other hosts.  See nodecfg/README.txt for more information on
the configuration file(s).


OTHER NOTES
===========

To temporarily disable the usage of puppet, touch:

  /var/qtqa/sysadmin/puppet/disable_puppet
  c:\qtqa\sysadmin\puppet\disable_puppet

Using this repository in conjuction with a puppet server probably won't work
due to our usage of the `generate' puppet function in a few places.

On Unix, puppet will log to the system log.

On Windows, puppet will log to c:\qtqa\sysadmin\puppet\last_puppet_run.txt,
but this is considered a FIXME until some better solution is implemented.
