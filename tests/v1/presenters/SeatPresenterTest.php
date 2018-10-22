<?php

use App\Tests\BaseTestCase;
use App\v1Module\Models\Seats;
use Nette\Application\BadRequestException;
use Nette\Application\Request;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\Presenter;
use Nette\Http\IResponse;
use Tester\Assert;

$container = require __DIR__ . "/../../bootstrap.php";

class SeatPresenterTest extends BaseTestCase {

    /** @var Presenter */
    protected $presenter;

    public function setUp() {
        $this->presenter = $this->createPresenter('v1:Seat');
    }

    public function testUnauthorizedRead() {
        Assert::exception(function() {
            $request = new Request('v1:Seat', 'GET', array('action' => 'read', 'id' => 20));
            $this->presenter->run($request);
        }, BadRequestException::class, null, IResponse::S401_UNAUTHORIZED);
    }

    public function testForbiddenRead() {
        Assert::exception(function() {
            $request = new Request('v1:Seat', 'GET', array('action' => 'read', 'id' => 20, 'token' => '1234'));
            $this->presenter->run($request);
        }, BadRequestException::class, null, IResponse::S403_FORBIDDEN);
    }

    public function testRead() {
        $request = new Request('v1:Seat', 'GET', array('action' => 'read', 'id' => 20, 'token' => 'abcd'));
        /** @var JsonResponse */
        $response = $this->presenter->run($request);
        $httpResponse = $this->container->getByType(IResponse::class);

        Assert::type(JsonResponse::class, $response);
        Assert::equal(IResponse::S200_OK, $httpResponse->getCode());
        Assert::true($response->getPayload() != null);

        Assert::equal([
            'id' => 20,
            'x' => 2,
            'y' => 10,
            'row' => 2,
            'col' => 10,
            'schema_id' => 1,
            'price' => 250,
            'state' => Seats::AVAILABLE,
                ], $response->getPayload());
    }


    public function testInvalidRead() {
        Assert::exception(function() {
            $request = new Request('v1:Seat', 'GET', array('action' => 'read', 'id' => 1001, 'token' => 'abcd'));
            /** @var JsonResponse */
            $response = $this->presenter->run($request);
        }, BadRequestException::class);
    }

    public function testUnauthorizedDelete() {
        Assert::exception(function() {
            $request = new Request('v1:Seat', 'DELETE', array('action' => 'delete', 'id' => 20));
            $this->presenter->run($request);
        }, BadRequestException::class, null, IResponse::S401_UNAUTHORIZED);
    }

    public function testForbiddenDelete() {
        Assert::exception(function() {
            $request = new Request('v1:Seat', 'DELETE', array('action' => 'delete', 'id' => 20, 'token' => '1234'));
            $this->presenter->run($request);
        }, BadRequestException::class, null, IResponse::S403_FORBIDDEN);
    }

    public function testDelete() {
        $request = new Request('v1:Seat', 'DELETE', array('action' => 'delete', 'id' => 23, 'token' => 'abcd'));
        /** @var JsonResponse */
        $response = $this->presenter->run($request);
        $httpResponse = $this->container->getByType(IResponse::class);

        Assert::equal(IResponse::S200_OK, $httpResponse->getCode());

        Assert::count(0, $this->database->table('seats')->where('id', 23));

        Assert::exception(function() {
            $request = new Request('v1:Seat', 'GET', array('action' => 'read', 'id' => 23, 'token' => 'abcd'));
            $response = $this->presenter->run($request);
        }, BadRequestException::class);
    }
}

# Spuštění testovacích metod
run(new SeatPresenterTest($container));
