<?php

namespace App\v1Module\Security;

use Nette\Security\Identity;

class User extends Identity
{

    /**
     * Check if the user has a role assigned to him.
     *
     * @param $role role name
     * @return bool true if this user has the role
     */
    public function isInRole($role)
    {
        return in_array($role, $this->getRoles());
    }
}