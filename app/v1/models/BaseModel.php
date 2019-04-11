<?php

namespace App\v1Module\Models;

use Nette\Application\BadRequestException;
use Nette\Database\Context;
use Nette\Reflection\ClassType;
use Nette\Utils\Strings;

class BaseModel
{

    /**
     * @var Context
     */
    protected $database;

    /**
     * @var string
     */
    protected $table;

    public function __construct(Context $database)
    {
        $this->database = $database;
        $this->table = Strings::lower(ClassType::from($this)->getShortName());
    }

    public function all()
    {
        return array_map([get_class(), 'toArray'], $this->database->table($this->table)->fetchPairs('id'));
    }

    public function create($parameters)
    {
        return self::toArray($this->database->table($this->table)->insert($parameters));
    }

    public function update($id, $parameters)
    {
        if (($entity = $this->database->table($this->table)->get($id)) === false) {
            throw new BadRequestException("Neznámé $id pro {$this->table}");
        }
        $entity->update($parameters);
        return self::toArray($entity);
    }

    public function delete($id)
    {
        if (($entity = $this->database->table($this->table)->get($id)) === false) {
            throw new BadRequestException("Neznámé $id pro {$this->table}");
        }
        $entity->delete();
    }

    public function find($id)
    {
        if (($entity = $this->database->table($this->table)->get($id)) === false) {
            throw new BadRequestException("Neznámé $id pro {$this->table}");
        }
        return self::toArray($entity);
    }

    /**
     * Get the active row of this table by a primary key.
     *
     * @param $id the primary key
     * @return the active row; or null if entity does not exist
     */
    public function entity($id)
    {
        return $this->database->table($this->table)->get($id) ?: null;
    }

    /**
     * Convert the argument to an array using ->toArray().
     *
     * @param $e entity
     * @return array representing the entity
     */
    public static function toArray($e)
    {
        return $e->toArray();
    }

    /**
     * Convert the whole result to an array of arrays representing entities.
     *
     * @param type $result
     * @return type
     */
    public static function resultToArray($result)
    {
        return array_map([get_class(), 'toArray'], $result);
    }
}
