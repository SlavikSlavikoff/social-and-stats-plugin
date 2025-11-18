<?php

$root = dirname(__DIR__, 3);
$loader = require $root.'/vendor/autoload.php';

// Register the plugin namespaces so that classes are autoloaded even if the
// plugin is not enabled inside Azuriom yet.
$loader->addPsr4('Azuriom\\Plugin\\InspiratoStats\\', dirname(__DIR__).'/src');
$loader->addPsr4('Azuriom\\Plugin\\InspiratoStats\\Tests\\', __DIR__.'/');
