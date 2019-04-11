<?php

namespace App\v1Module\Presenters;

use App\v1Module\Models\Tickets;

class TicketSeatsPresenter extends BasePresenter
{

    /**
     * @var Tickets
     * @inject
     */
    public $tickets;

    public function actionRead($id)
    {
        $this->ensureRoles(['user']);
        $this->sendJson($this->tickets->findSeats($id));
    }
}
