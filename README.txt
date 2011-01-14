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

 - the test machine has a copy of this repo (usually at /var/qtqa/sysadmin)

 - a script is run by a cron job on the test machine which updates the repo
   and runs puppet (in one-shot mode, not as a daemon)

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


OTHER NOTES
===========

To temporarily disable the usage of puppet, touch /var/qtqa/sysadmin/puppet/disable_puppet

Using this repository in conjuction with a puppet server probably won't work
due to our usage of the `generate' puppet function in a few places.
