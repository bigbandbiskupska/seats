<?php

require __DIR__ . '/../vendor/autoload.php';

header('Access-Control-Allow-Origin: *');

$configurator = new Nette\Configurator;

$configurator->setDebugMode(array('172.18.0.1', '172.20.0.1', '127.0.0.1', '85.70.17.135', '213.220.225.67')); // enable for your remote IP
$configurator->setDebugMode(true); // enable for your remote IP
$configurator->enableDebugger(__DIR__ . '/../log');

$configurator->setTempDirectory(__DIR__ . '/../temp');

$configurator->createRobotLoader()
    ->addDirectory(__DIR__)
    ->register();

$configurator->addConfig(__DIR__ . '/config/config.neon');
$configurator->addConfig(__DIR__ . '/config/config.local.neon');

$container = $configurator->createContainer();

return $container;
