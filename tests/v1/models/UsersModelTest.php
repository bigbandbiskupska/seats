<?php

namespace App\v1Module\Models;

use App\Tests\TestCaseWithDatabase;
use Nette\Database\UniqueConstraintViolationException;
use Tester\Assert;

$container = require __DIR__ . "/../../bootstrap.php";

class UsersModelTest extends TestCaseWithDatabase
{
    public function testCreate()
    {
        /** @var UsersModel $model */
        $model = $this->container->getByType(UsersModel::class);
        $model->create(['name' => 'XXX', 'surname' => 'YYY', 'email' => 'james@bond.nothing']);

        $user = $model->findOneBy(['name' => 'XXX']);
        Assert::notEqual(null, $user);
        $user = $model->entity($user['id']);
        Assert::equal('XXX', $user['name']);
        Assert::equal('YYY', $user['surname']);
        Assert::equal('james@bond.nothing', $user['email']);
        Assert::notEqual(null, $user['token']);
        // two schemas
        Assert::count(2, $user->related('allowed_limit'));
    }

    public function testDuplicateCreate()
    {
        Assert::exception(function () {
            /** @var UsersModel $model */
            $model = $this->container->getByType(UsersModel::class);
            $model->create(['email' => 'james@bond.com']);
        }, UniqueConstraintViolationException::class);
    }
}

# Spuštění testovacích metod
run(new UsersModelTest($container));
