<?php
\spl_autoload_register(function($class) {
    // Project-specific namespace prefix
    $prefix = 'LTI\\ExtensionHooks';

    // Does the class use the namespace prefix?
    $len = \strlen($prefix);
    if (\strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    include __DIR__ . '/' . $class . '.php';
});
?>
