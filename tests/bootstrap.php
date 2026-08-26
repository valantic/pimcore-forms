<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Define test constants
define('PIMCORE_TEST_MODE', true);

// Mock Pimcore constants if needed
if (!defined('PIMCORE_VERSION')) {
    define('PIMCORE_VERSION', '12.0.0');
}
