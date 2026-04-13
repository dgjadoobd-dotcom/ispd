<?php
/**
 * RADIUS Service Enhancement - Development Setup Script
 * 
 * This script sets up a development environment for the RADIUS service enhancement project.
 * It creates necessary configuration files and directories for development.
 */

echo "=========================================\n";
echo "RADIUS Service Enhancement - Dev Setup\n";
echo "=========================================\n\n";

// Define paths
$basePath = dirname(__DIR__);
$envFile = $basePath . '/.env';
$envExampleFile = $basePath . '/.env.example';

// Check if .env exists
if (!file_exists($envFile)) {
    echo "Creating .env file from example...\n";
    
    if (file_exists($envExampleFile)) {
        copy($envExampleFile, $envFile);
        echo "✓ Created .env file from .env.example\n";
    } else {
        // Create a basic .env file
        $envContent = <<<ENV
# Development Environment Configuration
APP_NAME="Digital ISP ERP (Development)"
APP_URL=http://localhost:8080
APP_ENV=development
APP_DEBUG=true
APP_TIMEZONE=Asia/Dhaka
APP_KEY=base64:ISPDigitalERPSecretKey2024Bangladesh

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=radius_development
DB_USERNAME=radius_dev
DB_PASSWORD=dev_password

# RADIUS Database Configuration
RADIUS_DB_CONNECTION=mysql
RADIUS_DB_HOST=localhost
RADIUS_DB_PORT=3306
RADIUS_DB_DATABASE=radius
RADIUS_DB_USERNAME=radius
RADIUS_DB_PASSWORD=radius123

# JWT Configuration
JWT_SECRET=DigitalispJWTSecret2024BD
JWT_EXPIRY=86400

# SMS Configuration
SMS_GATEWAY=sslwireless
SMS_API_KEY=your_sms_api_key_here
SMS_SENDER_ID=DIGITALISP

# MikroTik Configuration
MIKROTIK_TIMEOUT=10
RADIUS_HOST=172.17.50.10
RADIUS_PORT=1812

# Mail Configuration
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM=noreply@digitalisp.xyz

# Development Features
DEVELOPMENT_MODE=true
API_DOCS_ENABLED=true
ENV;
        
        file_put_contents($envFile, $envContent);
        echo "✓ Created basic .env file\n";
    }
} else {
    echo "✓ .env file already exists\n";
}

// Create development directories
$directories = [
    'logs',
    'logs/php',
    'logs/nginx',
    'logs/mysql',
    'tmp',
    'tmp/cache',
    'tmp/sessions',
    'tmp/uploads',
    'docker/mysql/initdb',
    'docker/nginx/conf.d',
    'docker/php',
];

foreach ($directories as $dir) {
    $fullPath = $basePath . '/' . $dir;
    if (!file_exists($fullPath)) {
        mkdir($fullPath, 0755, true);
        echo "✓ Created directory: $dir\n";
    }
}

// Create development configuration files

// 1. Create MySQL initialization script
$mysqlInitScript = <<<'SQL'
-- Development Database Initialization Script
-- For RADIUS Service Enhancement Project

-- Create development database
CREATE DATABASE IF NOT EXISTS radius_development;
CREATE DATABASE IF NOT EXISTS radius;

-- Create development user
CREATE USER IF NOT EXISTS 'radius_dev'@'%' IDENTIFIED BY 'dev_password';
GRANT ALL PRIVILEGES ON radius_development.* TO 'radius_dev'@'%';
GRANT ALL PRIVILEGES ON radius.* TO 'radius_dev'@'%';
FLUSH PRIVILEGES;

-- Use radius database
USE radius;

