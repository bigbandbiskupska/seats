<?php

namespace App\v1Module\Presenters;

use App\v1Module\Models\CheckpointsModel;

class UserHistoryPresenter extends BasePresenter
{

    /**
     * @var CheckpointsModel
     * @inject
     */
    public $checkpoints;

    public function actionRead($user_id)
    {
        $this->ensureRoles(['user']);
        $this->sendJson($this->checkpoints->findUserHistory($user_id));
    }
}
