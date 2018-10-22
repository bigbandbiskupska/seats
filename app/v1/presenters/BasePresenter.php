<?php

namespace App\v1Module\Presenters;

use App\v1Module\Models\Users;
use App\v1Module\Security\User;
use Exception;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Presenter;
use Nette\Http\IResponse;
use Nette\Security\Identity;
use Nette\Utils\Arrays;
use Nette\Utils\DateTime;
use Nette\Utils\Json;

class BasePresenter extends Presenter
{

    /**
     * @var Users
     * @inject
     */
    public $users;

    /**
     * @var mixed
     */
    protected $token = null;

    /**
     *
     * @var User
     */
    protected $user = null;

    private function setCorsHeaders() {
        //if (in_array($this->getHttpRequest()->getHeader('Origin'), $this->allowedOrigins)) {
            $this->getHttpResponse()->setHeader('Access-Control-Allow-Origin', $this->getHttpRequest()->getHeader('Origin'));
            $this->getHttpResponse()->setHeader('Access-Control-Allow-Credentials', 'true');
            $this->getHttpResponse()->setHeader('Access-Control-Allow-Methods', 'DELETE, PUT, POST, OPTIONS');
            $this->getHttpResponse()->setHeader('Access-Control-Allow-Headers', 'Accept, Overwrite, Destination, Content-Type, Depth, User-Agent, Translate, Range, Content-Range, Timeout, X-Requested-With, If-Modified-Since, Cache-Control, Location');
            $this->getHttpResponse()->setHeader('Access-Control-Max-Age', 1728000);
            if ($this->getHttpRequest()->getMethod() == 'OPTIONS') {
                $this->terminate();
            }
        //}
    }

    public function startup()
    {
        parent::startup();

        $this->setCorsHeaders();

        $this->token = $this->getParameter('token', null);
        if (($user = $this->users->findOneBy(['token' => $this->token])) === null) {
            $this->error('Uživatel není přihlášen.', IResponse::S401_UNAUTHORIZED);
        }

        $this->users->update($user['id'], [
            'last_click_at' => new DateTime(),
            'ip_address' => $_SERVER['REMOTE_ADDR'],
        ]);

        $this->user = new User($user['email'], explode(',', $user['roles']), $user);
    }

    /**
     * Return the actual logged user or {@code null}
     *
     * @return Identity
     */
    public function getUser()
    {
        return $this->user;
    }

    protected function notFoundException($message = NULL)
    {
        $this->error($message);
    }

    protected function forbiddenException($message = NULL)
    {
        throw new ForbiddenRequestException;
    }

    public function ensureRoles($roles = array())
    {
        if (!is_array($roles)) {
            $roles = array($roles);
        }

        $allowed = false;
        foreach($roles as $role) {
            $allowed = $allowed || $this->user->isInRole($role);
        };

        if (!$allowed) {
            $this->error('Uživatel není oprávněn vidět tento obsah.', IResponse::S403_FORBIDDEN);
        }
    }

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
