<?php

namespace App\v1Module\Presenters;

use App\v1Module\Models\UsersModel;

class UserTicketsPresenter extends BasePresenter
{

    /**
     * @var UsersModel
     * @inject
     */
    public $users;

    public function actionRead($user_id)
    {
        $this->ensureRoles(['user']);
        $this->sendJson($this->users->findTickets($user_id));
    }

}
