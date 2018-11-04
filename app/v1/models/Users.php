<?php

namespace App\v1Module\Models;

use Nette\Application\BadRequestException;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Utils\Random;
use Tracy\Debugger;

class Users extends BaseModel
{

    public function findTickets($id)
    {
        return array_map(function ($ticket) {
            return array_merge(
                $ticket->toArray(),
                ['created_at' => $ticket->created_at->getTimestamp() * 1000],
                ['updated_at' => $ticket->updated_at->getTimestamp() * 1000],
                ['schema' => self::toArray($ticket->ref('schema'))],
                ['seats' => array_keys($ticket->related('reservations')->fetchPairs('id'))]
            );
        }, $this->database->table('users')->get($id)->related('tickets.user_id')->fetchPairs('id'));
    }

    public function create($parameters)
    {
        $user = $this->database->table($this->table)->insert($parameters);

        // TODO: test this
        // needs new token for authorization
        do {
            $parameters['token'] = Random::generate(128);
            try {
                $this->update($user->id, [
                    'token' => $parameters['token']
                ]);
                break;
            } catch (UniqueConstraintViolationException $e) {
            }
        } while (true);

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
            return array_merge(self::toArray($user),
                ['last_click_at' => $user->last_click_at !== null ? $user->last_click_at->getTimestamp() * 1000 : null]
            );
        }
    }

    public function all()
    {
        $users = parent::all();
        foreach ($users as &$user) {
            $user['last_click_at'] = $user['last_click_at'] !== null ? $user['last_click_at']->getTimestamp() * 1000 : null;
            unset($user['password']);
        }
        return $users;
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
            'tickets' => array_keys($user->related('tickets.user_id')->fetchPairs('id')),
            ['last_click_at' => $user->last_click_at !== null ? $user->last_click_at->getTimestamp() * 1000 : null]
        ]);
    }
}
