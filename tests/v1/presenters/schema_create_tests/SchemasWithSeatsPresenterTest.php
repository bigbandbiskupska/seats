<?php

use App\Tests\BaseTestCase;
use Nette\Application\Request;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\Presenter;
use Nette\Http\IResponse;
use Tester\Assert;

$container = require __DIR__ . "/../../../bootstrap.php";

class SchemasWithPresenterTest extends BaseTestCase
{

    /** @var Presenter */
    protected $presenter;

    public function setUp()
    {
        $this->setUpRequestInput(array(
            'name' => 'Nový testovací koncert',
            'price' => 1000,
            'limit' => 100,
            'seats' => [
                [
                    [
                        'x' => 1, 'y' => 1, 'row' => 1, 'col' => 1, 'price' => 200
                    ],
                    [
                        'x' => 2, 'y' => 1, 'row' => 1, 'col' => 2, 'price' => 100
                    ],

                ],
                [
                    [
                        'x' => 1, 'y' => 2, 'row' => 2, 'col' => 1, 'price' => 400
                    ],
                    [
                        'x' => 2, 'y' => 2, 'row' => 2, 'col' => 2, 'price' => 800
                    ],

                ],
            ]
        ));

        $this->presenter = $this->createPresenter('v1:Schemas');
    }

    public function testCreate()
    {

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
            'seats' => [301, 302, 303, 304]
        ];
        $actual = $response->getPayload();
        Assert::equal($expected, $actual);

        Assert::count(3, $this->database->table('allowed_limit')->where('schema_id', 3));
        foreach ($this->database->table('allowed_limit')->where('schema_id', 3) as $limit) {
            Assert::equal(100, $limit->limit);
        }

        // TODO: test seats attributes
    }

}

# Spuštění testovacích metod
run(new SchemasWithPresenterTest($container));
