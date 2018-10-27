<?php

namespace App\v1Module\Models;

use Nette\Application\BadRequestException;
use Nette\Database\Context;
use Nette\Http\IResponse;
use Tracy\Debugger;
use Tracy\ILogger;

class Schemas extends BaseModel
{

    /**
     * @var Seats
     * @inject
     */
    private $seats;

    /**
     * Schemas constructor.
     * @param Seats $seats
     */
    public function __construct(Context $database, Seats $seats)
    {
        parent::__construct($database);
        $this->seats = $seats;
    }

    // TODO: write a test
    // TODO: is incomplete /schema/1/reservations?token=abcd
    public function findReservations($id)
    {
        if (($schema = $this->database->table($this->table)->get($id)) === false) {
            throw new BadRequestException("Neznámé $id pro {$this->table}");
        }
        return array_map(function ($seat) {
            return array_values(array_map(function ($e) {
                return $e->toArray();
            }, array_map(function ($reservation) {
                return $reservation->ticket;
            }, $seat->related('reservations.seat_id')->fetchAll())));

        }, $schema->related('seats.schema_id')->fetchPairs('id'));
    }

    public function findSeats($id)
    {
        if (($schema = $this->database->table($this->table)->get($id)) === false) {
            throw new BadRequestException("Neznámé $id pro {$this->table}");
        }
        $seats =  array_map(function ($seat) {
            return array_merge(
                $seat->toArray(),
                array(
                    'tickets' => array_values(array_map(function ($e) {
                        return $e->toArray();
                    }, array_map(function ($reservation) {
                        return $reservation->ticket;
                    }, $seat->related('reservations.seat_id')->fetchAll())))
                )
            );
        }, $schema->related('seats.schema_id')->fetchPairs('id'));
        shuffle($seats);
        return array_combine(array_map(function($s) { return $s['id'];}, $seats), $seats);
    }

    // TODO: write a test for schema.seats for presence of tickets
    // TODO: write a test for seat(id) for presence of tickets

    public function find($id)
    {
        if (($schema = $this->entity($id)) === null) {
            throw new BadRequestException("Neznámé $id pro {$this->table}");
        }
        $seats = $schema->related('seats.schema_id')->fetchPairs('id');
        $seats = array_keys($seats);
        shuffle($seats);
        return array_merge(self::toArray($schema), [
            'seats' => $seats
        ]);
    }

    // TODO: do not return unhidden for hidden user schema
    // TODO: include a test for schema seats
    public function all()
    {
        return array_map(function ($schema) {
            $seats = $schema->related('seats.schema_id')->fetchPairs('id');
            return array_merge(self::toArray($schema), [
                'seats' => array_keys($seats)
            ]);
        }, $this->database->table($this->table)->fetchPairs('id'));
    }

    public function update($id, $parameters)
    {
        foreach ($parameters as $column => $value) {
            if (!in_array($column, ['name', 'price', 'hidden', 'locked', 'limit', 'seats'])) {
                throw new BadRequestException("Neznámé nebo zakázané nastavení $column", IResponse::S400_BAD_REQUEST);
            }
        }

        if (($entity = $this->database->table($this->table)->get($id)) === false) {
            throw new BadRequestException("Neznámé $id pro {$this->table}");
        }

        $this->database->beginTransaction();
        try {
            $seats = null;
            if (isset($parameters['seats'])) {
                $seats = $parameters['seats'];
                unset ($parameters['seats']);
            }

            if (isset($parameters['limit'])) {
                // TODO: test check if this is actually changed
                $this->database->table('allowed_limit')->where('schema_id', $entity->id)->where('limit', $entity->limit)->update([
                    'limit' => $parameters['limit'],
                ]);
            }

            $entity->update($parameters);

            // TODO: test update seats
            if ($seats && count($seats) > 0 && count($seats[0]) > 0) {
                foreach ($seats as $row) {
                    foreach ($row as $seat) {
                        if (!isset($seat['x']) || $seat['x'] <= 0) {
                            throw new BadRequestException('Sedadlo musí mít "x" pozici.', IResponse::S400_BAD_REQUEST);
                        }
                        if (!isset($seat['y']) || $seat['y'] <= 0) {
                            throw new BadRequestException('Sedadlo musí mít "y" pozici.', IResponse::S400_BAD_REQUEST);
                        }
                        if (!isset($seat['row']) || $seat['row'] < 0) {
                            throw new BadRequestException('Sedadlo musí mít "row" pozici.', IResponse::S400_BAD_REQUEST);
                        }
                        if (!isset($seat['col']) || $seat['col'] < 0) {
                            throw new BadRequestException('Sedadlo musí mít "col" pozici.', IResponse::S400_BAD_REQUEST);
                        }
                        if (!isset($seat['price'])) {
                            throw new BadRequestException('Sedadlo musí mít "price" nastavenou.', IResponse::S400_BAD_REQUEST);
                        }
                    }
                }


                foreach ($seats as &$row) {
                    uasort($row, function ($s1, $s2) {
                        return $s1['x'] - $s2['x'];
                    });
                }


                uasort($seats, function ($row1, $row2) {
                    return $row1[0]['y'] - $row2[0]['y'];
                });

                $seats = $this->correctPositions($seats);

                foreach ($seats as &$row) {
                    foreach ($row as &$seat) {
                        if (!isset($seat['id'])) {
                            // create
                            $seat['schema_id'] = $entity->id;
                            $this->seats->create($seat);
                        } else {
                            // update
                            $id = $seat['id'];

                            if ($seat['state'] == Seats::WALL || $seat['state'] == Seats::AVAILABLE) {
                                $oldSeat = $this->seats->find($id);
                                if ($oldSeat['state'] === Seats::RESERVED && count($oldSeat['tickets']) > 0) {
                                    throw new BadRequestException(
                                        sprintf('Nelze smazat sedadlo z již existující objednávky [%d].', $oldSeat['tickets'][0]),
                                        IResponse::S400_BAD_REQUEST);
                                }
                            }

                            unset ($seat['id']);
                            unset ($seat['schema_id']);
                            unset ($seat['tickets']);

                            $this->seats->update($id, $seat);
                        }
                    }
                }
            }
            $this->database->commit();

            Debugger::log(sprintf('Schema [%d] (%s) was updated.', $entity->id, $entity->name), 'actions');
        } catch (Exception $e) {
            $this->database->rollBack();
            Debugger::log($e, $e, ILogger::EXCEPTION);
            throw new BadRequestException('Nepodařilo se uložit schéma.', IResponse::S400_BAD_REQUEST, $e);
        }

        return $this->find($entity->id);
    }

