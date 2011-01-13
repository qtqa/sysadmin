Some random notes about Puppet on Windows.

NOTE: these are some instructions which worked at one point in time.
They probably became outdated almost as soon as they were written.
At time of writing, we do not have any puppet-on-Windows setups in regular use.


CREATING A PUPPET BINARY PACKAGE FOR WINDOWS
============================================

These instructions will create a zip file with a ready-to-go puppet
installation to be extracted under c:\puppet .

 - Install Ruby from http://rubyinstaller.org/ .
   Use Ruby 1.8.7, install to c:\puppet\Ruby187 .

 - In Ruby command prompt:
   gem install rake rspec mocha win32-process win32-dir rack

 - Download http://rubyforge.org/frs/download.php/61461/sys-admin-1.5.2-x86-mswin32-60.gem
   to some working directory (outside of c:\puppet)

 - In Ruby command prompt:
   gem install sys-admin-1.5.2-x86-mswin32-60.gem

 - git clone git://github.com/reductivelabs/facter.git

 - In Ruby command prompt:
   cd facter
   rake gem
   gem install pkg/facter-1.5.7.gem

 - Download the desired puppet version (e.g. http://puppetlabs.com/downloads/puppet/puppet-2.6.1rc1.tar.gz)
   and extract to some working directory (outside of c:\puppet)

 - In Ruby command prompt:
   cd puppet-2.6.1
   rake create_gem
   gem install pkg\puppet-2.6.1.gem

 - Manually create these directories:
    c:\puppet\var

 - If there are syntax errors about: `uninitialized constant Fcntl::F_SETFD (NameError)',
   then comment out the lines causing the errors.

