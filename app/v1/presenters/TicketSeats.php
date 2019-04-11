<?php

namespace App\v1Module\Presenters;

use App\v1Module\Models\TicketsModel;

class TicketSeatsPresenter extends BasePresenter
{

    /**
     * @var TicketsModel
     * @inject
     */
    public $tickets;

    public function actionRead($id)
    {
        $this->ensureRoles(['user']);
        $this->sendJson($this->tickets->findSeats($id));
    }
}
