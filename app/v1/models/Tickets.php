<?php

namespace App\v1Module\Models;

use Exception;
use Nette\Application\BadRequestException;
use Nette\Database\Context;
use Nette\Http\IResponse;
use Nette\Utils\DateTime;
use Nette\Utils\Strings;
use Tracy\Debugger;
use Tracy\ILogger;

class Tickets extends BaseModel
{
    /**
     * @var Users
     * @inject
     */
    private $users;

    /**
     * Schemas constructor.
     * @param Users $users
     */
    public function __construct(Context $database, Users $users)
    {
        parent::__construct($database);
        $this->users = $users;
    }

    public function all()
    {
        return array_map(function($ticket) {
            return array_merge(self::toArray($ticket),
                ['created_at' => $ticket->created_at->getTimestamp() * 1000],
                ['updated_at' => $ticket->updated_at->getTimestamp() * 1000],
                ['schema' => self::toArray($ticket->ref('schema'))],
                ['seats' => array_keys($ticket->related('reservations')->fetchPairs('id'))]
            );
        }, $this->database->table($this->table)->fetchAll());
    }


    public function findSeats($id)
    {
        if (($ticket = $this->database->table($this->table)->get($id)) === false) {
            throw new BadRequestException("Neznámé $id pro {$this->table}");
        }
        return array_map(function ($reservation) {
            return self::toArray($reservation->ref('seat'));
        }, $ticket->related('reservations')->fetchPairs('id'));
    }

    public function create($parameters)
    {

        $this->database->beginTransaction();
        try {
            if (!isset($parameters['created_at'])) {
                $parameters['created_at'] = new DateTime;
            }
            if (!isset($parameters['updated_at'])) {
                $parameters['updated_at'] = new DateTime;
            }

            if (!$parameters['seats'] || count($parameters['seats']) === 0) {
                throw new BadRequestException('Žádná sedadla.', IResponse::S400_BAD_REQUEST);
            }

            // TODO: test if schema is locked

            if (isset($parameters['user_id'])) {
                if (($user = $this->database->table('users')->get($parameters['user_id'])) === false) {
                    throw new BadRequestException("Uživatel {$parameters['user_id']} neexistuje.", IResponse::S400_BAD_REQUEST);
                }
            } else if (isset($parameters['user']) && isset($parameters['user']['name']) && isset($parameters['user']['surname'])) {
                if(empty($parameters['user']['name']) || empty($parameters['user']['surname'])) {
                    throw new BadRequestException('Jméno a příjmení jsou povinná políčka.', IResponse::S400_BAD_REQUEST);
                }
                // TODO: test this
                $email = Strings::webalize($parameters['user']['name']) . '.' . Strings::webalize($parameters['user']['surname']) . '@system-created-email.com';
                if (($user = $this->database->table('users')->where('email', $email)->fetch()) === false) {
                    $user = $this->database->table('users')->get($this->users->create([
                        'name' => $parameters['user']['name'],
                        'surname' => $parameters['user']['surname'],
                        'email' => $email,
                        'password' => null,
                        'roles' => 'guest',
                        'token' => null,
                        'expires_at' => null,
                        'last_click_at' => new DateTime(),
                        'ip_address' => $_SERVER['REMOTE_ADDR'],
                    ])['id']);
                }
                unset($parameters['user']);
                $parameters['user_id'] = $user->id;
            } else {
                throw new BadRequestException("Uživatel neexistuje a nelze ho vytvořit.", IResponse::S400_BAD_REQUEST);
            }

            $seats = [];
            if (isset($parameters['seats']) && is_array($parameters['seats'])) {
                $seats = self::resultToArray($this->database->table('seats')->where('id', $parameters['seats'])->fetchPairs('id'));
                if (count($seats) !== count($parameters['seats'])) {
                    throw new BadRequestException('Jedno nebo více sedadel neexistuje.', IResponse::S400_BAD_REQUEST);
                }
                unset($parameters['seats']);
            }

            $schema_id = null;
            foreach ($seats as $id => $seat) {
                if ($seat['state'] !== Seats::AVAILABLE) {
                    throw new BadRequestException("Sedadlo {$id} již bylo rezervováno.", IResponse::S400_BAD_REQUEST);
                }
                if ($schema_id === null) {
                    $schema_id = $seat['schema_id'];
                    continue;
                }
                if ($schema_id !== $seat['schema_id']) {
                    throw new BadRequestException('Všechna sedadla musí být ze stejné akce.', IResponse::S400_BAD_REQUEST);
                }
            }

            if (($schema = $this->database->table('schemas')->get($schema_id)) === false) {
                throw new BadRequestException('Akce pro vaše sedadla neexistuje.', IResponse::S400_BAD_REQUEST);
            }

            if($schema->locked) {
                throw new BadRequestException('Tato akce je uzamčena pro rezervace.', IResponse::S400_BAD_REQUEST);
            }

            if (count($seats) > $user->related('allowed_limit')->where('schema_id', $schema->id)->fetch()->limit) {
                throw new BadRequestException('Počet sedadel přesáhl stanovený limit.', IResponse::S400_BAD_REQUEST);
            }

            // TODO: with multiple reservations user can get above the limit
            $total_seats = array_reduce($user->related('tickets')->fetchPairs('id'), function ($a, $ticket) use ($schema_id) {
                return $a + count(array_filter(array_map(function ($reservation) {
                        return $this->database->table('seats')->get($reservation->seat_id);
                    }, $ticket->related('reservations')->fetchAll()), function ($seat) use ($schema_id) {
                        return $seat->schema_id === $schema_id;
                    }));
            }, 0);

            if ($total_seats + count($seats) > $user->related('allowed_limit')->where('schema_id', $schema->id)->fetch()->limit) {
                throw new BadRequestException('Počet sedadel přesáhl stanovený limit.', IResponse::S400_BAD_REQUEST);
            }

            $parameters['schema_id'] = $schema->id;

            $ticket = $this->database->table($this->table)->insert($parameters);

            $processedSeats = array_values(array_map(function ($seat) use ($ticket) {
                return array(
                    'ticket_id' => $ticket->id,
                    'seat_id' => $seat['id'],
                    'note' => $ticket->note,
                    'price' => $seat['price'],
                );
            }, $seats));

            $this->database->table('reservations')->insert($processedSeats);
            $this->database->table('seats')->where('id', array_map(function ($seat) {
                return $seat['id'];
            }, $seats))->update([
                'state' => Seats::RESERVED
            ]);
            $this->database->commit();
            Debugger::log(sprintf('User [%d] (%s %s) created a new ticket [%d] with seats [%s] in schema [%d] (%s).',
                $user->id, $user->name, $user->surname,
                $ticket->id,
                implode(",", array_map(function($seat) { return $seat['id']; }, $seats)),
                $schema->id, $schema->name
            ), 'actions');
        } catch (BadRequestException $e) {
            throw $e;
        } catch (Exception $e) {
            $this->database->rollBack();
            Debugger::log($e, ILogger::EXCEPTION);
            throw new BadRequestException('Vytvoření objednávky selhalo.', IResponse::S400_BAD_REQUEST, $e);
        }
        return $this->find($ticket->id);
    }

