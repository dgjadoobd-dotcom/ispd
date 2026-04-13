#!/bin/bash

# Development Environment Setup Script
# For RADIUS Service Enhancement Project

set -e

echo "========================================="
echo "RADIUS Service Enhancement - Dev Setup"
echo "========================================="
echo ""

# Check if Docker and Docker Compose are installed
if ! command -v docker &> /dev/null; then
    echo "Docker is not installed. Please install Docker first."
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo "docker-compose is not installed. Please install docker-compose."
    exit 1
fi

echo "Setting up development environment..."
echo ""

# Create necessary directories
echo "Creating necessary directories..."
mkdir -p docker/mysql/initdb
mkdir -p docker/redis/data
mkdir -p docker/nginx/conf.d
mkdir -p docker/php
mkdir -p docker/mysql/initdb

echo "Creating development environment files..."

# Create a .env file for development
if [ ! -f .env ]; then
    echo "Creating .env file from development template..."
    cp .env.development .env
    echo ".env file created from development template."
else
    echo ".env file already exists. Skipping..."
fi

# Create docker-compose.override.yml for development
cat > docker-compose.override.yml << 'EOF'
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile.dev
    volumes:
      - .:/var/www/html
      - ./docker/php/php.ini:/usr/local/etc/php/php.ini
    environment:
      - XDEBUG_MODE=develop,debug
      - XDEBUG_SESSION=1
    extra_hosts:
      - "host.docker.internal:host-gateway"

  nginx:
    volumes:
      - ./docker/nginx/nginx.conf:/etc/nginx/nginx.conf
      - ./docker/nginx/conf.d:/etc/nginx/conf.d
      - ./docker/nginx/logs:/var/log/nginx
      - ./docker/nginx/html:/usr/share/nginx/html

  mysql:
    environment:
      MYSQL_ROOT_PASSWORD: rootpassword
      MYSQL_DATABASE: radius_development
      MYSQL_USER: radius_dev
      MYSQL_PASSWORD: dev_password
    volumes:
      - mysql_data:/var/lib/mysql
      - ./docker/mysql/initdb:/docker-entrypoint-initdb.d
    ports:
      - "3306:3306"

  redis:
    volumes:
      - redis_data:/data

  phpmyadmin:
    image: phpmyadmin/phpmyadmin
    environment:
      PMA_HOST: mysql
      PMA_PORT: 3306
      PMA_ARBITRARY: 1
    ports:
      - "8081:80"
    depends_on:
      - mysql

volumes:
  mysql_data:
  redis_data:
EOF

echo "docker-compose.override.yml created."

# Create development Dockerfile
cat > Dockerfile.dev << 'EOF'
FROM php:8.1-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libcurl4-openssl-dev \
    pkg-config \
    libssl-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && docker-php-ext-enable opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Xdebug for development
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Copy custom PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/php.ini

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 9000

CMD ["php-fpm"]
EOF

# Create development nginx configuration
mkdir -p docker/nginx/conf.d
cat > docker/nginx/conf.d/app.conf << 'EOF'
server {
    listen 80;
    server_name localhost;
    root /var/www/html/public;
    index index.php index.html;

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
EOF

# Create PHP configuration for development
mkdir -p docker/php
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
error_log = /var/log/php_errors.log

[xdebug]
xdebug.mode=debug,develop
xdebug.start_with_request=yes
xdebug.client_host=host.docker.internal
xdebug.client_port=9003
xdebug.idekey=VSCODE
xdebug.mode=debug,develop
xdebug.start_with_request=yes
xdebug.discover_client_host=1
xdebug.idekey=VSCODE
xdebug.log=/tmp/xdebug.log
xdebug.log_level=7
EOF

# Create MySQL initialization script
cat > docker/mysql/initdb/01-init.sql << 'EOF'
-- Create databases
CREATE DATABASE IF NOT EXISTS radius_development;
CREATE DATABASE IF NOT EXISTS radius_test;

-- Create user and grant privileges
CREATE USER IF NOT EXISTS 'radius_dev'@'%' IDENTIFIED BY 'dev_password';
GRANT ALL PRIVILEGES ON radius_development.* TO 'radius_dev'@'%';
GRANT ALL PRIVILEGES ON radius_test.* TO 'radius_dev'@'%';
FLUSH PRIVILEGES;
EOF

# Create a development setup script
cat > setup-dev.sh << 'EOF'
#!/bin/bash

echo "Setting up development environment for RADIUS Service Enhancement..."

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "Docker is not running. Please start Docker Desktop or Docker Engine."
    exit 1
fi

# Build and start containers
echo "Building and starting development containers..."
docker-compose -f docker-compose.yml -f docker-compose.override.yml up -d --build

echo ""
echo "Development environment is being set up..."
echo "Services will be available at:"
echo "  - Application: http://localhost:8080"
echo "  - phpMyAdmin: http://localhost:8081"
echo "  - Redis: localhost:6379"
echo ""
echo "To view logs: docker-compose logs -f"
echo "To stop services: docker-compose down"
echo ""
echo "To run database migrations:"
echo "  docker-compose exec app php artisan migrate"
echo ""
echo "To run tests:"
echo "  docker-compose exec app php vendor/bin/phpunit"
EOF

chmod +x setup-dev.sh

# Create a development README
cat > DEVELOPMENT.md << 'EOF'
# Development Environment Setup

## Prerequisites
- Docker and Docker Compose
- Git

## Quick Start

1. Clone the repository
2. Run the setup script:
   ```bash
   chmod +x dev-setup.sh
   ./dev-setup.sh
   ```

3. Start the development environment:
   ```bash
   docker-compose up -d
   ```

4. Access the application:
   - Application: http://localhost:8080
   - phpMyAdmin: http://localhost:8081
   - Redis: localhost:6379

## Development Commands

### Start services
```bash
docker-compose up -d
```

### Stop services
```bash
docker-compose down
```

### View logs
```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f app
```

### Run tests
```bash
docker-compose exec app php vendor/bin/phpunit
```

### Run database migrations
```bash
docker-compose exec app php artisan migrate
```

### Access containers
```bash
# Access app container
docker-compose exec app bash

# Access MySQL
docker-compose exec mysql mysql -u root -p

# Access Redis
docker-compose exec redis redis-cli
```

## Development Workflow

1. Code changes are automatically synced to the container
2. PHP-FPM will auto-reload on code changes
3. Xdebug is configured for debugging on port 9003
4. Logs are available via `docker-compose logs`

## Environment Variables

Development environment variables are in `.env.development`
EOF

echo ""
echo "Development environment setup complete!"
echo "Run './dev-setup.sh' to start the development environment."
echo "Check DEVELOPMENT.md for more details."
echo ""
echo "Next steps:"
echo "1. Run: chmod +x dev-setup.sh"
echo "2. Run: ./dev-setup.sh"
echo "3. Run: docker-compose up -d"
echo ""
echo "For more details, see DEVELOPMENT.md"