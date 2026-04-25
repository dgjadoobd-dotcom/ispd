# RADIUS Service Enhancement - Development Setup

This document describes how to set up and use the development environment for the RADIUS Service Enhancement project.

## Prerequisites

- Docker and Docker Compose
- Git (for version control)
- Code editor (VS Code, PHPStorm, etc.)

## Quick Start

### 1. Clone and Setup
```bash
# Clone the repository (if not already done)
git clone <repository-url>
cd ispd

# Set up development environment
php setup-dev.php
```

### 2. Start Development Environment
```bash
# Start all services
docker-compose -f docker-compose.dev.yml up -d

# Or use the helper script
dev-helper.bat start
```

### 3. Access Services
- **Application**: http://localhost:8080
- **phpMyAdmin**: http://localhost:8081 (root/rootpassword)
- **MySQL**: localhost:3306
- **Redis**: localhost:6379

## Development Environment Structure

```
.
├── app/                    # Application code
│   ├── Controllers/        # Controller classes
│   ├── Services/          # Business logic (including RadiusService)
│   ├── Core/              # Core framework components
│   └── Middleware/        # HTTP middleware
├── config/                # Configuration files
├── database/              # Database schemas and migrations
├── docker/                # Docker configuration
├── public/                # Web root
├── views/                 # View templates
└── tests/                 # Test files
```

## Development Workflow

### 1. Starting Development
```bash
# Start all services
dev-helper.bat start

# Or manually
docker-compose -f docker-compose.dev.yml up -d
```

### 2. Running Tests
```bash
# Run unit tests
docker-compose -f docker-compose.dev.yml exec app php vendor/bin/phpunit

# Run specific test file
docker-compose -f docker-compose.dev.yml exec app php vendor/bin/phpunit tests/Unit/RadiusServiceTest.php
```

### 3. Database Management
```bash
# Access MySQL
dev-helper.bat mysql

# Run migrations
docker-compose -f docker-compose.dev.yml exec app php artisan migrate

# Seed the database
docker-compose -f docker-compose.dev.yml exec app php artisan db:seed
```

### 4. Development Tools

#### Code Quality
```bash
# Run PHP Code Sniffer
docker-compose -f docker-compose.dev.yml exec app ./vendor/bin/phpcs

# Run PHPStan for static analysis
docker-compose -f docker-compose.dev.yml exec app ./vendor/bin/phpstan analyse
```

#### Debugging
- Xdebug is configured for VS Code
- Use `xdebug_break()` in code to trigger breakpoints
- Debug port: 9003

## Development Commands

### Using Helper Script
```bash
# Start all services
dev-helper.bat start

# Stop all services
dev-helper.bat stop

# View logs
dev-helper.bat logs

# Access container shell
dev-helper.bat shell

# Access MySQL
dev-helper.bat mysql

# Check status
dev-helper.bat status
```

### Manual Docker Commands
```bash
# Build containers
docker-compose -f docker-compose.dev.yml build

# Start services
docker-compose -f docker-compose.dev.yml up -d

# View logs
docker-compose -f docker-compose.dev.yml logs -f

# Stop services
docker-compose -f docker-compose.dev.yml down
```

## Database

### Development Database
- **Host**: localhost:3306
- **Database**: radius_development
- **Username**: radius_dev
- **Password**: dev_password

### Test Database
- **Database**: radius_test
- **Username**: test_user
- **Password**: test_password

### Database Migrations
```bash
# Create new migration
docker-compose -f docker-compose.dev.yml exec app php artisan make:migration create_users_table

# Run migrations
docker-compose -f docker-compose.dev.yml exec app php artisan migrate

# Rollback last migration
docker-compose -f docker-compose.dev.yml exec app php artisan migrate:rollback
```

## Testing

### Running Tests
```bash
# Run all tests
docker-compose -f docker-compose.dev.yml exec app php vendor/bin/phpunit

# Run specific test
docker-compose -f docker-compose.dev.yml exec app php vendor/bin/phpunit tests/Unit/RadiusServiceTest.php

# Run with coverage
docker-compose -f docker-compose.dev.yml exec app php vendor/bin/phpunit --coverage-html coverage
```

### Test Data
Test data is automatically loaded when the database is initialized. You can also load additional test data:

```bash
# Load test data
dev-helper.bat test-data
```

## Debugging

### Xdebug Configuration
Xdebug is pre-configured for VS Code. To use:

1. Set breakpoints in VS Code
2. Start debugging session (F5)
3. Xdebug will trigger on breakpoints

### Logs
- Application logs: `logs/app.log`
- Nginx logs: `logs/nginx/`
- PHP-FPM logs: `logs/php/`
- MySQL logs: `logs/mysql/`

## Environment Variables

Key environment variables in `.env`:

```env
APP_ENV=development
APP_DEBUG=true
DB_CONNECTION=mysql
DB_HOST=mysql
DB_DATABASE=radius_development
DB_USERNAME=radius_dev
DB_PASSWORD=dev_password
```

## Troubleshooting

### Common Issues

1. **Ports already in use**
   - Check if ports 8080, 8081, 3306 are in use
   - Change ports in docker-compose.dev.yml if needed

2. **Database connection issues**
   - Check if MySQL container is running: `docker-compose ps`
   - Verify credentials in .env file
   - Check logs: `docker-compose logs mysql`

3. **Permission issues**
   - Ensure proper file permissions: `chmod -R 755 storage bootstrap/cache`
   - Check Docker volume permissions

### Getting Help
- Check logs: `docker-compose -f docker-compose.dev.yml logs`
- Restart services: `docker-compose -f docker-compose.dev.yml restart`
- Rebuild containers: `docker-compose -f docker-compose.dev.yml up --build -d`

## Development Workflow

1. **Start development environment**: `dev-helper.bat start`
2. **Make code changes** in your editor
3. **Run tests**: `dev-helper.bat test`
4. **Check logs**: `dev-helper.bat logs`
5. **Stop when done**: `dev-helper.bat stop`

## Additional Resources

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Reference](https://docs.docker.com/compose/compose-file/)
- [PHP Documentation](https://www.php.net/manual/en/)
- [MySQL Documentation](https://dev.mysql.com/doc/)

## Support

For issues with the development environment, check:
1. Docker and Docker Compose are installed and running
2. Ports 8080, 8081, 3306 are available
3. Sufficient disk space and memory
4. Correct .env configuration