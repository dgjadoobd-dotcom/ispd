#!/bin/bash

# Production Deployment Script for RADIUS Service
# This script deploys the RADIUS service to production

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}Starting production deployment...${NC}"

# Load environment variables
if [ -f .env.production ]; then
    source .env.production
    echo "Loaded production environment variables"
else
    echo -e "${YELLOW}Warning: .env.production not found, using .env${NC}"
    if [ -f .env ]; then
        source .env
    fi
fi

# Check for required environment variables
if [ -z "$DB_PASSWORD" ] || [ -z "$REDIS_PASSWORD" ] || [ -z "$APP_KEY" ]; then
    echo -e "${RED}Error: Required environment variables not set${NC}"
    exit 1
fi

echo -e "${GREEN}Building Docker images...${NC}"
docker-compose -f docker-compose.prod.yml build

echo -e "${GREEN}Stopping existing containers...${NC}"
docker-compose -f docker-compose.prod.yml down

echo -e "${GREEN}Starting services...${NC}"
docker-compose -f docker-compose.prod.yml up -d

echo -e "${GREEN}Running database migrations...${NC}"
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force

echo -e "${GREEN}Clearing cache...${NC}"
docker-compose -f docker-compose.prod.yml exec app php artisan cache:clear
docker-compose -f docker-compose.prod.yml exec app php artisan config:cache
docker-compose -f docker-compose.prod.yml exec app php artisan route:cache
docker-compose -f docker-compose.prod.yml exec app php artisan view:cache

echo -e "${GREEN}Setting permissions...${NC}"
docker-compose -f docker-compose.prod.yml exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose -f docker-compose.prod.yml exec app chmod -R 775 storage bootstrap/cache

echo -e "${GREEN}Restarting services...${NC}"
docker-compose -f docker-compose.prod.yml restart

echo -e "${GREEN}Checking service health...${NC}"
sleep 10

# Check if services are running
if curl -f http://localhost/health > /dev/null 2>&1; then
    echo -e "${GREEN}✓ Health check passed${NC}"
else
    echo -e "${YELLOW}⚠  Health check failed, but continuing...${NC}"
fi

echo -e "${GREEN}Deployment completed!${NC}"
echo -e "${GREEN}Services are now running on:${NC}"
echo -e "  Application: http://localhost"
echo -e "  Database: localhost:3306"
echo -e "  Redis: localhost:6379"
echo -e "  phpMyAdmin: http://localhost:8081"

# Display running containers
echo -e "\n${GREEN}Running containers:${NC}"
docker-compose -f docker-compose.prod.yml ps