<?php
\spl_autoload_register(function($class) {
  //  if(strpos($class, "Learning_tools_integration") !== FALSE) return;
    // Project-specific namespace prefix
    $prefix = 'LTI\\ExtensionHooks';

    // Does the class use the namespace prefix?
    $len = \strlen($prefix);
    if (\strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    include __DIR__ . "/classes/" . $class . '.php';
});
?>
