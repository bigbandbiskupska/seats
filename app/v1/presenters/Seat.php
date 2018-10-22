<?php

namespace App\v1Module\Presenters;

use App\v1Module\Models\Seats;

/**
 * Simple route with matching (only if methods below exist):
 *    GET     => UsersPresenter::actionRead()
 *    POST    => UsersPresenter::actionCreate()
 *    PUT     => UsersPresenter::actionUpdate()
 *    DELETE  => UsersPresenter::actionDelete()
 */
class SeatPresenter extends BasePresenter
{

    /**
     * @var Seats
     * @inject
     */
    public $seats;

    public function actionRead($id)
    {
        $this->ensureRoles(['user']);
        $this->sendJson($this->seats->find($id));
    }

    public function actionUpdate($id)
    {
        $this->ensureRoles(['administrator']);
        $this->sendJson($this->seats->update($id, $this->getJsonBody()));
    }

    public function actionDelete($id)
    {
        $this->ensureRoles(['administrator']);
        $this->seats->delete($id);
        $this->sendJson([]);
    }

}
