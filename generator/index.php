<?php

$vendor = realpath(__DIR__ . '/vendor');

if (file_exists($vendor . "/autoload.php")) {
    require_once $vendor . "/autoload.php";
} else {
    $vendor = realpath(__DIR__ . '/../../');
    if (file_exists($vendor . "/autoload.php")) {
        require_once $vendor . "/autoload.php";
    } else {
        throw new Exception("Unable to load dependencies");
    }
}

use Symfony\Component\Console\Application;
use Smart\Geo\Generator\DataGenerator\Country\Command\GenerateDataCommand as GenerateCountryDataCommand;
use Smart\Geo\Generator\DataGenerator\Region\Command\GenerateDataCommand as GenerateRegionDataCommand;

$container = new \Smart\Geo\Generator\Container();

$application = new Application();
$application->add(new GenerateCountryDataCommand($container));
$application->add(new GenerateRegionDataCommand($container));
$application->run();
