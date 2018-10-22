<?php

use Nette\Configurator;
use Nette\Database\Connection;
use Tester\Environment;
use Tester\Helpers;

require __DIR__ . '/../vendor/autoload.php';

define("APP_DIR", __DIR__ . "/../app");
define("WWW_DIR", __DIR__ . "/../www");

define('SHARED_TMP_DIR', __DIR__ . '/temp');
define('TMP_DIR', __DIR__ . "/temp/" . getmypid());

register_shutdown_function(function() {
    // cleanup database __destruct
    gc_collect_cycles();
    // cleanup ourselves
    Tester\Helpers::purge(TMP_DIR);
    @rmdir(TMP_DIR);
});

Environment::setup();
date_default_timezone_set('Europe/Prague');

@mkdir(SHARED_TMP_DIR);
Helpers::purge(TMP_DIR);
Helpers::purge(TMP_DIR . '/log');

$configurator = new Configurator;

$configurator->setDebugMode(FALSE);
//$configurator->enableDebugger(TMP_DIR . '/log');
$configurator->setTempDirectory(TMP_DIR);

$configurator->createRobotLoader()
        ->addDirectory(APP_DIR)
        ->addDirectory(__DIR__)
        ->register();

$configurator->addConfig(APP_DIR . '/config/config.neon');
$configurator->addConfig(APP_DIR . '/config/config.local.neon');
$configurator->addConfig(__DIR__ . '/config/config.test.neon');
$configurator->addParameters(array("wwwDir" => TMP_DIR));
$configurator->addParameters(array("appDir" => APP_DIR));
$configurator->addParameters(array("testDir" => __DIR__));

$configurator->addParameters([
    'appDir' => APP_DIR,
    'wwwDir' => TMP_DIR
]);

$container = $configurator->createContainer();

Environment::lock('database', SHARED_TMP_DIR);

/** @var Connection $database */
$database = $container->getByType(Connection::class);
$database->query(file_get_contents(__DIR__ . '/db/init.sql'));

/** helpers */
function run($testcase) {
    $testcase->run();
}

return $container;
