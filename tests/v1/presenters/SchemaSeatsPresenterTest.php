<?php

use App\Tests\TestCaseWithDatabase;
use App\v1Module\Models\Seats;
use Nette\Application\BadRequestException;
use Nette\Application\IPresenterFactory;
use Nette\Application\Request;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\Presenter;
use Nette\Http\IResponse;
use Tester\Assert;

$container = require __DIR__ . "/../../bootstrap.php";

class SchemaSeatsPresenterTest extends TestCaseWithDatabase {

    /** @var Presenter */
    protected $presenter;

    public function setUpClass() {
        $this->presenter = $this->createPresenter('v1:SchemaSeats');
    }

    public function testUnauthorizedRead() {
        Assert::exception(function() {
            $request = new Request('v1:SchemaSeats', 'GET', array('action' => 'read', 'schema_id' => 1));
            $this->presenter->run($request);
        }, BadRequestException::class, null, IResponse::S401_UNAUTHORIZED);
    }

    public function testForbiddenRead() {
        Assert::exception(function() {
            $request = new Request('v1:SchemaSeats', 'GET', array('action' => 'read', 'schema_id' => 1, 'token' => '1234'));
            $this->presenter->run($request);
        }, BadRequestException::class, null, IResponse::S403_FORBIDDEN);
    }

    public function testInvalidRead() {
        Assert::exception(function() {
            $request = new Request('v1:SchemaSeats', 'GET', array('action' => 'read', 'schema_id' => 100, 'token' => 'abcd'));
            $response = $this->presenter->run($request);
        }, BadRequestException::class);
    }

    public function testRead() {
        $request = new Request('v1:SchemaSeats', 'GET', array('action' => 'read', 'schema_id' => 1, 'token' => 'abcd'));
        /** @var JsonResponse $response */
        $response = $this->presenter->run($request);
        $httpResponse = $this->container->getByType(IResponse::class);

        Assert::type(JsonResponse::class, $response);
        Assert::equal(IResponse::S200_OK, $httpResponse->getCode());
        Assert::true($response->getPayload() != null);

        $payload = $response->getPayload();
        print_r(array_keys($payload));

        Assert::count(100, $payload, 'The schema 1 should have 100 seats.');
        foreach($payload as $id => $seat) {
            Assert::equal(1, $seat['schema_id'], 'All of the seats should have the schema_id set to 1.');
            Assert::equal(250, $seat['price'], 'All of the seats should have the price set to 250.');
            Assert::equal(Seats::AVAILABLE, $seat['state'], 'All of the seats should be available');
        }
    }
}

# Spuštění testovacích metod
run(new SchemaSeatsPresenterTest($container));
