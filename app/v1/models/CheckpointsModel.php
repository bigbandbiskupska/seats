<?php
/**
 * Created by PhpStorm.
 * User: ktulinger
 * Date: 2019-04-11
 * Time: 16:57
 */

namespace App\v1Module\Models;


use DateTimeZone;
use Nette\Database\Context;
use Nette\InvalidArgumentException;
use Nette\Utils\DateTime;
use Nette\Utils\Json;
use stdClass;

class CheckpointsModel extends BaseModel
{

    const USERS = 'users';
    const SEATS = 'seats';

    public function __construct(Context $database)
    {
        parent::__construct($database);
    }

    public function prepareCheckpoint()
    {
        $users = $this->prepareTableCheckpoint(self::USERS, function ($user) {
            $allowed = ['name', 'surname', 'email', 'ip_address'];
            return array_filter($user->toArray(), function ($key) use ($allowed) {
                return in_array($key, $allowed);
            }, ARRAY_FILTER_USE_KEY);
        });
        $tickets = $this->prepareTableCheckpoint('tickets', function ($ticket) {
            return [
                'user' => [
                    'id' => $ticket->user->id,
                    'name' => $ticket->user->name,
                    'surname' => $ticket->user->surname,
                    'email' => $ticket->user->email
                ],
                'schema' => [
                    'id' => $ticket->schema->id,
                    'name' => $ticket->schema->name
                ],
                'note' => $ticket->note,
                'confirmed' => $ticket->confirmed,
                'created_at' => $ticket->created_at,
                'updated_at' => $ticket->updated_at,
            ];
        });
        $seats = $this->prepareTableCheckpoint(self::SEATS, function ($seat) {

            $users = array_values(array_map(function ($reservation) {
                $reservation = $reservation->ticket;
                return [
                    'id' => $reservation->user->id,
                    'name' => $reservation->user->name,
                    'surname' => $reservation->user->surname,
                    'email' => $reservation->user->email,
                    'description' => sprintf('%s %s (%s)', $reservation->user->name, $reservation->user->surname, $reservation->user->email),
                ];
            }, $seat->related('reservations.seat_id')->fetchPairs('ticket_id')));

            return array_merge(array_merge($seat->toArray(), [
                'schema' => $seat->schema->toArray()
            ]), [
                'user' => count($users) > 0 ? $users[0] : null,
            ]);
        });
        $schemas = $this->prepareTableCheckpoint('schemas', function ($user) {
            $allowed = ['id', 'name', 'limit', 'price', 'hidden', 'locked'];
            return array_filter($user->toArray(), function ($key) use ($allowed) {
                return in_array($key, $allowed);
            }, ARRAY_FILTER_USE_KEY);
        });

        $xxx = $this->prepareTableCheckpoint(self::USERS, function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'surname' => $user->surname,
                'email' => $user->email,
                'seats' => [
                    TicketsModel::CONFIRMED => array_merge([], ...array_map(function ($ticket) {
                        if (!$ticket->confirmed || $ticket->packaged) {
                            return [];
                        }

                        return array_values(array_map(function ($reservation) {
                            $seat = $reservation->seat;
                            return array_merge($seat->toArray(), [
                                'schema' => $seat->schema->toArray()
                            ]);
                        }, $ticket->related('reservations.ticket_id')->fetchAll()));
                    }, $user->related('tickets')->fetchAll())),
                    TicketsModel::UNCONFIRMED => array_merge([], ...array_map(function ($ticket) {
                        if ($ticket->confirmed || $ticket->packaged) {
                            return [];
                        }

                        return array_values(array_map(function ($reservation) {
                            $seat = $reservation->seat;
                            return array_merge($seat->toArray(), [
                                'schema' => $seat->schema->toArray()
                            ]);
                        }, $ticket->related('reservations.ticket_id')->fetchAll()));
                    }, $user->related('tickets')->fetchAll())),
                    TicketsModel::PACKAGED => array_merge([], ...array_map(function ($ticket) {
                        if (!$ticket->confirmed || !$ticket->packaged) {
                            return [];
                        }

                        return array_values(array_map(function ($reservation) {
                            $seat = $reservation->seat;
                            return array_merge($seat->toArray(), [
                                'schema' => $seat->schema->toArray()
                            ]);
                        }, $ticket->related('reservations.ticket_id')->fetchAll()));
                    }, $user->related('tickets')->fetchAll())),
                ],
            ];
        });

        return [
            //'users' => $users,
            //'tickets' => $tickets,
            self::SEATS => $seats,
            //'schemas' => $schemas,
            self::USERS => $xxx,
        ];
    }

    protected function prepareTableCheckpoint($table, $callback)
    {

        $model = $this->database->table($table);
        return array_map($callback, $model->fetchPairs('id'));
    }

    public static function prepareEntity()
    {
        $args = func_get_args();
        if (count($args) === 0) {
            throw new InvalidArgumentException('No entity given');
        }

        $entity = array_shift($args);

        $result = [];
        foreach ($args as $attribute) {
            $result[$attribute] = $entity[$attribute];
        }

        return $result;
    }

    public function latest($offset)
    {
        $checkpoint = $this->database->table($this->table)
            ->order('created_at DESC')
            ->limit(1, $offset)
            ->fetch();

        if (!$checkpoint) {
            return null;
        }

        $checkpoint = self::toArray($checkpoint);
        $checkpoint['data'] = Json::decode($checkpoint['data'], Json::FORCE_ARRAY);
        return $checkpoint;
    }

    public function diff($oldId, $newId)
    {
        $old = $oldId === null ?
            /* this should be the first available rev. */
            $this->database->table($this->table)
                ->order('created_at ASC')
                ->limit(1)
                ->fetch() :
            $this->find($oldId)['data'];
        $new = $newId === null ?
            /* asking for most recent */
            $this->prepareCheckpoint() :
            $this->find($newId)['data'];

        $diff = new CheckpointDiff();

        // find missing users
        foreach (array_diff_key($old[self::USERS], $new[self::USERS]) as $id => $user) {
            $diff->removeUser($user);
        }

        foreach (array_diff_key($new[self::USERS], $old[self::USERS]) as $id => $user) {
            $diff->addUser($user);
        }

        // common ids
        foreach (array_intersect_key($old[self::USERS], $new[self::USERS]) as $id => $user) {
            $previous = $old[self::USERS][$id];
            $current = $new[self::USERS][$id];

            foreach ([TicketsModel::UNCONFIRMED, TicketsModel::CONFIRMED, TicketsModel::PACKAGED] as $fromAction) {
                foreach ($this->my_array_diff($previous[self::SEATS][$fromAction], $current[self::SEATS][$fromAction]) as $seat) {
                    $moved = false;
                    foreach ([TicketsModel::UNCONFIRMED, TicketsModel::CONFIRMED, TicketsModel::PACKAGED] as $toAction) {
                        if ($fromAction === $toAction) {
                            continue;
                        }

                        if (in_array($seat, $current[self::SEATS][$toAction])) {
                            $diff->moveActionSeat($previous, $seat, $fromAction, $toAction);
                            $moved = true;
                        }
                    }

                    if (!$moved) {
                        $diff->moveActionSeat($previous, $seat, $fromAction, null);
                    }
                }

                foreach ($this->my_array_diff($current[self::SEATS][$fromAction], $previous[self::SEATS][$fromAction]) as $seat) {
                    $moved = false;
                    foreach ([TicketsModel::UNCONFIRMED, TicketsModel::CONFIRMED, TicketsModel::PACKAGED] as $toAction) {
                        if ($fromAction === $toAction) {
                            continue;
                        }

                        $moved = $moved || in_array($seat, $previous[self::SEATS][$toAction]);
                    }

                    if (!$moved) {
                        $diff->moveActionSeat($previous, $seat, null, $fromAction);
                    }
                }
            }
        }

        // find missing seats
        foreach (array_diff_key($old[self::SEATS], $new[self::SEATS]) as $id => $seat) {
            $diff->addDeletedSeat($seat);
        }

        foreach (array_diff_key($new[self::USERS], $old[self::USERS]) as $id => $seat) {
            $diff->addSeat($seat);
        }

        // common ids
        foreach (array_intersect_key($old[self::SEATS], $new[self::SEATS]) as $id => $seat) {
            $previous = $old[self::SEATS][$id];
            $current = $new[self::SEATS][$id];

            if ($current['user'] != $previous['user']) {
                if ($previous['user'] === null) {
                    $diff->moveSeat($current, null, $current['user']);
                } else if ($current['user'] === null) {
                    $diff->moveSeat($current, $previous['user'], null);
                } else {
                    $diff->moveSeat($current, $previous['user'], $current['user']);
                }
            }
        }

        $diff->created_at = $newId !== null ? DateTime::from($this->find($newId)['created_at']) : new DateTime();

        return Json::decode(Json::encode($diff));
    }


    public function create($parameters = null)
    {
        //dump($this->prepareCheckpoint());
        $checkpoint = $this->database->table($this->table)->insert([
            'data' => Json::encode($this->prepareCheckpoint()),
            'created_at' => new DateTime(),
        ]);

        return $this->find($checkpoint->id);
    }

    public function find($id)
    {
        $checkpoint = parent::find($id);
        $checkpoint['data'] = Json::decode($checkpoint['data'], Json::FORCE_ARRAY);
        $checkpoint['created_at'] = $checkpoint['created_at']->format('c');
        return $checkpoint;
    }

    public function all()
    {
        return array_map(function ($checkpoint) {
            $checkpoint['data'] = Json::decode($checkpoint['data'], Json::FORCE_ARRAY);
            $checkpoint['created_at'] = $checkpoint['created_at']->format('c');
            return $checkpoint;
        }, parent::all());
    }

    private function my_array_diff($seats1, $seats2)
    {
        $seats2 = array_map(function ($s) {
            return $s['id'];
        }, $seats2);

        return array_filter(array_map(function ($oSeat) use ($seats2) {
            if (in_array($oSeat['id'], $seats2)) {
                return null;
            }
            return $oSeat;
        }, $seats1));
    }

    public function findSchemaHistory($id)
    {
        $result = [];
        foreach ($this->generateHistoryPairs() as $old => $new) {
            $curr = $this->diff($old, $new);
            $curr->data->seats = array_filter($curr->data->seats, function ($seat) use ($id) {
                return $seat->seat->schema->id == $id;
            });
            $curr->data->users = array();
            /*$curr['data']['users'] = array_filter($curr['data']['users'], function($user) use ($id) {
                return $seat['schema']['id'] === $id;
            });
            */
            foreach ($curr->data->seats as $seat) {
                if (!array_key_exists($seat->seat->id, $result)) {
                    $result[$seat->seat->id] = [];
                }

                $result[$seat->seat->id][] = $record = new StdClass;
                $record->changed_at = $curr->created_at;
                $record->changed_at = DateTime::from($curr->created_at->date)->setTimezone(new DateTimeZone($curr->created_at->timezone))->format('c');
                $record->seat = $seat->seat;
                $record->from = $seat->from;
                $record->to = $seat->to;

                if($seat->seat->id === 2) {
                }
            }
        }

        return $result;
    }

    private function generateHistoryPairs()
    {
        $ids = array_keys($this->database->table($this->table)->order('created_at ASC')->fetchPairs('id'));
        $n = count($ids);

        $result = [];
        for ($i = 0; $i < $n; $i++) {
            $result[$ids[$i]] = $i === $n - 1 ? null : $ids[$i + 1];
        }

        return $result;
    }

    public function findUserHistory($id)
    {
        $result = [];
        foreach ($this->generateHistoryPairs() as $old => $new) {
            $curr = $this->diff($old, $new);
            $curr->data->seats = array();
            $curr->data->users = array_filter($curr->data->users, function ($user) use ($id) {
                return $user->user->id == $id;
            });

            foreach ($curr->data->users as $user) {
                if (!array_key_exists($user->user->id, $result)) {
                    $result[$user->user->id] = [];
                }

                $result[$user->user->id][] = $new = new StdClass;
                $new->changed_at = $curr->created_at;
                $new->changed_at = DateTime::from($curr->created_at->date)->setTimezone(new DateTimeZone($curr->created_at->timezone))->format('c');
                $new->user = $user->user;
                $new->seat = $user->seat;
                $new->action = $user->action;
                $new->from = $user->from;
                $new->to = $user->to;
                $new->reason = $user->reason;

            }
        }

        return $result;
    }
}