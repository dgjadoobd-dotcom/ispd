#!/bin/bash

# Development Deployment Script for RADIUS Service
# This script sets up the development environment

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}Setting up development environment...${NC}"

# Check if .env file exists, if not copy from .env.development
if [ ! -f .env ]; then
    if [ -f .env.development ]; then
        echo -e "${YELLOW}Copying .env.development to .env...${NC}"
        cp .env.development .env
        echo -e "${GREEN}Created .env file from .env.development${NC}"
    else
        echo -e "${RED}Error: .env.development not found${NC}"
        exit 1
    fi
fi

# Load environment variables
source .env

echo -e "${GREEN}Building Docker images for development...${NC}"
docker-compose -f docker-compose.dev.yml build

echo -e "${GREEN}Starting development services...${NC}"
docker-compose -f docker-compose.dev.yml up -d

echo -e "${GREEN}Waiting for services to start...${NC}"
sleep 15

echo -e "${GREEN}Running database migrations...${NC}"
docker-compose -f docker-compose.dev.yml exec app php artisan migrate --force

echo -e "${GREEN}Seeding database with test data...${NC}"
docker-compose -f docker-compose.dev.yml exec app php artisan db:seed

echo -e "${GREEN}Clearing cache...${NC}"
docker-compose -f docker-compose.dev.yml exec app php artisan cache:clear
docker-compose -f docker-compose.dev.yml exec app php artisan config:clear
docker-compose -f docker-compose.dev.yml exec app php artisan route:clear
docker-compose -f docker-compose.dev.yml exec app php artisan view:clear

echo -e "${GREEN}Setting permissions...${NC}"
docker-compose -f docker-compose.dev.yml exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose -f docker-compose.dev.yml exec app chmod -R 775 storage bootstrap/cache

echo -e "${GREEN}Checking service health...${NC}"
sleep 5

# Check if services are running
if curl -f http://localhost:8080/health 2>/dev/null; then
    echo -e "${GREEN}✓ Health check passed${NC}"
else
    echo -e "${YELLOW}⚠ Health check failed, but continuing...${NC}"
fi

echo -e "${GREEN}Development environment setup completed!${NC}"
echo -e "${GREEN}Services are now running on:${NC}"
echo -e "  Application: http://localhost:8080"
echo -e "  Database: localhost:3306"
echo -e "  Redis: localhost:6379"
echo -e "  phpMyAdmin: http://localhost:8081"
echo -e "  MailHog UI: http://localhost:8025"

# Display running containers
echo -e "\n${GREEN}Running containers:${NC}"
docker-compose -f docker-compose.dev.yml ps

echo -e "\n${GREEN}Next steps:${NC}"
echo -e "1. Access the application at http://localhost:8080"
echo -e "2. Check logs: docker-compose -f docker-compose.dev.yml logs -f"
echo -e "3. Stop services: docker-compose -f docker-compose.dev.yml down"
echo -e "4. Rebuild: docker-compose -f docker-compose.dev.yml up --build"