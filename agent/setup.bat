@echo off
echo ============================================
echo  FCNCHBD ISP Agent - Setup
echo ============================================
echo.

echo [1/4] Installing Python dependencies...
pip install -r requirements.txt
if errorlevel 1 ( echo ERROR: pip failed & pause & exit /b 1 )

echo.
echo [2/4] Installing Node.js dependencies...
npm install
if errorlevel 1 ( echo ERROR: npm failed & pause & exit /b 1 )

echo.
echo [3/4] Creating folders...
mkdir reports 2>nul
mkdir reports\backups 2>nul
mkdir logs 2>nul

echo.
echo [4/4] Setup complete!
echo.
echo ============================================
echo  HOW TO RUN:
echo ============================================
echo.
echo  1. Start WhatsApp Bot (scan QR once):
echo     node whatsapp_bot.js
echo.
echo  2. Start Daily Agent (in new window):
echo     python daily_agent.py
echo.
echo  3. Run a task manually:
echo     python daily_agent.py morning
echo     python daily_agent.py due
echo     python daily_agent.py evening
echo.
echo ============================================
pause
