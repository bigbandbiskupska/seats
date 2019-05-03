<?php
/**
 * Created by PhpStorm.
 * User: ktulinger
 * Date: 2019-04-13
 * Time: 12:11
 */

namespace App\v1Module\Models;


class UserEvent
{
    public $user;
    public $seat;
    public $action;
    public $from;
    public $to;
    public $reason;

    public function __construct($user, $seat, $action, $from, $to, $reason)
    {
        $user = array_merge([], $user);
        unset($user['seats']);
        $this->user = $user;
        $this->seat = $seat;
        $this->action = $action;
        $this->from = $from;
        $this->to = $to;
        $this->reason = $reason;
    }
}