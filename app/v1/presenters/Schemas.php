<?php

namespace App\v1Module\Presenters;

use App\v1Module\Models\SchemasModel;
use App\v1Module\Models\SeatsModel;

class SchemasPresenter extends BasePresenter
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

    public function actionRead()
    {
        /*$this->getHttpResponse()->setCode(400);
        $this->sendResponse(new JsonResponse([
            'status' => 400,
            'message' => 'this does not work'
        ]));*/
        $this->ensureRoles(['user']);
        $this->sendJson($this->schemas->all());
    }

    public function actionCreate()
    {
        $this->ensureRoles(['administrator']);
        $this->sendJson($this->schemas->create($this->getJsonBody(['name', 'price', 'limit', 'seats'])));
    }

    // TODO: test that locked schema does not do anything
}
