<?php

namespace Croogo\Users\Model\Entity;

use Croogo\Core\Auth\DefaultPasswordHasher;
use Cake\ORM\Entity;

class User extends Entity
{

    protected array $_hidden = ['password', 'activation_key'];

    /**
     * Hashes password when setting
     *
     * @param string $password
     * @return bool|string
     */
    protected function _setPassword($password)
    {
        return (new DefaultPasswordHasher)->hash($password);
    }

    /**
     * parentNode
     *
     * @return array
     */
    public function parentNode()
    {
        if (!$this->id) {
            return null;
        }
        if (empty($this->get('role_id'))) {
            return null;
        } else {
            // Plugin-qualified, so AclNode::node() gets the real table class instead
            // of an auto-table, which apps with allowFallbackClass(false) refuse.
            return ['Croogo/Users.Roles' => ['id' => $this->get('role_id')]];
        }
    }
}
