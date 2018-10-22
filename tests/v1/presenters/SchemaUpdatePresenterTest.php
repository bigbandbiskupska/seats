<?php

use App\Tests\BaseTestCase;
use Nette\Application\BadRequestException;
use Nette\Application\IPresenterFactory;
use Nette\Application\Request;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\Presenter;
use Nette\Http\IResponse;
use Tester\Assert;

$container = require __DIR__ . "/../../bootstrap.php";

class SchemaUpdatePresenterTest extends BaseTestCase {

    /** @var Presenter */
    protected $presenter;

    public function setUp() {
        $this->setUpRequestInput(array(
            'name' => 'Nový testovací koncert',
            'price' => 1000,
            'limit' => 100,
        ));

        $this->presenter = $this->createPresenter('v1:Schema');
    }

    public function testUnauthorizedUpdate() {
        Assert::exception(function() {
            $request = new Request('v1:Schema', 'PUT', array('action' => 'update', 'id' => 1));
            $this->presenter->run($request);
        }, BadRequestException::class, null, IResponse::S401_UNAUTHORIZED);
    }

    public function testForbiddenUpdate() {
        Assert::exception(function() {
            $request = new Request('v1:Schema', 'PUT', array('action' => 'update', 'id' => 1, 'token' => '1234'));
            $this->presenter->run($request);
        }, BadRequestException::class, null, IResponse::S403_FORBIDDEN);
    }

    public function testUpdate() {
        $request = new Request('v1:Schema', 'PUT', array('action' => 'update', 'id' => 1, 'token' => 'abcd'));

        $response = $this->presenter->run($request);
        $httpResponse = $this->container->getByType(IResponse::class);

        Assert::type(JsonResponse::class, $response);
        Assert::equal(IResponse::S200_OK, $httpResponse->getCode());
        Assert::true($response->getPayload() != null);

        $expected = [
            'id' => 1,
            'name' => 'Nový testovací koncert',
            'price' => 1000,
            'hidden' => 1,
            'locked' => 0,
            'limit' => 100,
        ];
        $actual = $response->getPayload();
        Assert::equal($expected, $actual);
    }

}

# Spuštění testovacích metod
run(new SchemaUpdatePresenterTest($container));
