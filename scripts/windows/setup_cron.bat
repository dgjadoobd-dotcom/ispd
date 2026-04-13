@echo off
echo Setting up ISP ERP Cron Jobs...

set PHP=C:\xampp\php\php.exe
set BASE=C:\xampp\htdocs\ispd

:: Daily invoice generation at midnight
schtasks /create /tn "ISP-Cron-Daily" /tr "\"%PHP%\" \"%BASE%\cron_automation.php\"" /sc DAILY /st 00:00 /ru SYSTEM /f
echo [OK] ISP-Cron-Daily (midnight)

:: Due reminders at 8am
schtasks /create /tn "ISP-Cron-DueReminders" /tr "\"%PHP%\" \"%BASE%\cron_automation.php\" due-reminders" /sc DAILY /st 08:00 /ru SYSTEM /f
echo [OK] ISP-Cron-DueReminders (8am)

:: Auto suspend every 6 hours
schtasks /create /tn "ISP-Cron-Suspend" /tr "\"%PHP%\" \"%BASE%\cron_automation.php\" suspend" /sc HOURLY /mo 6 /ru SYSTEM /f
echo [OK] ISP-Cron-Suspend (every 6h)

:: PipraPay processing every 15 minutes
schtasks /create /tn "ISP-Cron-PipraPay" /tr "\"%PHP%\" \"%BASE%\cron_selfhosted_piprapay.php\"" /sc MINUTE /mo 15 /ru SYSTEM /f
echo [OK] ISP-Cron-PipraPay (every 15min)

echo.
echo All cron jobs registered. Run as Administrator if any fail.
pause
