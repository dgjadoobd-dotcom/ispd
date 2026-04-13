<?php
/**
 * Test script to verify development environment setup
 */

echo "RADIUS Service Enhancement - Development Environment Test\n";
echo "==================================================\n\n";

// Test 1: Check for required files
echo "1. Checking for required files...\n";
$requiredFiles = [
    '.env' => 'Environment configuration',
    'docker-compose.dev.yml' => 'Docker Compose file',
    'Dockerfile.dev' => 'Dockerfile for development',
    'docker/nginx/default.conf' => 'Nginx configuration',
    'docker/php/php.ini' => 'PHP configuration',
    'docker/mysql/init/01-init.sql' => 'MySQL initialization script'
];

$allFilesExist = true;
foreach ($requiredFiles as $file => $description) {
    if (file_exists($file)) {
        echo "  ✓ $description: $file\n";
    } else {
        echo "  ✗ Missing: $description ($file)\n";
        $allFilesExist = false;
    }
}

// Test 2: Check Docker and Docker Compose
echo "\n2. Checking Docker and Docker Compose...\n";
$dockerVersion = shell_exec('docker --version 2>&1');
$dockerComposeVersion = shell_exec('docker-compose --version 2>&1');

if (strpos($dockerVersion, 'Docker version') !== false) {
    echo "  ✓ Docker is installed\n";
} else {
    echo "  ✗ Docker is not installed or not in PATH\n";
}

if (strpos($dockerComposeVersion, 'docker-compose version') !== false) {
    echo "  ✓ Docker Compose is installed\n";
} else {
    echo "  ✗ Docker Compose is not installed or not in PATH\n";
}

// Test 3: Check for required directories
echo "\n3. Checking directory structure...\n";
$requiredDirs = [
    'app/Controllers',
    'app/Services', 
    'config',
    'database',
    'docker',
    'public',
    'views'
];

foreach ($requiredDirs as $dir) {
    if (is_dir($dir)) {
        echo "  ✓ Directory exists: $dir\n";
    } else {
        echo "  ✗ Missing directory: $dir\n";
    }
}

// Test 4: Check for required PHP extensions
echo "\n4. Checking PHP environment...\n";
$extensions = ['pdo_mysql', 'pdo', 'json', 'mbstring', 'openssl'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "  ✓ Extension: $ext\n";
    } else {
        echo "  ✗ Missing extension: $ext\n";
    }
}

// Test 5: Check for .env configuration
echo "\n5. Checking environment configuration...\n";
if (file_exists('.env')) {
    $envContent = file_get_contents('.env');
    $requiredVars = ['APP_ENV', 'DB_HOST', 'DB_DATABASE', 'DB_USERNAME'];
    
    foreach ($requiredVars as $var) {
        if (strpos($envContent, $var) !== false) {
            echo "  ✓ Environment variable configured: $var\n";
        } else {
            echo "  ⚠  Missing or commented: $var\n";
        }
    }
} else {
    echo "  ✗ .env file not found\n";
}

// Test 6: Check for development tools
echo "\n6. Checking development tools...\n";
$tools = ['git', 'composer', 'php'];
foreach ($tools as $tool) {
    $output = [];
    $return_var = 0;
    exec("which $tool 2>nul", $output, $return_var);
    
    if ($return_var === 0) {
        echo "  ✓ $tool is available\n";
    } else {
        echo "  ⚠  $tool is not in PATH\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Development Environment Test Complete\n";
echo "To start the development environment:\n";
echo "1. docker-compose -f docker-compose.dev.yml up -d\n";
echo "2. Access the application at http://localhost:8080\n";
echo "3. Access phpMyAdmin at http://localhost:8081\n";
echo str_repeat("=", 50) . "\n";