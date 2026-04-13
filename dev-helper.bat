@echo off
REM Development Helper Script for RADIUS Service Enhancement
REM Usage: dev-helper [command]

if "%1"=="" (
    echo Usage: dev-helper [command]
    echo.
    echo Commands:
    echo   start     - Start development environment
    echo   stop      - Stop development environment
    echo   restart   - Restart development environment
    echo   logs      - Show logs
    echo   build     - Build containers
    echo   shell     - Open shell in app container
    echo   mysql     - Connect to MySQL
    echo   status    - Show container status
    echo   clean     - Clean up containers and volumes
    echo   test-data - Create test data
    echo   help      - Show this help
    goto :eof
)

if "%1"=="start" (
    echo Starting development environment...
    docker-compose -f docker-compose.dev.yml up -d
    echo.
    echo Development environment started!
    echo Application: http://localhost:8080
    echo phpMyAdmin: http://localhost:8081
    echo.
    echo Default credentials:
    echo phpMyAdmin: root / rootpassword
    echo Test user: testuser1 / password123
    goto :eof
)

if "%1"=="stop" (
    echo Stopping development environment...
    docker-compose -f docker-compose.dev.yml down
    goto :eof
)

if "%1"=="restart" (
    echo Restarting development environment...
    docker-compose -f docker-compose.dev.yml down
    docker-compose -f docker-compose.dev.yml up -d
    goto :eof
)

if "%1"=="logs" (
    docker-compose -f docker-compose.dev.yml logs -f
    goto :eof
)

if "%1"=="build" (
    echo Building containers...
    docker-compose -f docker-compose.dev.yml build
    goto :eof
)

if "%1"=="shell" (
    docker-compose -f docker-compose.dev.yml exec app bash
    goto :eof
)

if "%1"=="mysql" (
    docker-compose -f docker-compose.dev.yml exec mysql mysql -u root -prootpassword
    goto :eof
)

if "%1"=="status" (
    docker-compose -f docker-compose.dev.yml ps
    goto :eof
)

if "%1"=="clean" (
    echo Cleaning up containers and volumes...
    docker-compose -f docker-compose.dev.yml down -v
    echo Cleanup complete.
    goto :eof
)

if "%1"=="test-data" (
    echo Creating test data...
    docker-compose -f docker-compose.dev.yml exec mysql mysql -u root -prootpassword radius << "EOF"
    -- Create test data
    USE radius;
    
    -- Add test users if they don't exist
    INSERT IGNORE INTO radcheck (username, attribute, op, value) VALUES
    ('testuser1', 'Cleartext-Password', ':=', 'password123'),
    ('testuser2', 'Cleartext-Password', ':=', 'password456'),
    ('testuser3', 'Cleartext-Password', ':=', 'password789');
    
    INSERT IGNORE INTO radusergroup (username, groupname, priority) VALUES
    ('testuser1', 'bronze', 1),
    ('testuser2', 'silver', 1),
    ('testuser3', 'gold', 1);
    
    SELECT 'Test data created successfully!' as message;
EOF
    echo Test data created!
    goto :eof
)

if "%1"=="help" (
    echo Development Helper Script
    echo ======================
    echo.
    echo Available commands:
    echo   start     - Start development environment
    echo   stop      - Stop development environment
    echo   restart   - Restart development environment
    echo   logs      - Show logs
    echo   build     - Build containers
    echo   shell     - Open shell in app container
    echo   mysql     - Connect to MySQL
    echo   status    - Show container status
    echo   clean     - Clean up containers and volumes
    echo   test-data - Create test data
    echo   help      - Show this help
    goto :eof
)

echo Unknown command: %1
echo Use "dev-helper help" for available commands