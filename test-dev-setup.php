<?php
/**
 * Test script to verify development environment setup
 */

echo "Testing RADIUS Service Enhancement Development Environment\n";
echo "================================================\n\n";

// Test 1: Check if .env file exists
echo "1. Checking environment configuration...\n";
if (file_exists('.env')) {
    echo "   ✓ .env file exists\n";
    
    // Read and check some key values
    $envContent = file_get_contents('.env');
    if (strpos($envContent, 'APP_ENV=development') !== false) {
        echo "   ✓ Development environment detected\n";
    }
    if (strpos($envContent, 'APP_DEBUG=true') !== false) {
        echo "   ✓ Debug mode is enabled\n";
    }
} else {
    echo "   ✗ .env file not found\n";
}

// Test 2: Check if required directories exist
echo "\n2. Checking directory structure...\n";
$requiredDirs = [
    'docker/mysql/initdb',
    'docker/nginx/conf.d',
    'docker/php',
    'logs',
    'tmp'
];

foreach ($requiredDirs as $dir) {
    if (is_dir($dir)) {
        echo "   ✓ Directory exists: $dir\n";
    } else {
        echo "   ✗ Missing directory: $dir\n";
    }
}

// Test 3: Check Docker configuration
echo "\n3. Checking Docker configuration...\n";
$dockerFiles = [
    'docker-compose.dev.yml' => 'docker-compose.dev.yml',
    'Dockerfile.dev' => 'Dockerfile.dev',
    'docker/nginx/conf.d/app.conf' => 'docker/nginx/conf.d/app.conf'
];

foreach ($dockerFiles as $description => $file) {
    if (file_exists($file)) {
        echo "   ✓ $description exists\n";
    } else {
        echo "   ✗ Missing: $description\n";
    }
}

// Test 4: Check for required files
echo "\n4. Checking for required files...\n";
$requiredFiles = [
    'docker-compose.dev.yml' => 'Docker Compose configuration',
    'Dockerfile.dev' => 'Development Dockerfile',
    'docker/php/php.ini' => 'PHP configuration',
    'docker/nginx/conf.d/app.conf' => 'Nginx configuration'
];

foreach ($requiredFiles as $file => $description) {
    if (file_exists($file)) {
        echo "   ✓ $description exists\n";
    } else {
        echo "   ✗ Missing: $description ($file)\n";
    }
}

// Test 5: Check for development helper scripts
echo "\n5. Checking development tools...\n";
$devScripts = [
    'dev-helper.bat' => 'Windows helper script',
    'dev-helper.sh' => 'Linux/Mac helper script'
];

foreach ($devScripts as $script => $description) {
    if (file_exists($script)) {
        echo "   ✓ $description exists\n";
    } else {
        echo "   - $description not found (optional)\n";
    }
}

// Test 6: Check for development documentation
echo "\n6. Checking documentation...\n";
if (file_exists('README-DEVELOPMENT.md') || file_exists('README-DEVELOPMENT.md')) {
    echo "   ✓ Development documentation exists\n";
} else {
    echo "   ⚠ Development documentation not found\n";
}

// Test 7: Check for development tools
echo "\n7. Checking development tools...\n";
$tools = [
    'docker' => 'Docker',
    'docker-compose' => 'Docker Compose',
    'git' => 'Git'
];

foreach ($tools as $command => $name) {
    $output = [];
    $return_var = 0;
    exec("which $command 2>nul", $output, $return_var);
    
    if ($return_var === 0) {
        echo "   ✓ $name is available\n";
    } else {
        echo "   ⚠ $name not found in PATH\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Development Environment Test Complete\n";
echo "To start development environment:\n";
echo "1. docker-compose -f docker-compose.dev.yml up -d\n";
echo "2. Access the application at http://localhost:8080\n";
echo "3. Access phpMyAdmin at http://localhost:8081\n";
echo str_repeat("=", 50) . "\n";

// Check for common issues
echo "\nCommon issues to check:\n";
echo "1. Docker and Docker Compose must be installed and running\n";
echo "2. Ports 8080 and 8081 should be available\n";
echo "3. Check .env file for correct database credentials\n";
echo "4. Ensure Docker has enough resources (at least 2GB RAM)\n";

?>