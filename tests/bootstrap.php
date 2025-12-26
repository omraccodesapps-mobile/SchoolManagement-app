<?php

/**
 * Bootstrap file for PHPUnit tests in Symfony application.
 * 
 * This file is responsible for:
 * - Loading Composer autoloader
 * - Loading environment variables
 * - Initializing Symfony kernel
 * - Setting up test environment
 */

use Symfony\Component\Dotenv\Dotenv;

// ============================================================================
// Load Composer Autoloader
// ============================================================================
require dirname(__DIR__).'/vendor/autoload.php';

// ============================================================================
// Load Environment Variables
// ============================================================================
if (!isset($_SERVER['APP_ENV'])) {
    $_SERVER['APP_ENV'] = 'test';
}

// Load .env files (use .env.test if it exists, otherwise .env)
if (file_exists(dirname(__DIR__).'/.env.test')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env.test');
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

// ============================================================================
// Set Required Environment Variables
// ============================================================================
$_SERVER['APP_ENV'] = $_SERVER['APP_ENV'] ?? 'test';
$_SERVER['APP_DEBUG'] = $_SERVER['APP_DEBUG'] ?? false;
$_SERVER['KERNEL_CLASS'] = $_SERVER['KERNEL_CLASS'] ?? 'App\Kernel';

// ============================================================================
// Configure SQLite In-Memory Database for Tests
// ============================================================================
if (empty($_SERVER['DATABASE_URL']) || strpos($_SERVER['DATABASE_URL'], ':memory:') === false) {
    $_SERVER['DATABASE_URL'] = 'sqlite:///:memory:';
}

// ============================================================================
// Set Umask for File Permissions
// ============================================================================
if ($_SERVER['APP_DEBUG'] ?? false) {
    umask(0000);
}

// ============================================================================
// Additional Test Configuration
// ============================================================================
// Enable strict error reporting in tests
error_reporting(E_ALL);
ini_set('display_errors', '1');

