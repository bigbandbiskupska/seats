<?php

namespace App\v1Module\Presenters;

use App\v1Module\Models\Schemas;

class SchemaPresenter extends BasePresenter
{

    /**
     * @var Schemas
     * @inject
     */
    public $schemas;

    public function actionRead($id)
    {
        $this->ensureRoles(['user']);
        $this->sendJson($this->schemas->find($id));
    }

    public function actionUpdate($id)
    {
        $this->ensureRoles(['administrator']);
        $this->sendJson($this->schemas->update($id, $this->getJsonBody()));
    }

    public function actionDelete($id)
    {
        $this->ensureRoles(['administrator']);
        $this->schemas->delete($id);
        $this->sendJson([]);
    }

}
