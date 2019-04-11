<?php

namespace App\v1Module\Presenters;

use App\v1Module\Models\TicketsModel;

class TicketsPresenter extends BasePresenter
{

    /**
     * @var TicketsModel
     * @inject
     */
    public $tickets;

    public function actionCreate()
    {
        $this->ensureRoles(['user']);
        $data = $this->getJsonBody(['seats']);
        $this->sendJson($this->tickets->create($data));
    }

    // TODO: test
    public function actionRead()
    {
        $this->ensureRoles(['administrator']);
        $this->sendJson($this->tickets->all());
    }
}
