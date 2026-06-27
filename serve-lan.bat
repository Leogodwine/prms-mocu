@echo off
REM Same as serve.bat but also reachable from other devices on your WiFi.
echo.
echo  MoCU-PRMS — LAN mode
echo  ====================
echo.
echo  On THIS computer, open:
echo    http://127.0.0.1:8000
echo    http://localhost:8000
echo.
echo  From another device on the same WiFi, use your PC IP, e.g.:
echo    http://10.22.3.247:8000
echo.
echo  DO NOT use http://0.0.0.0:8000 in a browser.
echo.
cd /d "%~dp0"
php artisan serve --host=0.0.0.0 --port=8000
