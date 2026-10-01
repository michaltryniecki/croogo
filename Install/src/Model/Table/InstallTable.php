<?php

namespace Croogo\Install\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\File;

class InstallTable extends Table
{

    /**
     * name
     *
     * @var string
     */
    public $name = 'Install';

    /**
     * useTable
     *
     * @var string
     */
    public $useTable = false;

    /**
     *
     * @var CroogoPlugin
     */
    protected $_CroogoPlugin = null;

    /**
     * Create admin user
     *
     * @var array $user User datas
     * @return If user is created
     */
    public function addAdminUser($user)
    {
        $Users = \Cake\ORM\TableRegistry::getTableLocator()->get('Croogo/Users.Users');
        // removeBehavior() throws "Unknown object `Cached`" when the behavior is not
        // attached, and the table can legitimately be without it: UsersTable only adds
        // Croogo/Core.Cached in initialize(), which does not run when an app resolves
        // the table before the plugin is loaded. Guarding here keeps addAdminUser()
        // usable in that setup instead of dying on a cache-clear convenience.
        if ($Users->hasBehavior('Cached')) {
            $Users->removeBehavior('Cached');
        }
        $Roles = \Cake\ORM\TableRegistry::getTableLocator()->get('Croogo/Users.Roles');
        $Roles->addBehavior('Croogo/Core.Aliasable');
        // bin/cake install runs in one process: Roles may have been loaded (and its
        // alias list cached) before the roles seed ran, so byAlias() would miss and
        // the admin would be saved without a role.
        $Roles->getBehavior('Aliasable')->reload();
        $Users->getValidator('default')->remove('email')->remove('password');
        $user['name'] = $user['username'];
        $user['email'] = '';
        $user['timezone'] = 'UTC';
        $roleId = $Roles->getBehavior('Aliasable')->byAlias('superadmin');
        $user['role_id'] = $roleId;
        $user['status'] = true;
        $user['activation_key'] = md5(uniqid());
        $entity = $Users->get(1);
        $entity = $Users->patchEntity($entity, $user);
        if ($entity->getErrors()) {
            $this->err('Unable to create administrative user. Validation errors:');

            return $this->err($entity->getErrors());
        }
        $saved = $Users->save($entity);

        /*
         * Grant the role through the HABTM join table as well.
         *
         * With `Access Control.multiRole` enabled, UsersTable::initialize() declares
         * belongsToMany('Roles', ['through' => 'Croogo/Users.RolesUsers']) instead of
         * belongsTo('Roles'), so every role lookup goes through roles_users. Writing
         * only users.role_id left the freshly installed admin with a role id and no
         * roles at all — invisible until multiRole was switched on, and then the admin
         * could not read a single ACL-protected page.
         *
         * Writing the row here keeps the installer correct under both settings. The
         * insert is idempotent-guarded so re-running the installer does not duplicate
         * the grant, and it goes through the ORM so Trackable timestamps are set.
         */
        /*
         * Grant the role through the HABTM join table as well.
         *
         * With `Access Control.multiRole` enabled, UsersTable::initialize() declares
         * belongsToMany('Roles', ['through' => 'Croogo/Users.RolesUsers']) instead of
         * belongsTo('Roles'), so every role lookup goes through roles_users. Writing
         * only users.role_id left the freshly installed admin with a role id and no
         * roles at all — invisible until multiRole was switched on, and then the admin
         * could not read a single ACL-protected page.
         *
         * Writing the row here keeps the installer correct under both settings. The
         * insert is guarded so re-running the installer does not duplicate the grant,
         * and it goes through the ORM so Trackable timestamps are set.
         */
        if ($saved && $roleId) {
            $RolesUsers = \Cake\ORM\TableRegistry::getTableLocator()
                ->get('Croogo/Users.RolesUsers');
            $existing = $RolesUsers->find()
                ->where([
                    $RolesUsers->aliasField('user_id') => $entity->get('id'),
                    $RolesUsers->aliasField('role_id') => $roleId,
                ])
                ->count();
            if (!$existing) {
                $RolesUsers->saveOrFail($RolesUsers->newEntity([
                    'user_id' => $entity->get('id'),
                    'role_id' => $roleId,
                ]));
            }
        }
        return $saved;
    }
}
