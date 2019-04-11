<?php

namespace App\v1Module\Presenters;

use App\v1Module\Models\SchemasModel;
use App\v1Module\Models\SeatsModel;

class SchemaSeatsPresenter extends BasePresenter
{

    /**
     * @var SchemasModel
     * @inject
     */
    public $schemas;

    /**
     * @var SeatsModel
     * @inject
     */
    public $seats;

    public function actionRead($schema_id)
    {
        $this->ensureRoles(['user']);
        $this->sendJson($this->schemas->findSeats($schema_id));
    }
}
