<?php

use App\Tests\TestCaseWithDatabase;
use App\v1Module\Models\Seats;
use Nette\Application\BadRequestException;
use Nette\Application\Request;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\Presenter;
use Nette\Http\IResponse;
use Nette\Utils\DateTime;
use Tester\Assert;

$container = require __DIR__ . "/../../../bootstrap.php";

class TicketConfirmedTest extends TestCaseWithDatabase {

    /** @var Presenter */
    protected $presenter;

    public function setUpClass() {
        $this->database->table('tickets')->insert([
            'user_id' => 1,
            'note' => '007',
            'schema_id' => 1,
            'confirmed' => true,
            'created_at' => DateTime::from("2017-01-01 00:00:00"),
            'updated_at' => DateTime::from("2017-01-01 00:00:00"),
        ]);
        $this->database->table('reservations')->insert([
            ['seat_id' => 20, 'ticket_id' => 1, 'note' => '009'],
            ['seat_id' => 21, 'ticket_id' => 1, 'note' => '009'],
            ['seat_id' => 22, 'ticket_id' => 1, 'note' => '009']
        ]);
        foreach ($this->database->table('seats')->where('id', [20, 21, 22])->fetchPairs('id') as $seat) {
            $seat->update([
                'state' => Seats::RESERVED
            ]);
        }

        $this->presenter = $this->createPresenter('v1:Ticket');
    }

    public function testDelete() {
        Assert::exception(function() {
            $request = new Request('v1:Ticket', 'DELETE', array('action' => 'delete', 'id' => 1, 'token' => 'abcd'));
            $this->presenter->run($request);
        }, BadRequestException::class, null, IResponse::S400_BAD_REQUEST);

        Assert::count(1, $this->database->table('seats')->where('id', 20));
        Assert::count(1, $this->database->table('reservations')->where('seat_id', 20));
        Assert::count(1, $this->database->table('tickets')->where('id', 1));
        foreach ($this->database->table('seats')->where('id', [20, 21, 22])->fetchPairs('id') as $seat) {
            Assert::equal(Seats::RESERVED, $seat->state);
        }
    }

}

# Spuštění testovacích metod
run(new TicketConfirmedTest($container));