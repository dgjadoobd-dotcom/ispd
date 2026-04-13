#!/bin/bash

# Environment Configuration Test Script
# This script tests the environment configuration

echo "Testing Environment Configuration"
echo "================================"
echo ""

# Check if .env files exist
echo "1. Checking environment files..."
if [ -f ".env.development" ]; then
    echo "  ✓ .env.development exists"
else
    echo "  ✗ .env.development not found"
fi

if [ -f ".env.staging" ]; then
    echo "  ✓ .env.staging exists"
else
    echo "  ✗ .env.staging not found"
fi

if [ -f ".env.production" ]; then
    echo "  ✓ .env.production exists"
else
    echo "  ✗ .env.production not found"
fi

echo ""
echo "2. Checking Docker Compose files..."
if [ -f "docker-compose.dev.yml" ] || [ -f "docker-compose.yml" ]; then
    echo "  ✓ Docker Compose files exist"
else
    echo "  ✗ Docker Compose files not found"
fi

echo ""
echo "3. Checking Dockerfiles..."
if [ -f "Dockerfile" ]; then
    echo "  ✓ Dockerfile exists"
else
    echo "  ✗ Dockerfile not found"
fi

if [ -f "Dockerfile.prod" ] || [ -f "Dockerfile.production" ]; then
    echo "  ✓ Production Dockerfile exists"
else
    echo "  ✗ Production Dockerfile not found"
fi

echo ""
echo "4. Checking deployment scripts..."
if [ -f "deploy-dev.sh" ]; then
    echo "  ✓ deploy-dev.sh exists"
else
    echo "  ✗ deploy-dev.sh not found"
fi

if [ -f "deploy-staging.sh" ]; then
    echo "  ✓ deploy-staging.sh exists"
else
    echo "  ✗ deploy-staging.sh not found"
fi

if [ -f "deploy-prod.sh" ]; then
    echo "  ✓ deploy-prod.sh exists"
else
    echo "  ✗ deploy-prod.sh not found"
fi

echo ""
echo "5. Checking configuration files..."
if [ -d "docker" ]; then
    echo "  ✓ Docker configuration directory exists"
else
    echo "  ✗ Docker configuration directory not found"
fi

echo ""
echo "Environment configuration test completed."
echo "Check the output above for any missing files."