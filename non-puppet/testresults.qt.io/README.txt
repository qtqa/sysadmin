This file contains static setup files for testresults.qt.io host.
Alternatively, if you are reading this README.txt file _on_ testresults.qt.io,
it was copied from the git://code.qt.io/qtqa/sysadmin repository.

DEPLOYMENT
==========

May be deployed by:

  scp -r ~/path/to/sysadmin/testresults.qt.io/* <username>@testresults.qt.io:/var/www/testresults

Nothing automatically enforces synchronization between the git repo and the web server,
so please take care to keep things synchronized yourself.
