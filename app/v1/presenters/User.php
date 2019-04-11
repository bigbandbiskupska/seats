<?php

namespace App\v1Module\Presenters;

use App\v1Module\Models\UsersModel;

class UserPresenter extends BasePresenter
{

    /**
     * @var UsersModel
     * @inject
     */
    public $users;

    public function actionRead($id)
    {
        $this->ensureRoles(['user']);
        $this->sendJson($this->users->find($id));
    }

    public function actionUpdate($id)
    {
        $this->ensureRoles(['administrator']);
        $this->sendJson($this->users->update($id, $this->getJsonBody(['name', 'surname', 'email', 'ip_address'])));
    }

    public function actionDelete($id)
    {
        $this->ensureRoles(['administrator']);
        $this->users->delete($id);
        $this->sendJson([]);
    }

    // TODO: user/1/tickets

}
