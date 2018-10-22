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

$container = require __DIR__ . "/../../bootstrap.php";

class TicketPresenterTest extends BaseTestCase {

    /** @var Presenter */
    protected $presenter;

    public function setUpClass() {
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

        $this->database->table('tickets')->insert([
            'user_id' => 1,
            'note' => '008',
            'created_at' => DateTime::from("2017-01-01 00:00:00"),
            'updated_at' => DateTime::from("2017-01-01 00:00:00"),
        ]);
        $this->database->table('reservations')->insert([
            ['seat_id' => 6, 'ticket_id' => 2, 'note' => '008'],
            ['seat_id' => 7, 'ticket_id' => 2, 'note' => '008'],
            ['seat_id' => 8, 'ticket_id' => 2, 'note' => '008'],
            ['seat_id' => 9, 'ticket_id' => 2, 'note' => '008'],
            ['seat_id' => 10, 'ticket_id' => 2, 'note' => '008']
        ]);
        $this->database->table('seats')->where('id', [6, 7, 8, 9, 10])->update([
            'state' => Seats::RESERVED
        ]);

        $this->database->table('tickets')->insert([
            'user_id' => 1,
            'note' => '009',
            'created_at' => DateTime::from("2017-01-01 00:00:00"),
            'updated_at' => DateTime::from("2017-01-01 00:00:00"),
        ]);
        $this->database->table('reservations')->insert([
            ['seat_id' => 20, 'ticket_id' => 3, 'note' => '009'],
            ['seat_id' => 21, 'ticket_id' => 3, 'note' => '009'],
            ['seat_id' => 22, 'ticket_id' => 3, 'note' => '009']
        ]);
        $this->database->table('seats')->where('id', [20, 21, 22])->update([
            'state' => Seats::RESERVED
        ]);
    }

    public function setUp() {
        $this->presenter = $this->createPresenter('v1:Ticket');
    }

    public function testUnauthorizedRead() {
        Assert::exception(function() {
            $request = new Request('v1:Ticket', 'GET', array('action' => 'read', 'id' => 1));
            $this->presenter->run($request);
        }, BadRequestException::class, null, IResponse::S401_UNAUTHORIZED);
    }

    public function testForbiddenRead() {
        Assert::exception(function() {
            $request = new Request('v1:Ticket', 'GET', array('action' => 'read', 'id' => 1, 'token' => '1234'));
            $this->presenter->run($request);
        }, BadRequestException::class, null, IResponse::S403_FORBIDDEN);
    }

    public function testRead() {
        $request = new Request('v1:Ticket', 'GET', array('action' => 'read', 'id' => 1, 'token' => 'abcd'));
        /** @var JsonResponse */
        $response = $this->presenter->run($request);
        $httpResponse = $this->container->getByType(IResponse::class);

        Assert::type(JsonResponse::class, $response);
        Assert::equal(IResponse::S200_OK, $httpResponse->getCode());
        Assert::true($response->getPayload() != null);

        Assert::equal([
            'id' => 1,
            'user_id' => 1,
            'note' => '007',
            'confirmed' => 0,
            'created_at' => DateTime::from("2017-01-01 00:00:00"),
            'updated_at' => DateTime::from("2017-01-01 00:00:00"),
            'seats' => [
                [
                    'id' => 1,
                    'schema_id' => 1,
                    'x' => 1,
                    'y' => 1,
                    'row' => 1,
                    'col' => 1,
                    'price' => 250,
                    'state' => Seats::RESERVED,
                ],
                [
                    'id' => 2,
                    'schema_id' => 1,
                    'x' => 1,
                    'y' => 2,
                    'row' => 1,
                    'col' => 2,
                    'price' => 250,
                    'state' => Seats::RESERVED,
                ],
                [
                    'id' => 3,
                    'schema_id' => 1,
                    'x' => 1,
                    'y' => 3,
                    'row' => 1,
                    'col' => 3,
                    'price' => 250,
                    'state' => Seats::RESERVED,
                ],
                [
                    'id' => 4,
                    'schema_id' => 1,
                    'x' => 1,
                    'y' => 4,
                    'row' => 1,
                    'col' => 4,
                    'price' => 250,
                    'state' => Seats::RESERVED,
                ],
                [
                    'id' => 5,
                    'schema_id' => 1,
                    'x' => 1,
                    'y' => 5,
                    'row' => 1,
                    'col' => 5,
                    'price' => 250,
                    'state' => Seats::RESERVED,
                ],
            ],
                ], $response->getPayload());
    }

    public function testInvalidRead() {
        Assert::exception(function () {
            $request = new Request('v1:Ticket', 'GET', array('action' => 'read', 'id' => 1001, 'token' => 'abcd'));
            $response = $this->presenter->run($request);
        }, BadRequestException::class);
    }

    public function testUnauthorizedDelete() {
        Assert::exception(function() {
            $request = new Request('v1:Ticket', 'DELETE', array('action' => 'delete', 'id' => 1));
            $this->presenter->run($request);
        }, BadRequestException::class, null, IResponse::S401_UNAUTHORIZED);
    }

    public function testForbiddenDelete() {
        Assert::exception(function() {
            $request = new Request('v1:Ticket', 'DELETE', array('action' => 'delete', 'id' => 1, 'token' => '1234'));
            $this->presenter->run($request);
        }, BadRequestException::class, null, IResponse::S403_FORBIDDEN);
    }

    public function testDelete() {
        foreach ($this->database->table('seats')->where('id', [6, 7, 8, 9, 10])->fetchPairs('id') as $seat) {
            Assert::equal(Seats::RESERVED, $seat->state);
        }
        $request = new Request('v1:Ticket', 'DELETE', array('action' => 'delete', 'id' => 2, 'token' => 'abcd'));
        /** @var JsonResponse */
        $response = $this->presenter->run($request);
        $httpResponse = $this->container->getByType(IResponse::class);

        Assert::equal(IResponse::S200_OK, $httpResponse->getCode());

        Assert::count(0, $this->database->table('tickets')->where('id', 2));
        Assert::count(0, $this->database->table('reservations')->where('ticket_id', 2));

        Assert::exception(function() {
            $request = new Request('v1:Ticket', 'GET', array('action' => 'read', 'id' => 2, 'token' => 'abcd'));
            $response = $this->presenter->run($request);
        }, BadRequestException::class);

        Assert::count(0, $this->database->table('reservations')->where('ticket_id', 2));

        foreach ($this->database->table('seats')->where('id', [6, 7, 8, 9, 10])->fetchPairs('id') as $seat) {
            Assert::equal(Seats::AVAILABLE, $seat->state);
        }
    }

    public function testInvalidDelete() {
        Assert::exception(function() {
            $request = new Request('v1:Ticket', 'DELETE', array('action' => 'delete', 'id' => 4, 'token' => 'abcd'));
            /** @var JsonResponse */
            $response = $this->presenter->run($request);
            $httpResponse = $this->container->getByType(IResponse::class);
        }, BadRequestException::class);
    }

}

# Spuštění testovacích metod
run(new TicketPresenterTest($container));
