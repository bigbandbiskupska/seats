<?php

use App\Tests\TestCaseWithDatabase;
use Nette\Application\BadRequestException;
use Nette\Application\Request;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\Presenter;
use Nette\Http\IResponse;
use Tester\Assert;

$container = require __DIR__ . "/../../bootstrap.php";

class SchemaPresenterTest extends TestCaseWithDatabase {

    /** @var Presenter */
    protected $presenter;

    public function setUpClass() {
        $this->presenter = $this->createPresenter('v1:Schema');
    }

    public function testUnauthorizedRead() {
        Assert::exception(function() {
            $request = new Request('v1:Schema', 'GET', array('action' => 'read', 'id' => 1));
            $this->presenter->run($request);
        }, BadRequestException::class, null, IResponse::S401_UNAUTHORIZED);
    }

    public function testForbiddenRead() {
        Assert::exception(function() {
            $request = new Request('v1:Schema', 'GET', array('action' => 'read', 'id' => 1, 'token' => '1234'));
            $this->presenter->run($request);
        }, BadRequestException::class, null, IResponse::S403_FORBIDDEN);
    }

    public function testRead() {
        $request = new Request('v1:Schema', 'GET', array('action' => 'read', 'id' => 1, 'token' => 'abcd'));
        /** @var JsonResponse */
        $response = $this->presenter->run($request);
        $httpResponse = $this->container->getByType(IResponse::class);

        Assert::type(JsonResponse::class, $response);
        Assert::equal(IResponse::S200_OK, $httpResponse->getCode());
        Assert::true($response->getPayload() != null);

        Assert::equal([
            'id' => 1,
            'name' => 'Vánoční koncert',
            'price' => 250,
            'hidden' => 1,
            'locked' => 0,
            'limit' => 5,
            'seats' => array_values($this->database->table('seats')->where('schema_id', 1)->fetchPairs('id', 'id')),
        ], $response->getPayload());
    }

    public function testInvalidRead() {
        Assert::exception(function() {
            $request = new Request('v1:Schema', 'GET', array('action' => 'read', 'id' => 100, 'token' => 'abcd'));
            $response = $this->presenter->run($request);
        }, BadRequestException::class);
    }

    public function testUnauthorizedDelete() {
        Assert::exception(function() {
            $request = new Request('v1:Schema', 'DELETE', array('action' => 'delete', 'id' => 1));
            $this->presenter->run($request);
        }, BadRequestException::class, null, IResponse::S401_UNAUTHORIZED);
    }

    public function testForbiddenDelete() {
        Assert::exception(function() {
            $request = new Request('v1:Schema', 'DELETE', array('action' => 'delete', 'id' => 1, 'token' => '1234'));
            $this->presenter->run($request);
        }, BadRequestException::class, null, IResponse::S403_FORBIDDEN);
    }

    public function testDelete() {
        $request = new Request('v1:Schema', 'DELETE', array('action' => 'delete', 'id' => 2, 'token' => 'abcd'));
        /** @var JsonResponse */
        $response = $this->presenter->run($request);
        $httpResponse = $this->container->getByType(IResponse::class);

        Assert::type(JsonResponse::class, $response);
        Assert::equal(IResponse::S200_OK, $httpResponse->getCode());

        Assert::count(0, $this->database->table('schemas')->where('id', 2));
        Assert::count(0, $this->database->table('seats')->where('schema_id', 2));

        Assert::exception(function() {
            $request = new Request('v1:Schema', 'GET', array('action' => 'read', 'id' => 2, 'token' => 'abcd'));
            $response = $this->presenter->run($request);
        }, BadRequestException::class);
    }

}

# Spuštění testovacích metod
run(new SchemaPresenterTest($container));
