<?php

use Cake\Log\LogTrait;
use Cake\ORM\TableRegistry;
use Croogo\Core\Database\SequenceFixer;
use Migrations\BaseSeed;

class UsersSeed extends BaseSeed
{
    use LogTrait;

    public $record = [
        'id' => 1,
        'role_id' => 3,
        'username' => 'seed',
        'name' => 'Seed User',
        'email' => 'seed@example.com',
        'status' => false,
        'timezone' => 'UTC',
        'created_by' => 1,
    ];

    public function getDependencies(): array
    {
        return [
            'RolesSeed',
        ];
    }

    public function run(): void
    {
        $this->getAdapter()->commitTransaction();
        $Users = TableRegistry::getTableLocator()->get('Croogo/Users.Users');
        $entity = $Users->newEntity($this->record);
        $result = $Users->save($entity);
        // Records carry explicit ids, move the Postgres sequence past them.
        (new SequenceFixer())->fix($this->getAdapter()->getConnection(), ['users']);
        $this->getAdapter()->beginTransaction();
    }
}
