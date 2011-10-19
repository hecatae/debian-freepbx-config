@ECHO OFF
SET HERE=%~dp0
SET PHPBIN=%HERE%\..\PHP5\php.exe
SET PHPINI=%HERE%\..\PHP5\php-generator.ini
:start
cls
CD %HERE%
%PHPBIN% -c "%PHPINI%" voipConfigMaker.php
REM pause > NUL:
REM goto start