-- Create RADIUS tables if they don't exist
CREATE TABLE IF NOT EXISTS radcheck (
    id int(11) unsigned NOT NULL AUTO_INCREMENT,
    username varchar(64) NOT NULL DEFAULT '',
    attribute varchar(64) NOT NULL DEFAULT '',
    op char(2) NOT NULL DEFAULT '==',
    value varchar(253) NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    KEY username (username(32))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS radreply (
    id int(11) unsigned NOT NULL AUTO_INCREMENT,
    username varchar(64) NOT NULL DEFAULT '',
    attribute varchar(64) NOT NULL DEFAULT '',
    op char(2) NOT NULL DEFAULT '=',
    value varchar(253) NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    KEY username (username(32))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS radusergroup (
    id int(11) unsigned NOT NULL AUTO_INCREMENT,
    username varchar(64) NOT NULL DEFAULT '',
    groupname varchar(64) NOT NULL DEFAULT '',
    priority int(11) NOT NULL DEFAULT '1',
    PRIMARY KEY (id),
    KEY username (username(32))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS radacct (
    radacctid bigint(21) NOT NULL AUTO_INCREMENT,
    acctsessionid varchar(64) NOT NULL DEFAULT '',
    acctuniqueid varchar(32) NOT NULL DEFAULT '',
    username varchar(64) NOT NULL DEFAULT '',
    groupname varchar(64) NOT NULL DEFAULT '',
    realm varchar(64) DEFAULT '',
    nasipaddress varchar(15) NOT NULL DEFAULT '',
    nasportid varchar(32) DEFAULT NULL,
    nasporttype varchar(32) DEFAULT NULL,
    acctstarttime datetime DEFAULT NULL,
    acctupdatetime datetime DEFAULT NULL,
    acctstoptime datetime DEFAULT NULL,
    acctinterval int(12) DEFAULT NULL,
    acctsessiontime int(12) unsigned DEFAULT NULL,
    acctauthentic varchar(32) DEFAULT NULL,
    connectinfo_start varchar(50) DEFAULT NULL,
    connectinfo_stop varchar(50) DEFAULT NULL,
    acctinputoctets bigint(20) DEFAULT NULL,
    acctoutputoctets bigint(20) DEFAULT NULL,
    calledstationid varchar(50) DEFAULT NULL,
    callingstationid varchar(50) DEFAULT NULL,
    acctterminatecause varchar(32) DEFAULT NULL,
    servicetype varchar(32) DEFAULT NULL,
    framedprotocol varchar(32) DEFAULT NULL,
    framedipaddress varchar(15) DEFAULT NULL,
    PRIMARY KEY (radacctid),
    UNIQUE KEY acctuniqueid (acctuniqueid),
    KEY username (username),
    KEY framedipaddress (framedipaddress),
    KEY acctsessionid (acctsessionid),
    KEY acctsessiontime (acctsessiontime),
    KEY acctstarttime (acctstarttime),
    KEY acctinterval (acctinterval),
    KEY acctstoptime (acctstoptime),
    KEY nasipaddress (nasipaddress)
) ENGINE=InnoDB;

-- Create test data
INSERT IGNORE INTO radcheck (username, attribute, op, value) VALUES
('testuser1', 'Cleartext-Password', ':=', 'password123'),
('testuser2', 'Cleartext-Password', ':=', 'password456'),
('testuser3', 'Cleartext-Password', ':=', 'password789');

INSERT IGNORE INTO radusergroup (username, groupname, priority) VALUES
('testuser1', 'bronze', 1),
('testuser2', 'silver', 1),
('testuser3', 'gold', 1);

INSERT IGNORE INTO radreply (username, attribute, op, value) VALUES
('testuser1', 'Framed-IP-Address', '=', '192.168.1.100'),
('testuser2', 'Framed-IP-Address', '=', '192.168.1.101'),
('testuser3', 'Framed-IP-Address', '=', '192.168.1.102');

-- Create test accounting sessions
INSERT IGNORE INTO radacct (acctsessionid, acctuniqueid, username, groupname, nasipaddress, acctstarttime, acctsessiontime, acctinputoctets, acctoutputoctets) VALUES
('session001', 'unique001', 'testuser1', 'bronze', '192.168.1.1', NOW() - INTERVAL 1 HOUR, 3600, 1000000, 2000000),
('session002', 'unique002', 'testuser2', 'silver', '192.168.1.1', NOW() - INTERVAL 30 MINUTE, 1800, 2000000, 3000000),
('session003', 'unique003', 'testuser3', 'gold', '192.168.1.1', NOW(), 0, 0, 0);
SQL;

file_put_contents($basePath . '/docker/mysql/initdb/01-init.sql', $mysqlInitScript);
echo "✓ Created MySQL initialization script\n";

// 2. Create PHP configuration for development
$phpConfig = <<<'INI'
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
INI;

file_put_contents($basePath . '/docker/php/php.ini', $phpConfig);
echo "✓ Created PHP configuration\n";

// 3. Create nginx configuration
$nginxConfig = <<<'CONF'
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
}
CONF;

file_put_contents($basePath . '/docker/nginx/conf.d/app.conf', $nginxConfig);
echo "✓ Created nginx configuration\n";

// 4. Create development Dockerfile
$dockerfileDev = <<<'DOCKERFILE'
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
DOCKERFILE;

file_put_contents($basePath . '/Dockerfile.dev', $dockerfileDev);
echo "✓ Created development Dockerfile\n";

// 5. Create development docker-compose file
$dockerComposeDev = <<<'YAML'
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

networks:
  radius-network:
    driver: bridge
YAML;

file_put_contents($basePath . '/docker-compose.dev.yml', $dockerComposeDev);
echo "✓ Created development docker-compose file\n";

// 6. Create development helper script
$helperScript = <<<'BASH'
#!/bin/bash

# Development Helper Script for RADIUS Service Enhancement

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

print_status() {
    echo -e "${GREEN}[✓]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[!]${NC} $1"
}

print_error() {
    echo -e "${RED}[✗]${NC} $1"
}

case "$1" in
    start)
        echo "Starting development environment..."
        docker-compose -f docker-compose.dev.yml up -d
        echo ""
        echo "Services started:"
        echo "  Application: http://localhost:8080"
        echo "  phpMyAdmin:  http://localhost:8081"
        echo "  MySQL:       localhost:3306"
        echo ""
        echo "Default credentials:"
        echo "  phpMyAdmin: root / rootpassword"
        echo "  Test users:"
        echo "    testuser1 / password123 (Bronze)"
        echo "    testuser2 / password456 (Silver)"
        echo "    testuser3 / password789 (Gold)"
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
        docker-compose -f docker-compose.dev.yml exec mysql mysql -u root -prootpassword
        ;;
    status)
        echo "Container status:"
        docker-compose -f docker-compose.dev.yml ps
        ;;
    clean)
        echo "Cleaning up containers and volumes..."
        docker-compose -f docker-compose.dev.yml down -v
        ;;
    test-data)
        echo "Creating test data..."
        docker-compose -f docker-compose.dev.yml exec mysql mysql -u root -prootpassword radius << 'SQL'
