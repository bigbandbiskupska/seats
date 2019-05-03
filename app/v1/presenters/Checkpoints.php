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

class CheckpointsPresenter extends Presenter
{

    /**
     * @var CheckpointsModel
     * @inject
     */
    public $checkpoints;

    public function actionCreate()
    {
        $this->sendJson($this->checkpoints->create());
    }


    public function actionRead()
    {
        $this->sendJson($this->checkpoints->all());
    }


    public function actionDiff($old, $new)
    {
        $this->sendJson($this->checkpoints->diff($old, $new));
    }
}