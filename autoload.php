<?php

declare(strict_types=1);

/**
 * Autoloader - loads classes according to the namespace -> directory mapping.
 *
 * Core\ → /core/
 * Src\ → /src/
 *
 * Examples:
 * Core\Database → /core/Database.php
 * Core\Router → /core/Router.php
 * Src\Controller\Auth → /src/Controller/Auth.php
 * Src\Model\User → /src/Model/User.php
 */
spl_autoload_register(function (string $class): void {
    $namespaceMap = [
        'Core\\' => BASE_PATH . '/core/',
        'Src\\'  => BASE_PATH . '/src/',
    ];

    foreach ($namespaceMap as $prefix => $baseDir) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }

        $relative = substr($class, strlen($prefix));
        $file     = $baseDir . str_replace('\\', '/', $relative) . '.php';

        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
    // If the class is not found, PHP will throw an exception.
});