<?php

use App\Tests\BaseTestCase;
use App\v1Module\Models\Seats;
use Nette\Application\BadRequestException;
use Nette\Application\IPresenterFactory;
use Nette\Application\Request;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\Presenter;
use Nette\Http\IResponse;
use Nette\Utils\DateTime;
use Tester\Assert;

$container = require __DIR__ . "/../../../bootstrap.php";

class MoreThanAllowed3Test extends BaseTestCase {

    /** @var Presenter */
    protected $presenter;

    public function setUpClass() {
        $this->database->table('tickets')->insert([
            'user_id' => 1,
            'note' => '007',
            'confirmed' => 0,
            'created_at' => DateTime::from("2017-01-01 00:00:00"),
            'updated_at' => DateTime::from("2017-01-01 00:00:00"),
        ]);
        $this->database->table('reservations')->insert(
            [['seat_id' => 1, 'ticket_id' => 1, 'note' => '007'],
                ['seat_id' => 2, 'ticket_id' => 1, 'note' => '007'],
                ['seat_id' => 3, 'ticket_id' => 1, 'note' => '007'],
                ['seat_id' => 4, 'ticket_id' => 1, 'note' => '007'],
                ['seat_id' => 5, 'ticket_id' => 1, 'note' => '007']
            ]);
        $this->database->table('seats')->where('id', [1, 2, 3, 4, 5])->update([
            'state' => Seats::RESERVED
        ]);
    }

    public function setUp() {
        $this->setUpRequestInput(array(
            'user_id' => 1,
            'seats' => [6, 7, 8], // 5 is the limit and is already reserved
            'created_at' => DateTime::from("2017-01-01 00:00:00"),
            'updated_at' => DateTime::from("2017-01-01 00:00:00"),
        ));

        $factory = $this->container->getByType(IPresenterFactory::class);

        $this->presenter = $factory->createPresenter('v1:Tickets');
        $this->presenter->autoCanonicalize = false;
    }

    public function testAlreadyReservedSeatCreate() {
        $request = new Request('v1:Tickets', 'POST', array('action' => 'create', 'token' => 'abcd'));

        Assert::exception(function() use ($request) {
            $response = $this->presenter->run($request);
        }, BadRequestException::class);
    }

}

# Spuštění testovacích metod
run(new MoreThanAllowed3Test($container));
