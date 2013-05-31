<?php

# autoloader for the ferno\loco namespace (for internal use)

spl_autoload_register(function($class) {
    if (strpos($class, 'ferno\loco\\') === 0) {
        $path = __DIR__
            . DIRECTORY_SEPARATOR
            . strtr($class, '\\', DIRECTORY_SEPARATOR)
            . '.php';

        if (!file_exists($path)) {
            throw new RuntimeException("file not found: $path (class: $class)");
        }

        require $path;

        if (! class_exists($class)) {
            throw new RuntimeException("class not found: $class (path: $path)");
        }
    }
});
