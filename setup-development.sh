#!/bin/bash

# RADIUS Service Enhancement - Development Setup
# Simple setup script for development environment

set -e

echo "========================================="
echo "RADIUS Service Enhancement - Dev Setup"
echo "========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[✓]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[!]${NC} $1"
}

print_error() {
    echo -e "${RED}[✗]${NC} $1"
}

# Check prerequisites
echo "Checking prerequisites..."

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    print_error "Docker is not installed. Please install Docker first."
    echo "Visit: https://docs.docker.com/get-docker/"
    exit 1
fi
print_status "Docker is installed"

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    print_warning "docker-compose is not installed. Trying to use docker compose..."
    if ! docker compose version &> /dev/null; then
        print_error "Neither docker-compose nor docker compose are available."
        echo "Please install docker-compose:"
        echo "  Linux: sudo apt-get install docker-compose"
        echo "  macOS: brew install docker-compose"
        exit 1
    fi
    DOCKER_COMPOSE_CMD="docker compose"
else
    DOCKER_COMPOSE_CMD="docker-compose"
fi
print_status "Docker Compose is available"

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    print_error "Docker is not running. Please start Docker Desktop or Docker Engine."
    exit 1
fi
print_status "Docker is running"

echo ""
echo "Setting up development environment..."
echo ""

# Create necessary directories
print_status "Creating necessary directories..."
mkdir -p docker/mysql/initdb
mkdir -p docker/nginx/conf.d
mkdir -p docker/php
mkdir -p logs/nginx
mkdir -p logs/php
mkdir -p logs/mysql

# Create development environment file
if [ ! -f .env ]; then
    print_status "Creating .env file for development..."
    cat > .env << 'EOF'
# Development Environment Configuration
APP_NAME="RADIUS Service Enhancement (Dev)"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8080

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=radius_development
DB_USERNAME=radius_dev
DB_PASSWORD=dev_password

# RADIUS Database Configuration
RADIUS_DB_CONNECTION=mysql
RADIUS_DB_HOST=mysql
RADIUS_DB_PORT=3306
RADIUS_DB_DATABASE=radius
RADIUS_DB_USERNAME=radius
RADIUS_DB_PASSWORD=radius123

# Redis Configuration
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=

# Mail Configuration (MailHog)
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=dev@localhost
MAIL_FROM_NAME="RADIUS Service Dev"

# Security (Development only)
APP_KEY=base64:dev-key-change-in-production
JWT_SECRET=dev-jwt-secret-change-in-production
EOF
else
    print_warning ".env file already exists. Please ensure it has development settings."
fi

# Create MySQL initialization script
print_status "Creating MySQL initialization script..."
cat > docker/mysql/initdb/01-init.sql << 'EOF'
-- Create development database
CREATE DATABASE IF NOT EXISTS radius_development;
CREATE DATABASE IF NOT EXISTS radius;

-- Create development user
CREATE USER IF NOT EXISTS 'radius_dev'@'%' IDENTIFIED BY 'dev_password';
GRANT ALL PRIVILEGES ON radius_development.* TO 'radius_dev'@'%';
GRANT ALL PRIVILEGES ON radius.* TO 'radius_dev'@'%';
FLUSH PRIVILEGES;

-- Import RADIUS schema
USE radius;
SOURCE /docker-entrypoint-initdb.d/radius_schema.sql;
EOF

# Copy RADIUS schema to MySQL init directory
if [ -f database/radius_schema.sql ]; then
    cp database/radius_schema.sql docker/mysql/initdb/radius_schema.sql
    print_status "Copied RADIUS schema to MySQL init directory"
else
    print_warning "RADIUS schema not found at database/radius_schema.sql"
fi

# Create nginx configuration
print_status "Creating nginx configuration..."
cat > docker/nginx/conf.d/app.conf << 'EOF'
server {
    listen 80;
    server_name localhost;
    root /var/www/html/public;
    index index.php index.html;

    access_log /var/log/nginx/access.log;
    error_log /var/log/nginx/error.log;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }

    location ~ /\.ht {
        deny all;
    }

    # API documentation
    location /api/docs {
        alias /var/www/html/public/docs;
        index index.html;
    }
}
EOF

# Create PHP configuration
print_status "Creating PHP configuration..."
cat > docker/php/php.ini << 'EOF'
[PHP]
memory_limit = 256M
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 300
max_input_time = 300
display_errors = On
display_startup_errors = On
error_reporting = E_ALL
log_errors = On
error_log = /var/log/php/error.log

