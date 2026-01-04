<?php
spl_autoload_register(function ($class) {
   // PhpSpreadsheet
   $prefix = 'PhpOffice\\PhpSpreadsheet\\';
   $base_dir = __DIR__ . '/src/PhpSpreadsheet/';
   if (strncmp($prefix, $class, strlen($prefix)) === 0) {
       $relative = substr($class, strlen($prefix));
       $file = $base_dir . str_replace('\\', '/', $relative) . '.php';
       if (file_exists($file)) require $file;
       return;
   }
   // PSR Simple Cache
   $psrPrefix = 'Psr\\SimpleCache\\';
   $psrBase = dirname(__DIR__) . '/psr/simple-cache/src/';
   if (strncmp($psrPrefix, $class, strlen($psrPrefix)) === 0) {
       $relative = substr($class, strlen($psrPrefix));
       $file = $psrBase . str_replace('\\', '/', $relative) . '.php';
       if (file_exists($file)) require $file;
       return;
   }
});