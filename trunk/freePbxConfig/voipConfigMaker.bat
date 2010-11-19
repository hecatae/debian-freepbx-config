@ECHO OFF
SET HERE=%~dp0
SET PHPBIN=%HERE%\..\PHP5\php.exe
SET PHPINI=%HERE%\..\PHP5\php-generator.ini
s
:start
cls
CD %HERE%
%PHPBIN% -c "%PHPINI%" voipConfigMaker.php
pause
REM goto start