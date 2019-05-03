<?php

namespace App;

use Nette;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;
use Ublaboo\ApiRouter\ApiRoute;

class RouterFactory
{

    /**
     * @return Nette\Application\IRouter
     */
    public static function createRouter()
    {
        $router = new RouteList;
        $router[] = new ApiRoute('/api/v1/seats', 'v1:Seats');
        $router[] = new ApiRoute('/api/v1/schemas', 'v1:Schemas');
        $router[] = new ApiRoute('/api/v1/schema/<id>', 'v1:Schema');
        $router[] = new ApiRoute('/api/v1/seat/<id>', 'v1:Seat');
        $router[] = new ApiRoute('/api/v1/schema/<schema_id>/reservations', 'v1:SchemaTickets');
        $router[] = new ApiRoute('/api/v1/schema/<schema_id>/seats', 'v1:SchemaSeats');
        $router[] = new ApiRoute('/api/v1/schema/<schema_id>/seat/<id>', 'v1:Seat');
        $router[] = new ApiRoute('/api/v1/schema/<schema_id>/history', 'v1:SchemaHistory');

        $router[] = new ApiRoute('/api/v1/users', 'v1:Users');
        $router[] = new ApiRoute('/api/v1/user/<id>', 'v1:User');
        $router[] = new ApiRoute('/api/v1/user/<user_id>/history', 'v1:UserHistory');
        $router[] = new Route('/api/v1/users/login', 'v1:Login:login');

        $router[] = new ApiRoute('/api/v1/user/<user_id>/tickets', 'v1:UserTickets');
        $router[] = new ApiRoute('/api/v1/user/<user_id>/ticket/<id>', 'v1:Ticket');

        $router[] = new ApiRoute('/api/v1/ticket/<id>', 'v1:Ticket');
        $router[] = new ApiRoute('/api/v1/ticket/<id>/seats', 'v1:TicketSeats');
        $router[] = new ApiRoute('/api/v1/tickets', 'v1:Tickets');

        $router[] = new ApiRoute('/api/v1/checkpoint/<id>', 'v1:Checkpoint');
        $router[] = new ApiRoute('/api/v1/checkpoints', 'v1:Checkpoints');
        $router[] = new Route('/api/v1/checkpoints/diff/<old \d+>[/<new \d+>]', [
            'presenter' => 'v1:Checkpoints',
            'action' => 'diff',
            'new' => null,
        ]);

        $router[] = new Route('<presenter>/<action>[/<id>]', 'Homepage:default');
        return $router;
    }

}
