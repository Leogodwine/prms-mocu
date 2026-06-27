@echo off
REM Server binds to 0.0.0.0 internally — DO NOT open http://0.0.0.0:8000 in your browser (invalid address).
REM Use http://127.0.0.1:8000 instead. Binding 0.0.0.0 lets ONLYOFFICE Docker reach Laravel.
echo.
echo  MoCU-PRMS + ONLYOFFICE
echo  ======================
echo.
echo  OPEN IN YOUR BROWSER (copy one of these):
echo    http://127.0.0.1:8000
echo    http://localhost:8000
echo.
echo  DO NOT use http://0.0.0.0:8000  ^(browsers cannot open that address^)
echo.
echo  ONLYOFFICE runs separately at: http://127.0.0.1:8080
echo.
cd /d "%~dp0"
php artisan serve --host=0.0.0.0 --port=8000
