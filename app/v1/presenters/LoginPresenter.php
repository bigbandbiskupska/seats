<?php
/**
 * Created by PhpStorm.
 * User: ktulinger
 * Date: 03/09/2018
 * Time: 09:20
 */

namespace App\v1Module\Presenters;


use App\v1Module\Models\UsersModel;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Presenter;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Http\IResponse;
use Nette\Security\Passwords;
use Nette\Utils\Json;
use Nette\Utils\Random;

class LoginPresenter extends Presenter
{

    /**
     * @var UsersModel
     * @inject
     */
    public $users;

    public function actionLogin()
    {
        if ($this->getHttpRequest()->getMethod() !== 'POST') {
            throw new BadRequestException('Přihlašovat se lze pouze metodou POST.', IResponse::S405_METHOD_NOT_ALLOWED);
        }
        // TODO: test
        $params = $this->getJsonBody(['email', 'password']);
        $email = $params['email'];
        $password = $params['password'];

        $row = $this->users->findOneBy(['email' => $email]);

        if ($row === null) {
            throw new BadRequestException('Zadané údaje jsou nesprávné.', IResponse::S401_UNAUTHORIZED);
        } elseif (!Passwords::verify($password, $row['password'])) {
            throw new BadRequestException('Zadané údaje jsou nesprávné.', IResponse::S401_UNAUTHORIZED);
        }

        if (!$row['token'] || empty($row['token']) || $row['token'] === null) {
            // needs new token for authorization
            do {
                $row['token'] = $row['token'] = Random::generate(128);
                try {
                    $this->users->update($row['id'], [
                        'token' => $row['token']
                    ]);
                    break;
                } catch (UniqueConstraintViolationException $e) {
                }
            } while (true);
        }

        unset($row['password']);
        $row['roles'] = explode(",", $row['roles']);
        $this->sendJson($row);
    }

    // TODO: avoid duplication from base presenter
    protected function getJsonBody($validation = array())
    {
        $data = Json::decode($this->getHttpRequest()->getRawBody(), JSON::FORCE_ARRAY);
        foreach ($validation as $field) {
            if (!array_key_exists($field, $data)) {
                throw new BadRequestException("Parameter $field nebyl součástí požadavku i přesto, že byl očekáván", IResponse::S400_BAD_REQUEST);
            }
        }
        return $data;
    }
}