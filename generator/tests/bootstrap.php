<?php

date_default_timezone_set('UTC');

$vendor = realpath(__DIR__ . '/../vendor');

if (file_exists($vendor . "/autoload.php")) {
    require_once $vendor . "/autoload.php";
} else {
    $vendor = realpath(__DIR__ . '/../../../');
    if (file_exists($vendor . "/autoload.php")) {
        require_once $vendor . "/autoload.php";
    } else {
        throw new Exception("Unable to load dependencies");
    }
}

global $container;
$container = new \Smart\Geo\Generator\Container();
$container->getConfig()->setProviderStorage(realpath(__DIR__ . "/../storage"));
