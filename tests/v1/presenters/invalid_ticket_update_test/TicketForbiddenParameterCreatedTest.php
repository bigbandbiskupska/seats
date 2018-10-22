<?php

use App\Tests\BaseTestCase;
use App\v1Module\Models\Seats;
use Nette\Application\BadRequestException;
use Nette\Application\Request;
use Nette\Application\UI\Presenter;
use Nette\Http\IResponse;
use Nette\Utils\DateTime;
use Tester\Assert;

$container = require __DIR__ . "/../../../bootstrap.php";

class TicketForbiddenParameterCreatedTest extends BaseTestCase {

    /** @var Presenter */
    protected $presenter;

    public function setUp() {
        $this->database->table('tickets')->insert([
            'user_id' => 1,
            'note' => '007',
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

        $this->setUpRequestInput(array(
            'note' => 'test',
            'created_at' => DateTime::from("2017-01-01 00:00:00"), // invalid parameter (can't change date)
        ));


        $this->presenter = $this->createPresenter('v1:Ticket');
    }

    public function testUpdate() {
        Assert::exception(function() {
            $request = new Request('v1:Ticket', 'PUT', array('action' => 'update', 'id' => 1, 'token' => 'abcd'));
            $this->presenter->run($request);
        }, BadRequestException::class, null, IResponse::S400_BAD_REQUEST);
    }
}

# Spuštění testovacích metod
run(new TicketForbiddenParameterCreatedTest($container));
