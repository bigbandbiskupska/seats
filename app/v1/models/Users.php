<?php

namespace App\v1Module\Models;

use Nette\Application\BadRequestException;
use Tracy\Debugger;

class Users extends BaseModel
{

    public function findTickets($id)
    {
        return array_map(function ($ticket) {
            return array_merge(
                $ticket->toArray(),
                ['schema' => self::toArray($ticket->ref('schema'))],
                ['seats' => array_keys($ticket->related('reservations')->fetchPairs('id'))]
            );
        }, $this->database->table('users')->get($id)->related('tickets.user_id')->fetchPairs('id'));
    }

    public function create($parameters)
    {
        $user = $this->database->table($this->table)->insert($parameters);
        foreach ($this->database->table('schemas')->fetchAll() as $schema) {
            $this->database->table('allowed_limit')->insert([
                'schema_id' => $schema->id,
                'user_id' => $user->id,
                'limit' => $schema->limit,
            ]);
        }
        Debugger::log(sprintf('User [%d] (%s %s) was created.', $user->id, $user->name, $user->surname), 'actions');
        return self::toArray($user);
    }

    public function findOneBy($conditions)
    {
        $users = $this->database->table($this->table);
        foreach ($conditions as $param => $value) {
            $users = $users->where($param, $value);
        }
        $rows = $users->fetchAll();
        if (count($rows) > 1 || count($rows) === 0) {
            return null;
        }
        foreach ($users as $user) {
            return self::toArray($user);
        }
    }

    public function find($id)
    {
        // TODO: test
        if (($user = $this->entity($id)) === null) {
            throw new BadRequestException("Neznámé $id pro {$this->table}");
        }
        $data = self::toArray($user);
        unset($data['password']);
        return array_merge($data, [
            'tickets' => array_keys($user->related('tickets.user_id')->fetchPairs('id'))
        ]);
    }
}
