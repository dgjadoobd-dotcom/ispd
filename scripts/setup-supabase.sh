#!/bin/bash
# =============================================================================
# FCN ISP ERP - Supabase Setup Script
# =============================================================================
# This script helps set up local Supabase for FCN ISP ERP
# 
# Usage: sudo bash setup-supabase.sh
# =============================================================================

set -euo pipefail

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  Supabase Setup for FCN ISP ERP${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"

# Check if running as root
[[ $EUID -ne 0 ]] && echo -e "${RED}Run as root: sudo bash setup-supabase.sh${NC}" && exit 1

# Check for Docker (required for Supabase)
if ! command -v docker &> /dev/null; then
    echo -e "${RED}Docker is required. Install first: https://docs.docker.com/get-docker/${NC}"
    exit 1
fi

# Check if Docker is running
if ! docker info &> /dev/null; then
    echo -e "${RED}Docker is not running. Start Docker first.${NC}"
    exit 1
fi

# Check if Supabase CLI is installed
if ! command -v supabase &> /dev/null; then
    echo -e "${YELLOW}Installing Supabase CLI...${NC}"
    
    # Install via package manager or curl
    if command -v apt &> /dev/null; then
        # Try installing from GitHub releases
        curl -L "https://github.com/supabase/cli/releases/download/v1.206.0/supabase_1.206.0_linux_amd64.tar.gz" -o /tmp/supabase.tar.gz
        tar -xzf /tmp/supabase.tar.gz -C /tmp
        mv /tmp/supabase /usr/local/bin/supabase
        chmod +x /usr/local/bin/supabase
        rm -f /tmp/supabase.tar.gz
    fi
fi

echo -e "${GREEN}Supabase CLI installed${NC}"

# Initialize Supabase in project directory
cd /home/kamrul/pro/ispd

echo -e "\n${YELLOW}Initializing Supabase...${NC}"
supabase init

echo -e "\n${YELLOW}Starting Supabase locally...${NC}"
supabase start

# Get Supabase credentials
echo -e "\n${YELLOW}Getting Supabase credentials...${NC}"
supabase status

echo -e "\n${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}Supabase Setup Complete!${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo ""
echo "Your Supabase URL: http://127.0.0.1:54321"
echo ""
echo "To configure FCN ISP ERP:"
echo "  1. Edit .env file"
echo "  2. Set SUPABASE_ENABLED=true"
echo "  3. Add SUPABASE_ANON_KEY from above"
echo "  4. Add SUPABASE_SERVICE_ROLE_KEY from above"
echo "  5. Test at /supabase/test"
echo ""