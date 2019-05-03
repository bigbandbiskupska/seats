<?php

namespace App\v1Module\Presenters;

use App\v1Module\Models\CheckpointsModel;

class SchemaHistoryPresenter extends BasePresenter
{

    /**
     * @var CheckpointsModel
     * @inject
     */
    public $checkpoints;

    public function actionRead($schema_id)
    {
        $this->ensureRoles(['user']);
        $this->sendJson($this->checkpoints->findSchemaHistory($schema_id));
    }
}
