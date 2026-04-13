#!/bin/bash

# Staging Deployment Script for RADIUS Service
# This script deploys the RADIUS service to staging environment

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}Starting staging deployment...${NC}"

# Load environment variables
if [ -f .env.staging ]; then
    source .env.staging
    echo "Loaded staging environment variables"
else
    echo -e "${YELLOW}Warning: .env.staging not found, using .env${NC}"
    if [ -f .env ]; then
        source .env
    fi
fi

# Check for required environment variables
if [ -z "$DB_PASSWORD" ] || [ -z "$REDIS_PASSWORD" ]; then
    echo -e "${YELLOW}Warning: Some environment variables may not be set${NC}"
fi

echo -e "${GREEN}Building Docker images for staging...${NC}"
docker-compose -f docker-compose.staging.yml build

echo -e "${GREEN}Stopping existing containers...${NC}"
docker-compose -f docker-compose.staging.yml down

echo -e "${GREEN}Starting staging services...${NC}"
docker-compose -f docker-compose.staging.yml up -d

echo -e "${GREEN}Running database migrations...${NC}"
docker-compose -f docker-compose.staging.yml exec app php artisan migrate --force

echo -e "${GREEN}Clearing cache...${NC}"
docker-compose -f docker-compose.staging.yml exec app php artisan cache:clear
docker-compose -f docker-compose.staging.yml exec app php artisan config:cache
docker-compose -f docker-compose.staging.yml exec app php artisan route:cache
docker-compose -f docker-compose.staging.yml exec app php artisan view:cache

echo -e "${GREEN}Setting permissions...${NC}"
docker-compose -f docker-compose.staging.yml exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose -f docker-compose.staging.yml exec app chmod -R 775 storage bootstrap/cache

echo -e "${GREEN}Restarting services...${NC}"
docker-compose -f docker-compose.staging.yml restart

echo -e "${GREEN}Checking service health...${NC}"
sleep 10

# Check if services are running
if curl -f http://localhost:8082/health 2>/dev/null; then
    echo -e "${GREEN}✓ Health check passed${NC}"
else
    echo -e "${YELLOW}⚠ Health check failed, but continuing...${NC}"
fi

echo -e "${GREEN}Staging deployment completed!${NC}"
echo -e "${GREEN}Services are now running on:${NC}"
echo -e "  Application: http://localhost:8082"
echo -e "  Database: localhost:3307"
echo -e "  Redis: localhost:6380"
echo -e "  phpMyAdmin: http://localhost:8084"

# Display running containers
echo -e "\n${GREEN}Running containers:${NC}"
docker-compose -f docker-compose.staging.yml ps