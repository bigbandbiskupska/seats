<?php

namespace App\v1Module\Presenters;

use App\v1Module\Models\Seats;

class SeatsPresenter extends BasePresenter
{

    /**
     * @var Seats
     * @inject
     */
    public $seats;

    public function actionCreate()
    {
        $this->ensureRoles(['administrator']);
        $this->sendJson($this->seats->create($this->getJsonBody(['x', 'y', 'col', 'row', 'schema_id'])));
    }

}
