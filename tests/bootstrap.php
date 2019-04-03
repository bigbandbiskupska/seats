<?php

use App\Tests\CustomTestCase;
use App\Tests\TestCaseWithDatabase;
use Nette\Database\Connection;

require __DIR__ . '/../vendor/autoload.php';

define("APP_DIR", __DIR__ . "/../app");
define("WWW_DIR", __DIR__ . "/../www");

define('TMP_DIR', __DIR__ . "/temp/" . getmypid());

register_shutdown_function(function () {
    //Tester\Helpers::purge(TMP_DIR);
    //@rmdir(TMP_DIR);
});

Tester\Environment::setup();
date_default_timezone_set('Europe/Prague');

@mkdir(__DIR__ . "/temp");
Tester\Helpers::purge(TMP_DIR);
Tester\Helpers::purge(TMP_DIR . '/log');

$configurator = new Nette\Configurator;

$configurator->setDebugMode(true);
$configurator->enableDebugger(TMP_DIR . '/log');
$configurator->setTempDirectory(TMP_DIR);

$configurator->createRobotLoader()
    ->addDirectory(APP_DIR)
    ->addDirectory(__DIR__)
    ->register();


$configurator->addConfig(APP_DIR . '/config/config.neon');
$configurator->addConfig(APP_DIR . '/config/config.local.neon');
$configurator->addConfig(APP_DIR . '/config/config.test.neon');
$configurator->addParameters(array("wwwDir" => TMP_DIR));
$configurator->addParameters(array("appDir" => APP_DIR));
$configurator->addParameters(array("testDir" => __DIR__));

$configurator->addParameters([
    'appDir' => APP_DIR,
    'wwwDir' => TMP_DIR
]);

$container = $configurator->createContainer();

/** helpers */
function run($testcase)
{
    if ($testcase instanceof TestCaseWithDatabase) {
        global $container;

        /** @var Connection $database */
        $database = $container->getByType(Connection::class);
        $database->query('CREATE DATABASE bbb_test_seats_' . getmypid());
        $database->query('USE bbb_test_seats_' . getmypid());
        $database->query(file_get_contents(__DIR__ . '/db/init.sql'));
        $database->query('USE bbb_test_seats_' . getmypid());

        register_shutdown_function(function () use ($container) {
            /** @var Connection $database */
            //$database = $container->getByType(Connection::class);
            //$database->query('DROP DATABASE bbb_test_seats_' . getmypid());
        });
    }

    if ($testcase instanceof CustomTestCase) {
        $testcase->setUpClass();
    }

    $testcase->run();

    if ($testcase instanceof CustomTestCase) {
        $testcase->tearDownClass();
    }
}

return $container;
