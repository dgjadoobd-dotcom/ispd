# FCN ISP ERP - Pull & Update Script
# Run this on your Ubuntu server

#!/bin/bash
echo "========================================"
echo "FCN ISP ERP - Pulling Latest Code"
echo "========================================"

# Navigate to project directory
cd ~/pro/ispd 2>/dev/null || cd ~/pro/fcnchbd 2>/dev/null || cd ~/pro/ispd/fcnchbd 2>/dev/null || {
    echo "ERROR: Project folder not found!"
    echo "Expected: ~/pro/ispd or ~/pro/fcnchbd"
    exit 1
}

echo "Current folder: $(pwd)"
echo ""

# Pull latest from GitHub
echo "Pulling latest code..."
git pull origin main

# Check for new .env requirements
echo ""
echo "========================================"
echo "Verifying .env configuration..."
echo "========================================"

# Check if AI is enabled in .env
if grep -q "AI_ENABLED=true" .env 2>/dev/null; then
    echo "✓ AI enabled"
else
    echo "⚠ AI not enabled (optional)"
fi

# Check if Supabase is enabled
if grep -q "SUPABASE_ENABLED=true" .env 2>/dev/null; then
    echo "✓ Supabase enabled"
else
    echo "⚠ Supabase not enabled (optional)"
fi

echo ""
echo "========================================"
echo "Restarting PHP server..."
echo "========================================"

# Kill existing PHP server
pkill -f "php -S" 2>/dev/null

# Start PHP server on port 8081
php -S 10.10.10.42:8081 -t public &

sleep 2

# Verify server is running
if curl -s http://10.10.10.42:8081/ >/dev/null 2>&1; then
    echo "✓ Server running at http://10.10.10.42:8081"
else
    echo "⚠ Server may need manual restart"
    echo "Run: php -S 10.10.10.42:8081 -t public &"
fi

echo ""
echo "========================================"
echo "UPDATE COMPLETE!"
echo "========================================"
echo ""
echo "Access the application:"
echo "  Login: http://10.10.10.42:8081/login"
echo "  Admin: http://10.10.10.42:8081/admin"
echo ""
echo "Test AI: http://10.10.10.42:8081/ai/test"
echo "Test Routes: http://10.10.10.42:8081/dashboard"