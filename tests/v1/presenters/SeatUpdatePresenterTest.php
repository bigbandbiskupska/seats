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

class SeatUpdatePresenterTest extends TestCaseWithDatabase {

    /** @var Presenter */
    protected $presenter;

    public function setUpClass() {
        $this->setUpRequestInput(array(
            'x' => 1304,
            'y' => 223,
            'row' => 70594,
            'col' => 34436,
            'price' => 1234,
            'state' => Seats::RESERVED,
        ));

        $this->presenter = $this->createPresenter('v1:Seat');
    }

    public function testUnauthorizedUpdate() {
        Assert::exception(function() {
            $request = new Request('v1:Seat', 'PUT', array('action' => 'update', 'id' => 20));
            $this->presenter->run($request);
        }, BadRequestException::class, null, IResponse::S401_UNAUTHORIZED);
    }

    public function testForbiddenUpdate() {
        Assert::exception(function() {
            $request = new Request('v1:Seat', 'PUT', array('action' => 'update', 'id' => 20, 'token' => '1234'));
            $this->presenter->run($request);
        }, BadRequestException::class, null, IResponse::S403_FORBIDDEN);
    }

    public function testUpdate() {
        $request = new Request('v1:Seat', 'PUT', array('action' => 'update', 'id' => 20, 'token' => 'abcd'));

        $response = $this->presenter->run($request);
        $httpResponse = $this->container->getByType(IResponse::class);

        Assert::type(JsonResponse::class, $response);
        Assert::equal(IResponse::S200_OK, $httpResponse->getCode());
        Assert::true($response->getPayload() != null);

        $expected = [
            'x' => 1304,
            'y' => 223,
            'row' => 70594,
            'col' => 34436,
            'schema_id' => 1,
            'id' => 20,
            'price' => 1234,
            'state' => Seats::RESERVED,
        ];
        $actual = $response->getPayload();
        Assert::equal($expected, $actual);
    }

}

# Spuštění testovacích metod
run(new SeatUpdatePresenterTest($container));
