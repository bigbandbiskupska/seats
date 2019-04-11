<?php

use App\Tests\TestCaseWithDatabase;
use App\v1Module\Models\SeatsModel;
use Nette\Application\BadRequestException;
use Nette\Application\Request;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\Presenter;
use Nette\Http\IResponse;
use Nette\Utils\DateTime;
use Tester\Assert;

$container = require __DIR__ . "/../../../bootstrap.php";

class SeatOnTheTicketTest extends TestCaseWithDatabase {

    /** @var Presenter */
    protected $presenter;

    public function setUpClass() {
        $this->presenter = $this->createPresenter('v1:Seat');

        $this->database->table('tickets')->insert([
            'user_id' => 1,
            'note' => '009',
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
                'state' => SeatsModel::RESERVED
            ]);
        }
    }

    public function testDelete() {
        Assert::exception(function() {
            $request = new Request('v1:Seat', 'DELETE', array('action' => 'delete', 'id' => 20, 'token' => 'abcd'));
            /** @var JsonResponse */
            $response = $this->presenter->run($request);
            $httpResponse = $this->container->getByType(IResponse::class);

        }, BadRequestException::class, null, IResponse::S400_BAD_REQUEST);

        Assert::count(1, $this->database->table('seats')->where('id', 20));
        Assert::count(1, $this->database->table('reservations')->where('seat_id', 20));
        Assert::count(1, $this->database->table('tickets')->where('id', 1));
        foreach ($this->database->table('seats')->where('id', [20, 21, 22])->fetchPairs('id') as $seat) {
            Assert::equal(SeatsModel::RESERVED, $seat->state);
        }
    }
}

# Spuštění testovacích metod
run(new SeatOnTheTicketTest($container));
