<?php

namespace App\v1Module\Presenters;

use App\v1Module\Models\Users;
use Latte\Engine;
use Nette\Mail\IMailer;
use Nette\Mail\Message;
use Tulinkry\Services\ParameterService;

class UsersPresenter extends BasePresenter
{
    /**
     * @var Users
     * @inject
     */
    public $users;

    /**
     * @var IMailer
     * @inject
     */
    public $mailer;

    /**
     * @var ParameterService
     * @inject
     */
    public $parameters;

    public function actionCreate()
    {
        $this->ensureRoles(['administrator']);
        $body = $this->getJsonBody(['name', 'surname', 'email']);
        if (!isset($body['password']) || empty($body['password'])) {
            // this is a new user without a password
            // so we need to send him an email about how to reset the password

            $latte = new Engine();
            $params = [
                'new_password_link' => sprintf('%s?redirect=%s',
                    $this->parameters->params['api']['users']['forgotten_password'],
                    urlencode($this->parameters->params['api']['tickets']['base'])),
                'name' => $body['name'],
                'surname' => $body['surname'],
            ];


            $mail = new Message();
            $mail->setFrom('vstupenky@bigbandbiskupska.cz')
                ->addTo($body['email'])
                ->setSubject('Vítejte ' . $body['name'] . ' v aplikaci na rezervaci lístků')
                ->setHtmlBody($latte->renderToString(__DIR__ . '/../templates/mail/new_user.latte', $params));
            $this->mailer->send($mail);
            $body['password'] = null;
        }
        $this->sendJson($this->users->create($body));
    }


    public function actionRead()
    {
        // TODO: test read
        // TODO: remove unneccessary fields
        $this->ensureRoles(['administrator']);
        $this->sendJson($this->users->all());
    }
}
