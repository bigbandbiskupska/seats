<?php

use App\Tests\TestCaseWithDatabase;
use Nette\Application\BadRequestException;
use Nette\Application\Request;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\Presenter;
use Nette\Http\IResponse;
use Tester\Assert;

$container = require __DIR__ . "/../../../bootstrap.php";

class SchemasPresenterTest extends TestCaseWithDatabase {

    /** @var Presenter */
    protected $presenter;

    public function setUpClass() {
        $this->setUpRequestInput(array(
            'name' => 'Nový testovací koncert',
            'price' => 1000,
            'limit' => 100,
            'seats' => []
        ));

        $this->presenter = $this->createPresenter('v1:Schemas');
    }

    public function testUnauthorizedRead() {
        Assert::exception(function() {
            $request = new Request('v1:Schemas', 'GET', array('action' => 'read'));
            $this->presenter->run($request);
        }, BadRequestException::class, null, IResponse::S401_UNAUTHORIZED);
    }

    public function testForbiddenRead() {
        Assert::exception(function() {
            $request = new Request('v1:Schemas', 'GET', array('action' => 'read', 'token' => '1234'));
            $this->presenter->run($request);
        }, BadRequestException::class, null, IResponse::S403_FORBIDDEN);
    }

    public function testRead() {
        $request = new Request('v1:Schemas', 'GET', array('action' => 'read', 'token' => 'abcd'));
        /** @var JsonResponse */
        $response = $this->presenter->run($request);
        $httpResponse = $this->container->getByType(IResponse::class);

        Assert::type(JsonResponse::class, $response);
        Assert::equal(IResponse::S200_OK, $httpResponse->getCode());
        Assert::true($response->getPayload() != null);

        Assert::equal([
            1 => [
                'id' => 1,
                'name' => 'Vánoční koncert',
                'price' => 250,
                'hidden' => 1,
                'locked' => 0,
                'limit' => 5,
                'seats' => array_values($this->database->table('seats')->where('schema_id', 1)->fetchPairs('id', 'id'))
            ],
            2 => [
                'id' => 2,
                'name' => 'Velikonoční koncert',
                'price' => 100,
                'hidden' => 0,
                'locked' => 1,
                'limit' => 5,
                'seats' => array_values($this->database->table('seats')->where('schema_id', 2)->fetchPairs('id', 'id'))
            ]
        ], $response->getPayload());
    }

    public function testUnauthorizedCreate() {
        Assert::exception(function() {
            $request = new Request('v1:Schemas', 'POST', array('action' => 'create'));
            $this->presenter->run($request);
        }, BadRequestException::class, null, IResponse::S401_UNAUTHORIZED);
    }

    public function testForbiddenCreate() {
        Assert::exception(function() {
            $request = new Request('v1:Schemas', 'POST', array('action' => 'create', 'token' => '1234'));
            $this->presenter->run($request);
        }, BadRequestException::class, null, IResponse::S403_FORBIDDEN);
    }

    public function testCreate() {

        $request = new Request('v1:Schemas', 'POST', array('action' => 'create', 'token' => 'abcd'));
        /** @var JsonResponse */
        $response = $this->presenter->run($request);
        $httpResponse = $this->container->getByType(IResponse::class);

        Assert::type(JsonResponse::class, $response);
        Assert::equal(IResponse::S200_OK, $httpResponse->getCode());
        Assert::true($response->getPayload() != null);

        $expected = [
            'id' => 3,
            'name' => 'Nový testovací koncert',
            'price' => 1000,
            'hidden' => 0,
            'locked' => 1,
            'limit' => 100,
            'seats' => [],
        ];
        $actual = $response->getPayload();
        Assert::equal($expected, $actual);

        Assert::count(3, $this->database->table('allowed_limit')->where('schema_id', 3));
        foreach ($this->database->table('allowed_limit')->where('schema_id', 3) as $limit) {
            Assert::equal(100, $limit->limit);
        }
    }

}

# Spuštění testovacích metod
run(new SchemasPresenterTest($container));
