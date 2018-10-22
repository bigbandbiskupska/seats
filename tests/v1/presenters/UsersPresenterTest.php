<?php

use App\Tests\BaseTestCase;
use Nette\Application\BadRequestException;
use Nette\Application\Request;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\Presenter;
use Nette\Http\IResponse;
use Nette\Utils\DateTime;
use Tester\Assert;

$container = require __DIR__ . "/../../bootstrap.php";

class UsersPresenterTest extends BaseTestCase {

    /** @var Presenter */
    protected $presenter;

    public function setUp() {
        $this->setUpRequestInput(array(
            'name' => 'New',
            'surname' => 'User',
            'email' => 'new@user.com',
            'password' => 'yyy',
            'ip_address' => '7.7.7.7',
            'expires_at' => DateTime::from("2017-01-01 00:00:00")
        ));

        $this->presenter = $this->createPresenter('v1:Users');
    }

    public function testUnauthorizedCreate() {
        Assert::exception(function() {
            $request = new Request('v1:Users', 'POST', array('action' => 'create'));
            $this->presenter->run($request);
        }, BadRequestException::class, null, IResponse::S401_UNAUTHORIZED);
    }

    public function testForbiddenCreate() {
        Assert::exception(function() {
            $request = new Request('v1:Users', 'POST', array('action' => 'create', 'token' => '1234'));
            $this->presenter->run($request);
        }, BadRequestException::class, null, IResponse::S403_FORBIDDEN);
    }

    public function testCreate() {

        $request = new Request('v1:Users', 'POST', array('action' => 'create', 'token' => 'abcd'));
        /** @var JsonResponse */
        $response = $this->presenter->run($request);
        $httpResponse = $this->container->getByType(IResponse::class);

        Assert::type(JsonResponse::class, $response);
        Assert::equal(IResponse::S200_OK, $httpResponse->getCode());
        Assert::true($response->getPayload() != null);

        $expected = [
            'id' => 4,
            'name' => 'New',
            'surname' => 'User',
            'email' => 'new@user.com',
            'password' => 'yyy',
            'token' => '',
            'roles' => 'user',
            'expires_at' => DateTime::from("2017-01-01 00:00:00"),
            'ip_address' => '7.7.7.7',
        ];
        $actual = $response->getPayload();
        Assert::equal($expected, $actual);

        Assert::count(2, $this->database->table('allowed_limit')->where('user_id', 4));
        foreach ($this->database->table('allowed_limit')->where('user_id', 4) as $limit) {
            Assert::equal(5, $limit->limit);
        }
    }

}

# Spuštění testovacích metod
run(new UsersPresenterTest($container));
