:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
::
:: Copyright (C) 2013 Digia Plc and/or its subsidiary(-ies).
:: Contact: http://www.qt-project.org/legal
::
:: This file is part of the Qt Toolkit.
::
:: $QT_BEGIN_LICENSE:LGPL$
:: Commercial License Usage
:: Licensees holding valid commercial Qt licenses may use this file in
:: accordance with the commercial license agreement provided with the
:: Software or, alternatively, in accordance with the terms contained in
:: a written agreement between you and Digia.  For licensing terms and
:: conditions see http://qt.digia.com/licensing.  For further information
:: use the contact form at http://qt.digia.com/contact-us.
::
:: GNU Lesser General Public License Usage
:: Alternatively, this file may be used under the terms of the GNU Lesser
:: General Public License version 2.1 as published by the Free Software
:: Foundation and appearing in the file LICENSE.LGPL included in the
:: packaging of this file.  Please review the following information to
:: ensure the GNU Lesser General Public License version 2.1 requirements
:: will be met: http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
::
:: In addition, as a special exception, Digia gives you certain additional
:: rights.  These rights are described in the Digia Qt LGPL Exception
:: version 1.1, included in the file LGPL_EXCEPTION.txt in this package.
::
:: GNU General Public License Usage
:: Alternatively, this file may be used under the terms of the GNU
:: General Public License version 3.0 as published by the Free Software
:: Foundation and appearing in the file LICENSE.GPL included in the
:: packaging of this file.  Please review the following information to
:: ensure the GNU General Public License version 3.0 requirements will be
:: met: http://www.gnu.org/copyleft/gpl.html.
::
::
:: $QT_END_LICENSE$
::
:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
@echo off
setlocal
set CURFILE=%0
if "%1" == "/?" goto help
if "%1" == "" goto help

:loop
if "%1" == "" goto endloop
set PARAM=%1
if "%PARAM:~0,3%" == "/g:" (set GITURL=%PARAM:~3%&shift&goto loop)
if "%PARAM:~0,3%" == "/d:" (set DLURL=%PARAM:~3%&shift&goto loop)
if "%PARAM:~0,3%" == "/p:" (set PUPPETURL=%PARAM:~3%&shift&goto loop)
echo Invalid syntax!&goto help
:endloop

if "%GITURL%" == "" goto help
if "%DLURL%" == "" set DLURL=http://ci-files01-hki.ci.local/input/
if "" neq "%PUPPETURL%" set PUPPETURL= --puppet-url %PUPPETURL%

set PORTABLEPERLVERSION=strawberry-perl-5.16.2.2-32bit-portable.zip

echo Downloading Portable Perl to c:\temp
powershell -ExecutionPolicy Unrestricted -command ".\'wintools\downloader.ps1' -source \"%DLURL%/windows/%PORTABLEPERLVERSION%\" -destination \"c:\temp\%PORTABLEPERLVERSION%\"; Exit $LASTEXITCODE" >c:\temp\windows_bootstrap-get_perl_portable.txt 2>&1
if NOT %ERRORLEVEL% == 0 goto :error_in_download_perl
del /q c:\temp\windows_bootstrap-get_perl_portable.txt
echo OK

echo Downloading Unzip to c:\utils\unzip
powershell -ExecutionPolicy Unrestricted -command ".\'wintools\downloader.ps1' -source \"%DLURL%/windows/unzip.exe\" -destination \"c:\utils\unzip\unzip.exe\"; Exit $LASTEXITCODE" >c:\temp\windows_bootstrap-get_unzip.txt 2>&1
if NOT %ERRORLEVEL% == 0 goto :error_in_download_unzip
del /q c:\temp\windows_bootstrap-get_unzip.txt
echo OK

echo Unzipping Portable Perl to c:\utils
c:\utils\unzip\unzip.exe -o c:\temp\%PORTABLEPERLVERSION% -d c:\utils\strawberryperl_portable >nul 2>c:\temp\Portable_Perl_unzip.txt
if NOT %ERRORLEVEL% == 0 goto :error_in_unzipping_portable_perl
del /q c:\temp\Portable_Perl_unzip.txt
echo OK

:: Setting Perl environment variables
set STRAWBERRYPERL_ROOT=c:\utils\strawberryperl_portable
set PERL5LIB=
set PERL_LOCAL_LIB_ROOT=
set PERL_MB_OPT=
set PERL_MM_OPT=
path=%STRAWBERRYPERL_ROOT%\c\bin;%STRAWBERRYPERL_ROOT%\perl\site\bin;%STRAWBERRYPERL_ROOT%\perl\bin;%PATH%

echo Run 'CPAN Win32::Shortcut'
call c:\utils\strawberryperl_portable\perl\bin\cpan.bat Win32::Shortcut >nul 2>c:\temp\cpan_shortcut_install.txt
if NOT %ERRORLEVEL% == 0 goto :error_in_cpan_shortcut_install
del /q c:\temp\cpan_shortcut_install.txt
echo OK

echo %STRAWBERRYPERL_ROOT%\perl\bin\perl.exe wintools\win_boostrap.pl%PUPPETURL% %GITURL%
%STRAWBERRYPERL_ROOT%\perl\bin\perl.exe wintools\win_bootstrap.pl%PUPPETURL% %GITURL%
if NOT %ERRORLEVEL% == 0 goto :error_in_bootstrap_pl
echo OK

goto end

:help
echo.
echo Usage: %CURFILE% /g:^<url^> [/d:^<url^>] [/p:^<url^>]
echo.
echo   /g:^<url^>       Define Git repository where puppet configuration is located.
echo                  E.g. git://qt.gitorious.org/qtqa/sysadmin.git
echo.
echo OPTIONAL:
echo   /d:^<url^>       Define download location where binaries are located.
echo                  If omitted 'http://ci-files01-hki.ci.local/input/' is used.
echo.
echo   /p:^<url^>       Pass on Puppet download location. This parameter is passed
echo                  on to 'win_boostrap.pl'.
echo                  If omitted 'win_Bootstrap.pl' uses it's default value.
echo.
echo EXAMPLE:
echo   %CURFILE% /g:git://qt.gitorious.org/qtqa/sysadmin.git /d:http://ci-files01-hki.ci.local/input/ /p:https://downloads.puppetlabs.com/windows/puppet-3.0.0rc2.msi
echo.
goto end

:error_in_download_perl
echo.
echo Error in getting Portable Perl
echo.
type c:\temp\windows_bootstrap-get_perl_portable.txt
goto end

:error_in_download_unzip
echo.
echo Error in getting Unzip
echo.
type c:\temp\windows_bootstrap-get_unzip.txt
goto end

:error_in_unzipping_portable_perl
echo.
echo Error in unzipping Portable Perl
echo.
type c:\temp\Portable_Perl_unzip.txt
goto end

:error_in_cpan_shortcut_install.txt
echo.
echo Error in installing Win32::Shortcut
echo.
type c:\temp\cpan_shortcut_install.txt
goto end

:error_in_bootstrap_pl
echo.
echo Error in running win_bootstrap.pl
echo.
goto end

:end
endlocal