-- Add more test users
INSERT IGNORE INTO radcheck (username, attribute, op, value) VALUES
('devuser1', 'Cleartext-Password', ':=', 'devpass1'),
('devuser2', 'Cleartext-Password', ':=', 'devpass2'),
('devuser3', 'Cleartext-Password', ':=', 'devpass3');

INSERT IGNORE INTO radusergroup (username, groupname, priority) VALUES
('devuser1', 'development', 1),
('devuser2', 'development', 1),
('devuser3', 'development', 1);

SELECT 'Test data created successfully!' as message;
SQL
        ;;
    *)
        echo "Usage: $0 {start|stop|restart|logs|build|shell|mysql|status|clean|test-data}"
        echo ""
        echo "Commands:"
        echo "  start      - Start development environment"
        echo "  stop       - Stop development environment"
        echo "  restart    - Restart development environment"
        echo "  logs       - Show logs"
        echo "  build      - Build containers"
        echo "  shell      - Open shell in app container"
        echo "  mysql      - Connect to MySQL"
        echo "  status     - Show container status"
        echo "  clean      - Clean up containers and volumes"
        echo "  test-data  - Create additional test data"
        exit 1
        ;;
esac
BASH;

file_put_contents($basePath . '/dev-helper.sh', $helperScript);
chmod($basePath . '/dev-helper.sh', 0755);
echo "✓ Created development helper script\n";

