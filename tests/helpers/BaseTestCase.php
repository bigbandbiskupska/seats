<?php

namespace App\Tests;

use Nette\Application\IPresenterFactory;
use Nette\Database\Context;
use Nette\DI\Container;
use Nette\Http\IResponse;
use Nette\Utils\Json;
use Tester\TestCase;

class BaseTestCase extends TestCase {

    /** @var Container */
    protected $container;

    /** @var Context */
    protected $database;

    public function __construct(Container $container) {
        $this->container = $container;
        $this->database = $this->container->getByType(Context::class);
        $this->setUpClass();
    }

    public function __destruct() {
       $this->tearDownClass();
    }

    protected function setUpClass() {
    }

    protected function tearDownClass() {
    }

    public function setUpRequestInput($data) {
        $httpRequestFactory = $this->container->getByType(RequestFactory::class);

        $httpRequestFactory->setRawBodyCallback(function() use ($data) {
            return Json::encode($data);
        });
    }

    public function createPresenter($name) {
        $factory = $this->container->getByType(IPresenterFactory::class);
        $presenter = $factory->createPresenter($name);
        $presenter->autoCanonicalize = false;
        $presenter->getHttpResponse()->setCode(IResponse::S200_OK);
        return $presenter;
    }
}