[Date]
date.timezone = "Asia/Dhaka"

[opcache]
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
EOF

# Create a simple docker-compose.yml for development
print_status "Creating docker-compose.yml for development..."
cat > docker-compose.dev.yml << 'EOF'
version: '3.8'

services:
  # PHP Application
  app:
    build:
      context: .
      dockerfile: Dockerfile.dev
    container_name: radius-app-dev
    restart: unless-stopped
    working_dir: /var/www/html
    volumes:
      - .:/var/www/html
      - ./docker/php/php.ini:/usr/local/etc/php/php.ini
      - ./logs/php:/var/log/php
    depends_on:
      - mysql
      - redis
    environment:
      - APP_ENV=development
      - APP_DEBUG=true
    networks:
      - radius-network

  # Nginx
  nginx:
    image: nginx:alpine
    container_name: radius-nginx-dev
    restart: unless-stopped
    ports:
      - "8080:80"
    volumes:
      - .:/var/www/html
      - ./docker/nginx/conf.d:/etc/nginx/conf.d
      - ./logs/nginx:/var/log/nginx
    depends_on:
      - app
    networks:
      - radius-network

  # MySQL Database
  mysql:
    image: mysql:8.0
    container_name: radius-mysql-dev
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: rootpassword
      MYSQL_DATABASE: radius_development
      MYSQL_USER: radius_dev
      MYSQL_PASSWORD: dev_password
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql
      - ./docker/mysql/initdb:/docker-entrypoint-initdb.d
      - ./logs/mysql:/var/log/mysql
    networks:
      - radius-network

  # Redis
  redis:
    image: redis:7-alpine
    container_name: radius-redis-dev
    restart: unless-stopped
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data
    networks:
      - radius-network

  # MailHog for email testing
  mailhog:
    image: mailhog/mailhog
    container_name: radius-mailhog-dev
    restart: unless-stopped
    ports:
      - "8025:8025"  # Web UI
      - "1025:1025"   # SMTP
    networks:
      - radius-network

  # phpMyAdmin
  phpmyadmin:
    image: phpmyadmin/phpmyadmin
    container_name: radius-phpmyadmin-dev
    restart: unless-stopped
    environment:
      PMA_HOST: mysql
      PMA_PORT: 3306
      PMA_USER: root
      PMA_PASSWORD: rootpassword
    ports:
      - "8081:80"
    depends_on:
      - mysql
    networks:
      - radius-network

volumes:
  mysql_data:
  redis_data:

networks:
  radius-network:
    driver: bridge
EOF

# Create development Dockerfile
print_status "Creating development Dockerfile..."
cat > Dockerfile.dev << 'EOF'
FROM php:8.1-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libcurl4-openssl-dev \
    libssl-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && docker-php-ext-enable opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Create log directory
RUN mkdir -p /var/log/php && chown www-data:www-data /var/log/php

EXPOSE 9000

CMD ["php-fpm"]
EOF

# Create a development helper script
print_status "Creating development helper script..."
cat > dev-help.sh << 'EOF'
#!/bin/bash

# Development Helper Script
# Usage: ./dev-help.sh [command]

case "$1" in
    start)
        echo "Starting development environment..."
        docker-compose -f docker-compose.dev.yml up -d
        echo ""
        echo "Services started:"
        echo "  Application: http://localhost:8080"
        echo "  phpMyAdmin:  http://localhost:8081"
        echo "  MailHog:     http://localhost:8025"
        echo "  MySQL:       localhost:3306"
        echo "  Redis:       localhost:6379"
        ;;
    stop)
        echo "Stopping development environment..."
        docker-compose -f docker-compose.dev.yml down
        ;;
    restart)
        echo "Restarting development environment..."
        docker-compose -f docker-compose.dev.yml restart
        ;;
    logs)
        echo "Showing logs..."
        docker-compose -f docker-compose.dev.yml logs -f
        ;;
    build)
        echo "Building containers..."
        docker-compose -f docker-compose.dev.yml build
        ;;
    shell)
        echo "Opening shell in app container..."
        docker-compose -f docker-compose.dev.yml exec app bash
        ;;
    mysql)
        echo "Connecting to MySQL..."
        docker-compose -f docker-compose.dev.yml exec mysql mysql -u root -p
        ;;
    redis)
        echo "Connecting to Redis..."
        docker-compose -f docker-compose.dev.yml exec redis redis-cli
        ;;
    status)
        echo "Container status:"
        docker-compose -f docker-compose.dev.yml ps
        ;;
    clean)
        echo "Cleaning up containers and volumes..."
        docker-compose -f docker-compose.dev.yml down -v
        ;;
    *)
        echo "Usage: $0 {start|stop|restart|logs|build|shell|mysql|redis|status|clean}"
        echo ""
        echo "Commands:"
        echo "  start   - Start development environment"
        echo "  stop    - Stop development environment"
        echo "  restart - Restart development environment"
        echo "  logs    - Show logs"
        echo "  build   - Build containers"
        echo "  shell   - Open shell in app container"
        echo "  mysql   - Connect to MySQL"
        echo "  redis   - Connect to Redis"
        echo "  status  - Show container status"
        echo "  clean   - Clean up containers and volumes"
        exit 1
        ;;
