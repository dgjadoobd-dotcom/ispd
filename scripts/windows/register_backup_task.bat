@echo off
setlocal

set "TASK_NAME=ISPD Auto Backup"
set "RUNNER=C:\xampp\htdocs\ispd\scripts\windows\run_backup.bat"

schtasks /Create ^
  /TN "%TASK_NAME%" ^
  /TR "\"%RUNNER%\"" ^
  /SC DAILY ^
  /ST 02:00 ^
  /RL HIGHEST ^
  /F

if errorlevel 1 (
  echo Failed to create scheduled task.
  exit /b 1
)

echo Task created: %TASK_NAME%
echo Runs daily at 02:00 AM
echo Verify with: schtasks /Query /TN "%TASK_NAME%" /V /FO LIST
exit /b 0
