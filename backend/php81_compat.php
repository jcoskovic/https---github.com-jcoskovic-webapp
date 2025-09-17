<?php

// PHP 8.1 Compatibility Helper
// This file helps suppress deprecation warnings temporarily

// Set error reporting to exclude deprecated warnings
error_reporting(E_ALL & ~E_DEPRECATED);

// Custom error handler for deprecated warnings
set_error_handler(function ($severity, $message, $file, $line) {
    // If this is a deprecation warning, log it but don't stop execution
    if ($severity === E_DEPRECATED) {
        // Log to Laravel log if available
        if (function_exists('logger')) {
            logger()->warning("PHP 8.1 Deprecation: $message in $file on line $line");
        }
        return true; // Don't execute PHP's internal error handler
    }

    // For all other errors, use default handling
    return false;
}, E_DEPRECATED);
