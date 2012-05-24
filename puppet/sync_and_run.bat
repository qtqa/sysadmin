@echo off
rem Poor man's logging.
rem Until we can investigate proper integration with some Windows log
rem service (if that makes sense at all), we just make sure the
rem last_puppet_run.txt file will hold the output from the most recent
rem puppet run.
set PUPPET_LOG=%~dp0last_puppet_run.txt
date /T >"%PUPPET_LOG%"
time /T >>"%PUPPET_LOG%"
perl "%~dp0sync_and_run.pl" >>"%PUPPET_LOG%" 2>&1
