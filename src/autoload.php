<?php

/**
 * PSR-4 Autoloader
 */
spl_autoload_register(function ($class) {
    // Convert namespace to path
    // App\Domain\Brand -> Domain/Brand.php
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
        return;
    }

    // Fallback for bundled domain exceptions defined in one file
    if (str_starts_with($relativeClass, 'Domain\\')) {
        $exceptionsFile = $baseDir . 'Domain/Exceptions.php';
        if (file_exists($exceptionsFile)) {
            require_once $exceptionsFile;
        }
    }
});
