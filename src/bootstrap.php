<?php

global $vendor_path;

if (! isset($vendor_path)) {
    if (file_exists(__DIR__.'/../vendor/autoload.php')) {
        $vendor_path = __DIR__.'/../vendor/';
    } else {
        $vendor_path = __DIR__.'/../../../';
    }
}

require $vendor_path.'autoload.php';

$app = new CraftCli\Application($vendor_path);

$app->addComposerCommands();
$app->addUserDefinedCommands();

$app->run();