esac
EOF

chmod +x dev-help.sh

# Create a test data script
print_status "Creating test data script..."
cat > create-test-data.sh << 'EOF'
#!/bin/bash

# Create test data for RADIUS service development

echo "Creating test data for RADIUS service..."

# Check if MySQL is running
if ! docker-compose -f docker-compose.dev.yml ps mysql | grep -q "Up"; then
    echo "MySQL is not running. Please start the development environment first."
    exit 1
fi

# Create test users in RADIUS database
echo "Creating test users in RADIUS database..."
docker-compose -f docker-compose.dev.yml exec mysql mysql -u root -prootpassword radius << 'SQL'
-- Create test users
INSERT INTO radcheck (username, attribute, op, value) VALUES
('testuser1', 'Cleartext-Password', ':=', 'password123'),
('testuser2', 'Cleartext-Password', ':=', 'password456'),
('testuser3', 'Cleartext-Password', ':=', 'password789');

-- Assign users to groups
INSERT INTO radusergroup (username, groupname, priority) VALUES
('testuser1', 'bronze', 1),
('testuser2', 'silver', 1),
('testuser3', 'gold', 1);

-- Add reply attributes (static IPs)
INSERT INTO radreply (username, attribute, op, value) VALUES
('testuser1', 'Framed-IP-Address', '=', '192.168.1.100'),
('testuser2', 'Framed-IP-Address', '=', '192.168.1.101'),
('testuser3', 'Framed-IP-Address', '=', '192.168.1.102');

-- Create group attributes (profiles)
INSERT INTO radgroupreply (groupname, attribute, op, value) VALUES
('bronze', 'Mikrotik-Rate-Limit', '=', '10M/5M'),
('silver', 'Mikrotik-Rate-Limit', '=', '20M/10M'),
('gold', 'Mikrotik-Rate-Limit', '=', '50M/25M');

-- Create some test accounting sessions
INSERT INTO radacct (acctsessionid, acctuniqueid, username, groupname, nasipaddress, acctstarttime, acctsessiontime, acctinputoctets, acctoutputoctets) VALUES
('session001', 'unique001', 'testuser1', 'bronze', '192.168.1.1', NOW() - INTERVAL 1 HOUR, 3600, 1000000, 2000000),
('session002', 'unique002', 'testuser2', 'silver', '192.168.1.1', NOW() - INTERVAL 30 MINUTE, 1800, 2000000, 3000000),
('session003', 'unique003', 'testuser3', 'gold', '192.168.1.1', NOW(), 0, 0, 0);

echo "Test data created successfully!";
echo "";
echo "Test users:";
echo "  testuser1 / password123 (Bronze profile)";
echo "  testuser2 / password456 (Silver profile)";
echo "  testuser3 / password789 (Gold profile)";
echo "";
echo "Active sessions created for testing.";
SQL

echo "Test data creation complete!"
EOF

chmod +x create-test-data.sh

# Create a simple README for development
print_status "Creating development README..."
cat > README-DEV.md << 'EOF'
# RADIUS Service Enhancement - Development Guide

## Quick Start

1. **Setup development environment:**
   ```bash
   chmod +x setup-development.sh
   ./setup-development.sh
   ```

2. **Start development services:**
   ```bash
   ./dev-help.sh start
   ```

3. **Access services:**
   - Application: http://localhost:8080
   - phpMyAdmin: http://localhost:8081 (root/rootpassword)
   - MailHog: http://localhost:8025
   - MySQL: localhost:3306
   - Redis: localhost:6379