    public function update($id, $parameters)
    {
        foreach ($parameters as $column => $value) {
            if (!in_array($column, ['note', 'confirmed'])) {
                throw new BadRequestException("Neznámé nebo zakázané nastavení $column.", IResponse::S400_BAD_REQUEST);
            }
        }

        $parameters['updated_at'] = new DateTime;

        $ticket = parent::update($id, $parameters);
        $user = $this->database->table('users')->get($ticket['user_id']);

        $ticket = $this->find($ticket['id']);

        Debugger::log(sprintf('User [%d] (%s %s) updated a ticket [%d] with seats [%s] [confirmed = %d] in schema [%d] (%s).',
            $user->id, $user->name, $user->surname,
            $ticket['id'],
            implode(",", $ticket['seats']),
            $ticket['confirmed'],
            $ticket['schema']['id'], $ticket['schema']['name']
        ), 'actions');

        return $ticket;
    }

    public function delete($id)
    {
        if (($ticket = $this->database->table($this->table)->get($id)) === false) {
            throw new BadRequestException("Neznámé $id pro {$this->table}");
        }
        if ($ticket->confirmed) {
            throw new BadRequestException("Objednávka $id již byla zaplacena, a tudíž jí nelze smazat.", IResponse::S400_BAD_REQUEST);
        }
        $this->database->beginTransaction();
        try {

            $logRecord = sprintf('User [%d] (%s %s) deleted a ticket [%d] with seats [%s] in schema [%d] (%s).',
                $ticket->ref('user')->id, $ticket->ref('user')->name, $ticket->ref('user')->surname,
                $id,
                implode(",", array_map(function($reservation) { return $reservation->seat_id; }, $ticket->related('reservations')->fetchAll())),
                $ticket->ref('schema')->id, $ticket->ref('schema')->name
            );

            foreach ($ticket->related('reservations')->fetchAll() as $reservation) {
                $reservation->ref('seats', 'seat_id')->update([
                    'state' => Seats::AVAILABLE
                ]);
                $reservation->delete();
            }

            $ticket->delete();
            $this->database->commit();
            Debugger::log($logRecord, 'actions');
        } catch (Exception $e) {
            $this->database->rollBack();
            Debugger::log($e, ILogger::EXCEPTION);
            throw new BadRequestException("Objednávku $id se nepodařilo smazat.", IResponse::S400_BAD_REQUEST, $e);
        }
    }

    public function find($id)
    {
        if (($ticket = $this->database->table($this->table)->get($id)) === false) {
            throw new BadRequestException("Neznámé $id pro {$this->table}.");
        }

        return array_merge($ticket->toArray(),
            ['created_at' => $ticket->created_at->getTimestamp() * 1000],
            ['updated_at' => $ticket->updated_at->getTimestamp() * 1000],
            ['schema' => self::toArray($ticket->ref('schema'))],
            ['seats' => array_keys($ticket->related('reservations')->fetchPairs('id'))]);
    }
}
