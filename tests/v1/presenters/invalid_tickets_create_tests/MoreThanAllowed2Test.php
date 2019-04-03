<?php

use App\Tests\TestCaseWithDatabase;
use Nette\Application\BadRequestException;
use Nette\Application\IPresenterFactory;
use Nette\Application\Request;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\Presenter;
use Nette\Http\IResponse;
use Nette\Utils\DateTime;
use Tester\Assert;

$container = require __DIR__ . "/../../../bootstrap.php";

class MoreThanAllowed2Test extends TestCaseWithDatabase {

    /** @var Presenter */
    protected $presenter;

    public function setUpClass() {
        $this->setUpRequestInput(array(
            'user_id' => 2,
            'seats' => [101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112], // 10 is the limit
            'created_at' => DateTime::from("2017-01-01 00:00:00"),
            'updated_at' => DateTime::from("2017-01-01 00:00:00"),
        ));

        $this->presenter = $this->createPresenter('v1:Tickets');
    }

    public function testAlreadyReservedSeatCreate() {
        $request = new Request('v1:Tickets', 'POST', array('action' => 'create', 'token' => 'abcd'));
        
        Assert::exception(function() use ($request) {
            $response = $this->presenter->run($request);
        }, BadRequestException::class);
    }

}

# Spuštění testovacích metod
run(new MoreThanAllowed2Test($container));
