#!/bin/bash
# =============================================================================
# FCN ISP ERP - AI Setup Script (Ollama)
# =============================================================================
# This script helps set up Ollama with Gemma 4 for local AI
# 
# Usage: sudo bash setup-ai.sh
# =============================================================================

set -euo pipefail

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  AI Setup for FCN ISP ERP (Ollama + Gemma 4)${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"

# Check if running as root
[[ $EUID -ne 0 ]] && echo -e "${RED}Run as root: sudo bash setup-ai.sh${NC}" && exit 1

# Install Ollama
echo -e "\n${YELLOW}Installing Ollama...${NC}"
if command -v ollama &> /dev/null; then
    echo -e "${GREEN}Ollama already installed${NC}"
    ollama --version
else
    curl -fsSL https://ollama.com/install.sh | sh
    echo -e "${GREEN}Ollama installed${NC}"
fi

# Pull Gemma 4 model
echo -e "\n${YELLOW}Installing Gemma 4 model...${NC}"
echo -e "${YELLOW}This may take a few minutes (download ~4GB)...${NC}"
ollama pull gemma4:latest

# Pull other recommended models
echo -e "\n${YELLOW}Installing additional models...${NC}"
ollama pull llama3.1 || true
ollama pull mistral || true

# List installed models
echo -e "\n${GREEN}Installed models:${NC}"
ollama list

# Create systemd service
echo -e "\n${YELLOW}Creating systemd service...${NC}"
cat > /etc/systemd/system/ollama.service << 'EOF'
[Unit]
Description=Ollama Service
After=network-online.target

[Service]
Type=simple
User=www-data
Group=www-data
ExecStart=/usr/local/bin/ollama serve
Environment=OLLAMA_HOST=0.0.0.0:11434
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable ollama
systemctl start ollama
systemctl status ollama --no-pager

# Test connection
echo -e "\n${YELLOW}Testing Ollama connection...${NC}"
sleep 2
curl -s http://localhost:11434/api/tags | grep -q "models" && echo -e "${GREEN}✓ Ollama is running!${NC}" || echo -e "${RED}✗ Ollama not responding${NC}"

echo -e "\n${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}AI Setup Complete!${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo ""
echo "Ollama URL: http://localhost:11434"
echo "API Endpoint: http://localhost:11434/api"
echo ""
echo "To test from FCN ISP ERP:"
echo "  1. Go to Settings → AI"
echo "  2. Set AI_ENABLED=true"
echo "  3. Set AI_BASE_URL=http://localhost:11434/api"
echo "  4. Set AI_MODEL=gemma4:latest"
echo "  5. Click Test AI"
echo ""