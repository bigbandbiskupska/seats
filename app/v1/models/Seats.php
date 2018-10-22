<?php

namespace App\v1Module\Models;

use Nette\Application\BadRequestException;
use Nette\Http\IResponse;
use Tracy\Debugger;

class Seats extends BaseModel
{

    const WALL = 0;
    const AVAILABLE = 1;
    const RESERVED = 2;

    public function find($id)
    {
        if (($entity = $this->database->table($this->table)->get($id)) === false) {
            throw new BadRequestException("Neznámé $id pro {$this->table}");
        }

        return array_merge(self::toArray($entity),
            array(
                'tickets' => array_keys($entity->related('reservations.seat_id')->fetchPairs('ticket_id'))
            )
        );
    }

    public function create($parameters)
    {
        if (!isset($parameters['price'])) {
            if (($schema = $this->database->table('schemas')->get($parameters['schema_id'])) === false) {
                throw new BadRequestException("Nepodařilo se najít schéma pro toho sedadlo.");
            }
            $parameters['price'] = $schema->price;
        }

        $seat = $this->database->table($this->table)->insert($parameters);

        Debugger::log(sprintf('Seat [%d] was created in schema [%d].', $seat->id, $parameters['schema_id']), 'actions');

        return self::toArray($seat);
    }

    public function delete($id)
    {
        if (($entity = $this->database->table($this->table)->get($id)) === false) {
            throw new BadRequestException("Neznámé $id pro {$this->table}.", IResponse::S400_BAD_REQUEST);
        }
        if ($entity->related('reservations')->count('*') > 0) {
            throw new BadRequestException("Sedadlo $id je již součástí objednávky.", IResponse::S400_BAD_REQUEST);
        }
        Debugger::log(sprintf('Seat [%d] was deleted from schema [%d] (%s).', $entity->id, $entity->ref('schema')->id, $entity->ref('schema')->name), 'actions');
        $entity->delete();
    }

    public function update($id, $parameters)
    {
        foreach ($parameters as $column => $value) {
            if (!in_array($column, ['x', 'y', 'col', 'row', 'state', 'price'])) {
                throw new BadRequestException("Neznámé nebo zakázané nastavení $column.", IResponse::S400_BAD_REQUEST);
            }
        }

        $seat = parent::update($id, $parameters);

        Debugger::log(sprintf('Seat [%d] was updated.', $seat['id']), 'actions');
        return $seat;
    }

}