## Development Commands

Use the helper script for common tasks:

```bash
# Start services
./dev-help.sh start

# Stop services
./dev-help.sh stop

# View logs
./dev-help.sh logs

# Open shell in app container
./dev-help.sh shell

# Connect to MySQL
./dev-help.sh mysql

# Connect to Redis
./dev-help.sh redis

# Check status
./dev-help.sh status

# Clean up everything
./dev-help.sh clean
```

## Creating Test Data

To create test RADIUS users and sessions:

```bash
./create-test-data.sh
```

This will create:
- 3 test users with different profiles (bronze, silver, gold)
- Static IP assignments
- Test accounting sessions

## Development Workflow

1. **Code changes** are automatically synced to the container
2. **PHP-FPM** auto-reloads on code changes
3. **Logs** are available in the `logs/` directory
4. **Database** is persisted in Docker volumes

## Project Structure

```
.
├── app/                    # Application code
│   ├── Controllers/       # PHP controllers
│   ├── Services/          # Business logic (including RadiusService.php)
│   └── Middleware/        # HTTP middleware
├── config/                # Configuration files
├── database/              # Database schemas and migrations
├── docker/                # Docker configuration
│   ├── mysql/initdb/     # MySQL initialization scripts
│   ├── nginx/conf.d/     # Nginx configuration
│   └── php/              # PHP configuration
��── logs/                  # Application logs
├── public/                # Web root
└── views/                 # HTML templates
```

## Testing

### Unit Tests
```bash
# Run from within the app container
docker-compose -f docker-compose.dev.yml exec app php vendor/bin/phpunit
```

### API Testing
Use tools like Postman or curl to test API endpoints:

```bash
# Example: Get active sessions
curl http://localhost:8080/api/v1/sessions

# Example: Create a user
curl -X POST http://localhost:8080/api/v1/users \
  -H "Content-Type: application/json" \
  -d '{"username": "newuser", "password": "test123", "profile": "bronze"}'
```

## Debugging

### PHP Errors
- Check `logs/php/error.log`
- Enable `APP_DEBUG=true` in `.env`

### Database
- Access phpMyAdmin at http://localhost:8081
- Use MySQL Workbench or other tools on port 3306

### Redis
- Use redis-cli: `./dev-help.sh redis`
- Monitor with RedisInsight or other tools

## Environment Variables

Key environment variables in `.env`:

- `APP_ENV=development` - Development mode
- `APP_DEBUG=true` - Enable debug mode
- `DB_*` - Database configuration
- `RADIUS_DB_*` - RADIUS database configuration
- `REDIS_*` - Redis configuration

## Troubleshooting

### Services won't start
```bash
# Check Docker logs
docker-compose -f docker-compose.dev.yml logs

# Rebuild containers
./dev-help.sh clean
./dev-help.sh build
./dev-help.sh start
```

### Database connection issues
1. Check if MySQL is running: `./dev-help.sh status`
2. Verify credentials in `.env`
3. Check MySQL logs: `tail -f logs/mysql/error.log`

### Application errors
1. Check PHP logs: `tail -f logs/php/error.log`
2. Check nginx logs: `tail -f logs/nginx/error.log`
3. Enable debug mode in `.env`

## Next Steps

After setting up the development environment:

1. Review the existing `RadiusService.php` in `app/Services/`
2. Check the requirements in `.kiro/specs/radius-service-enhancement/requirements.md`
3. Review the design in `.kiro/specs/radius-service-enhancement/design.md`
4. Start implementing the enhancement tasks
EOF

# Make scripts executable
chmod +x setup-development.sh
chmod +x dev-help.sh
chmod +x create-test-data.sh

echo ""
echo "========================================="
echo "Development Setup Complete!"
echo "========================================="
echo ""
echo "Next steps:"
echo ""
echo "1. Review the setup:"
echo "   cat README-DEV.md"
echo ""
echo "2. Start the development environment:"
echo "   ./dev-help.sh start"
echo ""
echo "3. Create test data:"
echo "   ./create-test-data.sh"
echo ""
echo "4. Access the application:"
echo "   http://localhost:8080"
echo ""
echo "5. Check phpMyAdmin:"
echo "   http://localhost:8081 (root/rootpassword)"
echo ""
echo "For more details, see README-DEV.md"
echo "========================================="