    public function delete($id)
    {
        if (($schema = $this->database->table($this->table)->get($id)) === false) {
            throw new BadRequestException("Neznámé $id pro {$this->table}");
        }
        $schema->related('seats')->delete();
        Debugger::log(sprintf('Schema [%d] (%s) was deleted.', $schema->id, $schema->name), 'actions');
        $schema->delete();
    }

    public function create($parameters)
    {
        $this->database->beginTransaction();
        try {
            $seats = null;
            if (isset($parameters['seats'])) {
                $seats = $parameters['seats'];
                unset ($parameters['seats']);
            }
            // TODO: test allowed limit
            $schema = $this->database->table($this->table)->insert($parameters);
            foreach ($this->database->table('users')->fetchAll() as $user) {
                $this->database->table('allowed_limit')->insert([
                    'schema_id' => $schema->id,
                    'user_id' => $user->id,
                    'limit' => $schema->limit,
                ]);
            }

            // TODO: test create seats
            if ($seats && count($seats) > 0 && count($seats[0]) > 0) {
                foreach ($seats as $row) {
                    foreach ($row as $seat) {
                        if (!isset($seat['x']) || $seat['x'] <= 0) {
                            throw new BadRequestException('Sedadlo musí mít "x" pozici.', IResponse::S400_BAD_REQUEST);
                        }
                        if (!isset($seat['y']) || $seat['y'] <= 0) {
                            throw new BadRequestException('Sedadlo musí mít "y" pozici.', IResponse::S400_BAD_REQUEST);
                        }
                        if (!isset($seat['row']) || $seat['row'] < 0) {
                            throw new BadRequestException('Sedadlo musí mít "row" pozici.', IResponse::S400_BAD_REQUEST);
                        }
                        if (!isset($seat['col']) || $seat['col'] < 0) {
                            throw new BadRequestException('Sedadlo musí mít "col" pozici.', IResponse::S400_BAD_REQUEST);
                        }
                        if (!isset($seat['price'])) {
                            throw new BadRequestException('Sedadlo musí mít "price" nastavenou.', IResponse::S400_BAD_REQUEST);
                        }
                    }
                }

                foreach ($seats as &$row) {
                    uasort($row, function ($s1, $s2) {
                        return $s1['x'] - $s2['x'];
                    });
                }

                uasort($seats, function ($row1, $row2) {
                    return $row1[0]['y'] - $row2[0]['y'];
                });

                $seats = $this->correctPositions($seats);

                foreach ($seats as &$row) {
                    foreach ($row as &$seat) {
                        $seat['schema_id'] = $schema->id;
                        $this->seats->create($seat);
                    }
                }

            }
            $this->database->commit();
            Debugger::log(sprintf('Schema [%d] (%s) was created.', $schema->id, $schema->name), 'actions');
        } catch (Exception $e) {
            $this->database->rollBack();
            Debugger::log($e, ILogger::EXCEPTION);
            throw new BadRequestException('Vytvoření schématu selhalo.', IResponse::S400_BAD_REQUEST, $e);
        }
        return $this->find($schema->id);
    }

    public function correctPositions($seats)
    {
        for ($i = 0, $row = 0; $i < count($seats); $i++) {
            $line = &$seats[$i];
            for ($j = 0, $col = 0, $empty = true; $j < count($line); $j++) {
                $seat = &$line[$j];
                if ($seat['state'] != Seats::WALL) {
                    $col++;
                    $empty = false;
                }
                $seat['col'] = $col;
            }

            if (!$empty) {
                $row++;
            }

            foreach ($line as &$seat) {
                $seat['row'] = $row;
            }
        }
        return $seats;
    }

}
