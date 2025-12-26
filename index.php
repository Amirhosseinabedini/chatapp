<?php
/**
 * Root index.php - Bootstrap Symfony when document root is project root
 * This file is used for Ionos and other shared hosting servers
 * where document root points to project root instead of public/
 */

use App\Kernel;

// Bootstrap Symfony runtime (this will automatically load .env files)
require_once __DIR__ . '/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
