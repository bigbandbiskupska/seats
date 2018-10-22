<?php

use App\Tests\BaseTestCase;
use App\v1Module\Models\Seats;
use Nette\Application\BadRequestException;
use Nette\Application\IPresenterFactory;
use Nette\Application\Request;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\Presenter;
use Nette\Http\IResponse;
use Tester\Assert;
use Nette\Utils\DateTime;

$container = require __DIR__ . "/../../bootstrap.php";

class TicketUpdatePresenterTest extends BaseTestCase {

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
            'confirmed' => 1
        ));

        $this->presenter = $this->createPresenter('v1:Ticket');
    }

    public function testUnauthorizedUpdate() {
        Assert::exception(function() {
            $request = new Request('v1:Ticket', 'PUT', array('action' => 'update', 'id' => 1));
            $this->presenter->run($request);
        }, BadRequestException::class, null, IResponse::S401_UNAUTHORIZED);
    }

    public function testForbiddenUpdate() {
        Assert::exception(function() {
            $request = new Request('v1:Ticket', 'PUT', array('action' => 'update', 'id' => 1, 'token' => 'qwer'));
            $this->presenter->run($request);
        }, BadRequestException::class, null, IResponse::S403_FORBIDDEN);
    }

    public function testUpdate() {
        $request = new Request('v1:Ticket', 'PUT', array('action' => 'update', 'id' => 1, 'token' => 'abcd'));

        $response = $this->presenter->run($request);
        $httpResponse = $this->container->getByType(IResponse::class);

        Assert::type(JsonResponse::class, $response);
        Assert::equal(IResponse::S200_OK, $httpResponse->getCode());
        Assert::true($response->getPayload() != null);

        $actual = $response->getPayload();
        Assert::equal(1, $actual['confirmed']);
    }

}

# Spuštění testovacích metod
run(new TicketUpdatePresenterTest($container));
