@echo off
setlocal

set "PHP_EXE=C:\xampp\php\php.exe"
set "PROJECT_DIR=C:\xampp\htdocs\ispd"
set "LOG_DIR=%PROJECT_DIR%\storage\logs"
set "LOG_FILE=%LOG_DIR%\selfhosted_piprapay_cron.log"

if not exist "%PHP_EXE%" (
  echo [ERROR] PHP executable not found: %PHP_EXE%
  exit /b 1
)

if not exist "%LOG_DIR%" mkdir "%LOG_DIR%"

cd /d "%PROJECT_DIR%"

echo [%date% %time%] Starting self-hosted PipraPay cron >> "%LOG_FILE%"
"%PHP_EXE%" "%PROJECT_DIR%\cron_selfhosted_piprapay.php" >> "%LOG_FILE%" 2>&1
set "ERR=%ERRORLEVEL%"
if not "%ERR%"=="0" (
  echo [%date% %time%] [ERROR] Exit code: %ERR% >> "%LOG_FILE%"
  exit /b %ERR%
)

echo [%date% %time%] Completed successfully >> "%LOG_FILE%"
exit /b 0
