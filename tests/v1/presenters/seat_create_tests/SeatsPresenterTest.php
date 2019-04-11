<?php

use App\Tests\TestCaseWithDatabase;
use App\v1Module\Models\SeatsModel;
use Nette\Application\BadRequestException;
use Nette\Application\IPresenterFactory;
use Nette\Application\Request;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\Presenter;
use Nette\Http\IResponse;
use Tester\Assert;

$container = require __DIR__ . "/../../../bootstrap.php";

class SeatsPresenterTest extends TestCaseWithDatabase {

    /** @var Presenter */
    protected $presenter;

    public function setUpClass() {
        $this->setUpRequestInput(array(
            'x' => 100,
            'y' => 300,
            'row' => 40,
            'col' => 30,
            'state' => 1,
            'schema_id' => 1,
        ));

        $this->presenter = $this->createPresenter('v1:Seats');
    }

    public function testUnauthorizedCreate() {
        Assert::exception(function() {
            $request = new Request('v1:Seats', 'POST', array('action' => 'create'));
            $this->presenter->run($request);
        }, BadRequestException::class, null, IResponse::S401_UNAUTHORIZED);
    }

    public function testForbiddenCreate() {
        Assert::exception(function() {
            $request = new Request('v1:Seats', 'POST', array('action' => 'create', 'token' => '1234'));
            $this->presenter->run($request);
        }, BadRequestException::class, null, IResponse::S403_FORBIDDEN);
    }

    public function testCreate() {

        $request = new Request('v1:Seats', 'POST', array('action' => 'create', 'token' => 'abcd'));
        /** @var JsonResponse */
        $response = $this->presenter->run($request);
        $httpResponse = $this->container->getByType(IResponse::class);

        Assert::type(JsonResponse::class, $response);
        Assert::equal(IResponse::S200_OK, $httpResponse->getCode());
        Assert::true($response->getPayload() != null);

        $expected = [
            'x' => 100,
            'y' => 300,
            'row' => 40,
            'col' => 30,
            'state' => SeatsModel::AVAILABLE,
            'schema_id' => 1,
            'price' => 250,
            'id' => 301,
        ];
        $actual = $response->getPayload();
        Assert::equal($expected, $actual);
    }

}

# Spuštění testovacích metod
run(new SeatsPresenterTest($container));
