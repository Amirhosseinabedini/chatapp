<?php
/**
 * Public entry point for Symfony application
 * This is the standard entry point when document root is public/
 */

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
