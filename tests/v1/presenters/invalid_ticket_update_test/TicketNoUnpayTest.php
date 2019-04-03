<?php

use App\Tests\TestCaseWithDatabase;
use App\v1Module\Models\Seats;
use Nette\Application\Request;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\Presenter;
use Nette\Http\IResponse;
use Nette\Utils\DateTime;
use Tester\Assert;

$container = require __DIR__ . "/../../../bootstrap.php";

class TicketNoUnpayTest extends TestCaseWithDatabase
{

    /** @var Presenter */
    protected $presenter;

    public function setUpClass()
    {
        $this->database->table('tickets')->insert([
            'user_id' => 3,
            'schema_id' => 1,
            'note' => '007',
            'confirmed' => true,
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
            'confirmed' => false,
        ));

        $this->presenter = $this->createPresenter('v1:Ticket');
    }

    public function testUpdateAdmin() {
        $request = new Request('v1:Ticket', 'PUT', array('action' => 'update', 'id' => 1, 'token' => 'abcd'));
        $response = $this->presenter->run($request);
        $httpResponse = $this->container->getByType(IResponse::class);

        Assert::type(JsonResponse::class, $response);
        Assert::equal(IResponse::S200_OK, $httpResponse->getCode());
        Assert::true($response->getPayload() != null);

        Assert::equal(0, $this->database->table('tickets')->get(1)->confirmed);
    }
}

# Spuštění testovacích metod
run(new TicketNoUnpayTest($container));
