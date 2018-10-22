<?php

use App\Tests\BaseTestCase;
use App\v1Module\Models\Seats;
use Nette\Application\BadRequestException;
use Nette\Application\Request;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\Presenter;
use Nette\Http\IResponse;
use Tester\Assert;

$container = require __DIR__ . "/../../../bootstrap.php";

class SeatForbiddenParameterIdTest extends BaseTestCase {

    /** @var Presenter */
    protected $presenter;

    public function setUp() {
        $this->setUpRequestInput(array(
            'x' => 1304,
            'y' => 223,
            'row' => 70594,
            'col' => 34436,
            'id' => 25, // invalid parameter (can't change id)
            'price' => 1234,
            'state' => Seats::RESERVED,
        ));

        $this->presenter = $this->createPresenter('v1:Seat');
    }

    public function testUpdate() {
        Assert::exception(function() {
            $request = new Request('v1:Seat', 'PUT', array('action' => 'update', 'id' => 20, 'token' => 'abcd'));
            $this->presenter->run($request);
        }, BadRequestException::class, null, IResponse::S400_BAD_REQUEST);
    }
}

# Spuštění testovacích metod
run(new SeatForbiddenParameterIdTest($container));