// 7. Create development README
$devReadme = <<<'MD'
# RADIUS Service Enhancement - Development Setup

## Quick Start

1. **Run the setup script:**
   ```bash
   php setup-dev.php
   ```

2. **Start the development environment:**
   ```bash
   chmod +x dev-helper.sh
   ./dev-helper.sh start
   ```

3. **Access the services:**
   - Application: http://localhost:8080
   - phpMyAdmin: http://localhost:8081 (root/rootpassword)
   - MySQL: localhost:3306

## Development Commands

Use the helper script for common tasks:

```bash
# Start services
./dev-helper.sh start

# Stop services
./dev-helper.sh stop

# View logs
./dev-helper.sh logs

# Open shell in app container
./dev-helper.sh shell

# Connect to MySQL
./dev-helper.sh mysql

# Check status
./dev-helper.sh status

# Clean up everything
./dev-helper.sh clean

# Create additional test data
./dev-helper.sh test-data
```

## Test Data

The development environment comes with pre-configured test data:

### Test Users
- **testuser1** / password123 (Bronze profile)
- **testuser2** / password456 (Silver profile)  
- **testuser3** / password789 (Gold profile)

### Static IPs
- testuser1: 192.168.1.100
- testuser2: 192.168.1.101
- testuser3: 192.168.1.102

### Test Sessions
- 3 active sessions for testing accounting functionality

## Project Structure

```
.
├── app/                    # Application code
│   ├── Controllers/       # PHP controllers
│   ├── Services/          # Business logic (including RadiusService.php)
│   └── Middleware/        # HTTP middleware
├── config/                # Configuration files
├── database/              # Database schemas
├── docker/                # Docker configuration
│   ├── mysql/initdb/     # MySQL initialization
│   ├── nginx/conf.d/     # Nginx configuration
│   └── php/              # PHP configuration
├── logs/                  # Application logs
├── public/                # Web root
└── views/                 # HTML templates
```

## Development Workflow

1. **Code changes** are automatically synced to the container
2. **PHP-FPM** auto-reloads on code changes
3. **Logs** are available in the `logs/` directory
4. **Database** is persisted in Docker volumes

## Testing the RADIUS Service

You can test the existing `RadiusService.php` functionality:

1. Access the application at http://localhost:8080
2. Test the RADIUS API endpoints
3. Check the database in phpMyAdmin at http://localhost:8081

## Environment Variables

Key environment variables in `.env`:

- `APP_ENV=development` - Development mode
- `APP_DEBUG=true` - Enable debug mode
- `DB_*` - Main database configuration
- `RADIUS_DB_*` - RADIUS database configuration

## Troubleshooting

### Services won't start
```bash
# Check Docker logs
./dev-helper.sh logs

# Rebuild containers
./dev-helper.sh clean
./dev-helper.sh build
./dev-helper.sh start
```

### Database connection issues
1. Check if MySQL is running: `./dev-helper.sh status`
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
MD;

file_put_contents($basePath . '/README-DEVELOPMENT.md', $devReadme);
echo "✓ Created development README\n";

echo "\n=========================================\n";
echo "Development Setup Complete!\n";
echo "=========================================\n\n";

echo "Next steps:\n";
echo "1. Make the helper script executable:\n";
echo "   chmod +x dev-helper.sh\n\n";
echo "2. Start the development environment:\n";
echo "   ./dev-helper.sh start\n\n";
echo "3. Access the application:\n";
echo "   http://localhost:8080\n\n";
echo "4. Check phpMyAdmin:\n";
echo "   http://localhost:8081 (root/rootpassword)\n\n";
echo "For more details, see README-DEVELOPMENT.md\n";
echo "=========================================\n";
?>