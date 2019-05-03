<?php
/**
 * Created by PhpStorm.
 * User: ktulinger
 * Date: 2019-04-13
 * Time: 12:18
 */

namespace App\v1Module\Models;


class SeatEvent
{
    public $seat;
    public $from;
    public $to;

    public function __construct($seat, $from, $to)
    {
        $seat = array_merge([], $seat);
        unset($seat['user']);
        $this->seat = $seat;
        $this->from = $from;
        $this->to = $to;
    }
}