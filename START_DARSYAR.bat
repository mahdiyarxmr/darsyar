@echo off
setlocal EnableExtensions

cd /d "%~dp0"
title DarsYar Local Server

echo ========================================================
echo   DarsYar School Management Platform - Local Launcher
echo ========================================================
echo.

set "XAMPP_DIR=P:\xampp"
set "PHP_BIN=%XAMPP_DIR%\php\php.exe"
set "MYSQLD_BIN=%XAMPP_DIR%\mysql\bin\mysqld.exe"
set "MYSQL_INI=%XAMPP_DIR%\mysql\bin\my.ini"

if not exist "%PHP_BIN%" (
    for %%P in (php.exe) do set "PHP_BIN=%%~$PATH:P"
)
if not exist "%PHP_BIN%" (
    echo [ERROR] PHP binary not found at P:\xampp\php\php.exe or in PATH.
    exit /b 1
)

echo [1/4] Checking MariaDB / MySQL service on port 3306...
netstat -ano | findstr ":3306 " | findstr "LISTENING" >nul 2>&1
if errorlevel 1 goto start_mysql
echo       MariaDB is already running on port 3306.
goto check_db

:start_mysql
if not exist "%MYSQLD_BIN%" (
    echo [ERROR] MySQL binary not found at "%MYSQLD_BIN%". Start XAMPP MySQL manually.
    exit /b 1
)
echo       Starting XAMPP MariaDB in background...
start "DarsYar MariaDB" /MIN "%MYSQLD_BIN%" --defaults-file="%MYSQL_INI%" --standalone
set /a _WAIT=0

:wait_mysql
set /a _WAIT+=1
timeout /t 1 /nobreak >nul
netstat -ano | findstr ":3306 " | findstr "LISTENING" >nul 2>&1
if not errorlevel 1 goto mysql_ready
if %_WAIT% LSS 15 goto wait_mysql
echo [ERROR] MariaDB failed to listen on port 3306 within 15 seconds.
exit /b 1

:mysql_ready
echo       MariaDB is now running on port 3306.

:check_db
echo [2/4] Verifying database connection and pending migrations...
"%PHP_BIN%" "%~dp0bin\migrate.php"
if errorlevel 1 (
    echo [ERROR] Database connection or migration check failed. Check .env configuration.
    exit /b 1
)

echo [3/4] Checking DarsYar HTTP server on 127.0.0.1:8000...
netstat -ano | findstr "127.0.0.1:8000" | findstr "LISTENING" >nul 2>&1
if errorlevel 1 goto start_php
echo       DarsYar server is already running at http://127.0.0.1:8000
if /I not "%~1"=="--no-browser" (
    echo       Opening login page in browser...
    start "" "http://127.0.0.1:8000/login"
)
exit /b 0

:start_php
echo [4/4] Starting PHP built-in server at http://127.0.0.1:8000 ...
echo       Document Root : "%~dp0public"
echo       URL           : http://127.0.0.1:8000/login
echo       Stop Script   : STOP_DARSYAR.bat or Ctrl+C
echo.

if /I not "%~1"=="--no-browser" (
    start "" /B cmd /c "timeout /t 2 /nobreak >nul & start "" "http://127.0.0.1:8000/login""
)

"%PHP_BIN%" -S 127.0.0.1:8000 -t "%~dp0public"
