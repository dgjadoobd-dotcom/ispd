@echo off
setlocal

set "PHP_EXE=C:\xampp\php\php.exe"
set "PROJECT_DIR=C:\xampp\htdocs\ispd"
set "LOG_DIR=%PROJECT_DIR%\storage\logs"
set "LOG_FILE=%LOG_DIR%\backup.log"

if not exist "%PHP_EXE%" (
  echo [ERROR] PHP not found: %PHP_EXE%
  exit /b 1
)

if not exist "%LOG_DIR%" mkdir "%LOG_DIR%"

cd /d "%PROJECT_DIR%"

echo [%date% %time%] Starting backup >> "%LOG_FILE%"
"%PHP_EXE%" "%PROJECT_DIR%\scripts\backup.php" %1 >> "%LOG_FILE%" 2>&1
set "ERR=%ERRORLEVEL%"

if not "%ERR%"=="0" (
  echo [%date% %time%] [ERROR] Backup failed, exit code: %ERR% >> "%LOG_FILE%"
  exit /b %ERR%
)

echo [%date% %time%] Backup completed >> "%LOG_FILE%"
exit /b 0
