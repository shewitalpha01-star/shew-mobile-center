@echo off
echo Fixing phpMyAdmin connection...

set FILE=C:\xampp\phpMyAdmin\config.inc.php

powershell -Command "(Get-Content '%FILE%') -replace \"'host'\] = 'localhost'\", \"'host'] = '127.0.0.1'\" | Set-Content '%FILE%'"

echo Done! Restarting MySQL...
net stop mysql
net start mysql

echo.
echo Fix applied! Open http://localhost/phpmyadmin
pause
