<?php

namespace App\v1Module\Presenters;

use App\v1Module\Models\Users;

class UserTicketsPresenter extends BasePresenter
{

    /**
     * @var Users
     * @inject
     */
    public $users;

    public function actionRead($user_id)
    {
        $this->ensureRoles(['user']);
        $this->sendJson($this->users->findTickets($user_id));
    }

}
