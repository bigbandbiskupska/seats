<?php
/**
 * Created by PhpStorm.
 * User: ktulinger
 * Date: 2019-04-13
 * Time: 11:36
 */

namespace App\v1Module\Models;


use Nette\Utils\DateTime;
use StdClass;

class CheckpointDiff extends StdClass
{
    public $created_at;
    public $data = [
        'seats' => [],
        'users' => [],
    ];

    private $reasons = [
        'unconfirmed' => [
            'confirmed' => ['moved', 'paid ticket'],
            'packaged' => ['moved', 'invalidly packaged unpaid ticket'],
            null => ['removed', 'removed ticket'],
        ],
        'confirmed' => [
            'unconfirmed' => ['moved', 'invalid unpaid ticket'],
            'packaged' => ['moved', 'packaged ticket'],
            null => ['removed', 'invalid remove paid ticket'],
        ],
        'packaged' => [
            null => ['removed', 'invalidly removed packaged ticket'],
            'unconfirmed' => ['added', 'added ticket'],
            'confirmed' => ['added', 'invalidly added paid ticket'],
        ],
        null => [
            'unconfirmed' => ['added', 'added ticket'],
            'confirmed' => ['added', 'invalidly added paid ticket'],
            'packaged' => ['added', 'invalidly added packaged ticket'],
        ],
    ];

    /**
     * CheckpointDiff constructor.
     */
    public function __construct()
    {
        $this->created_at = new DateTime();
    }


    public function removeUser($user)
    {
        $this->data['users'][] = sprintf('deleted user %s', $user);
    }

    public function addUser($user)
    {
        $this->data['users'][] = sprintf('added user %s', $user);
    }

    public function moveActionSeat($user, $seat, $from, $to)
    {
        $this->data['users'][] = new UserEvent($user, $seat, $this->reasons[$from][$to][0], $from, $to, $this->reasons[$from][$to][1]);
    }

    public function addDeletedSeat($seat)
    {
        $this->data['seats'][] = 'deleted seat ' . $seat;
    }

    public function addSeat($seat)
    {
        $this->data['seats'][] = 'deleted seat ' . $seat;
    }

    public function moveSeat($seat, $from, $to)
    {
        $this->data['seats'][] = new SeatEvent($seat, $from, $to);
    }
}

