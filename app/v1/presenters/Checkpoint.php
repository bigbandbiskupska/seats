<?php
/**
 * Created by PhpStorm.
 * User: ktulinger
 * Date: 03/09/2018
 * Time: 09:20
 */

namespace App\v1Module\Presenters;


use App\v1Module\Models\CheckpointsModel;
use Nette\Application\UI\Presenter;

class CheckpointPresenter extends Presenter
{

    /**
     * @var CheckpointsModel
     * @inject
     */
    public $checkpoints;

    public function actionRead($id)
    {
        $this->sendJson($this->checkpoints->find($id));
    }


}