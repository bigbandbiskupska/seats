<?php

namespace App\v1Module\Presenters;

use App\v1Module\Models\TicketsModel;
use Nette\Application\BadRequestException;
use Nette\Http\IResponse;

class TicketPresenter extends BasePresenter
{

    /**
     * @var TicketsModel
     * @inject
     */
    public $tickets;

    // TODO: can read cizí tickets
    public function actionRead($id)
    {
        $this->ensureRoles(['user']);
        $this->sendJson($this->tickets->find($id));
    }

    public function actionUpdate($id)
    {
        $this->ensureRoles(['administrator']);
        $this->sendJson($this->tickets->update($id, $this->getJsonBody()));
    }

    public function actionDelete($id)
    {
        $this->ensureRoles(['user']);
        if ($this->tickets->find($id)['user_id'] !== $this->user->getData()['id']) {
            throw new BadRequestException('Nelze smazat objednávku, která vám nepatří.', IResponse::S400_BAD_REQUEST);
        }
        $this->tickets->delete($id);
        $this->sendJson([]);
    }

}
