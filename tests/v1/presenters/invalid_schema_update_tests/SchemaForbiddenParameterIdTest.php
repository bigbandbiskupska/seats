<?php

use App\Tests\TestCaseWithDatabase;
use Nette\Application\BadRequestException;
use Nette\Application\IPresenterFactory;
use Nette\Application\Request;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\Presenter;
use Nette\Http\IResponse;
use Tester\Assert;

$container = require __DIR__ . "/../../../bootstrap.php";

class SchemaForbiddenParameterIdTest extends TestCaseWithDatabase {

    /** @var Presenter */
    protected $presenter;

    public function setUpClass() {
        $this->setUpRequestInput(array(
            'id' => 100,
            'name' => 'Nový testovací koncert',
            'price' => 1000,
            'limit' => 100,
        ));

        $this->presenter = $this->createPresenter('v1:Schema');
    }

    public function testUpdate() {
        Assert::exception(function() {
            $request = new Request('v1:Schema', 'PUT', array('action' => 'update', 'id' => 1, 'token' => 'abcd'));
            $this->presenter->run($request);
        }, BadRequestException::class, null, IResponse::S400_BAD_REQUEST);
    }

}

# Spuštění testovacích metod
run(new SchemaForbiddenParameterIdTest($container));
