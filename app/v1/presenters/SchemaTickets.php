<?php

namespace App\v1Module\Presenters;

use App\v1Module\Models\Schemas;
use App\v1Module\Models\Seats;

class SchemaTicketsPresenter extends BasePresenter
{

    /**
     * @var Schemas
     * @inject
     */
    public $schemas;

    /**
     * @var Seats
     * @inject
     */
    public $seats;

    public function actionRead($schema_id)
    {
        $this->ensureRoles(['user']);
        $this->sendJson($this->schemas->findReservations($schema_id));
    }
}
