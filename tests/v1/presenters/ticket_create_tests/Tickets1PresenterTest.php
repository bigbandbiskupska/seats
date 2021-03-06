<?php

use App\Tests\TestCaseWithDatabase;
use App\v1Module\Models\SeatsModel;
use Nette\Application\BadRequestException;
use Nette\Application\IPresenterFactory;
use Nette\Application\Request;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\Presenter;
use Nette\Http\IResponse;
use Nette\Utils\DateTime;
use Tester\Assert;

$container = require __DIR__ . "/../../../bootstrap.php";

class Tickets1PresenterTest extends TestCaseWithDatabase {

    /** @var Presenter */
    protected $presenter;

    public function setUpClass()
    {
        $this->setUpRequestInput(array(
            'user_id' => 1,
            'seats' => [1, 2, 3, 4, 5],
            'note' => '007',
            'created_at' => '2017-01-01 00:00:00',
            'updated_at' => '2017-01-01 00:00:00',
        ));

        $this->presenter = $this->createPresenter('v1:Tickets');
    }

    public function testUnauthorizedCreate() {
        Assert::exception(function() {
            $request = new Request('v1:Tickets', 'POST', array('action' => 'create'));
            $this->presenter->run($request);
        }, BadRequestException::class, null, IResponse::S401_UNAUTHORIZED);
    }

    public function testForbiddenCreate() {
        Assert::exception(function() {
            $request = new Request('v1:Tickets', 'POST', array('action' => 'create', 'token' => '1234'));
            $this->presenter->run($request);
        }, BadRequestException::class, null, IResponse::S403_FORBIDDEN);
    }

    public function testCreateForUser1() {
        foreach ($this->database->table('seats')->where('id', [1, 2, 3, 4, 5])->fetchPairs('id') as $seat) {
            Assert::equal(SeatsModel::AVAILABLE, $seat->state);
        }

        $request = new Request('v1:Tickets', 'POST', array('action' => 'create', 'token' => 'abcd'));
        /** @var JsonResponse */
        $response = $this->presenter->run($request);
        $httpResponse = $this->container->getByType(IResponse::class);

        Assert::type(JsonResponse::class, $response);
        Assert::equal(IResponse::S200_OK, $httpResponse->getCode());
        Assert::true($response->getPayload() != null);

        $expected = [
            'user_id' => 1,
            'note' => '007',
            'confirmed' => 0,
            'created_at' => DateTime::from("2017-01-01 00:00:00"),
            'updated_at' => DateTime::from("2017-01-01 00:00:00"),
        ];


        $actual = $response->getPayload();
        dump($actual);
        Assert::equal($expected['user_id'], $actual['user_id']);
        // TODO: verify the timestamp is a good idea to return
        Assert::equal($expected['created_at']->getTimestamp() * 1000, $actual['created_at']);
        Assert::equal($expected['updated_at']->getTimestamp() * 1000, $actual['updated_at']);
        Assert::equal([1, 2, 3, 4, 5], $actual['seats']);
        Assert::equal($expected['note'], $actual['note']);

        foreach ($this->database->table('reservations')->fetchPairs('seat_id') as $reservation) {
            Assert::contains($reservation->seat->id, [1, 2, 3, 4, 5]);
            Assert::equal('007', $reservation->note);
        }
        foreach ($this->database->table('seats')->where('id', [1, 2, 3, 4, 5])->fetchPairs('id') as $seat) {
            Assert::equal(SeatsModel::RESERVED, $seat->state);
        }
    }

}

# Spuštění testovacích metod
run(new Tickets1PresenterTest($container));
