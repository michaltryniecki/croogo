<?php
declare(strict_types=1);

namespace Croogo\Install\Test\Fixture;

use Croogo\Core\TestSuite\CroogoTestFixture;

/**
 * The row InstallTable::addAdminUser() always assumes exists: it does
 * `$Users->get(1)` and patches that entity, so the installer is an "update user 1"
 * operation wearing a "create admin" name.
 *
 * Schema mirrors Users\config\Migrations\20160807105314_UsersInitialMigration.php.
 */
class AdminUsersFixture extends CroogoTestFixture
{
    public string $table = 'users';

    public $fields = [
        'id' => ['type' => 'integer', 'null' => false, 'default' => null],
        'role_id' => ['type' => 'integer', 'null' => false, 'default' => null],
        'username' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 60],
        'password' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => 100],
        'name' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 50],
        'email' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 100],
        'website' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => 100],
        'activation_key' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => 60],
        'image' => ['type' => 'string', 'null' => true, 'default' => null],
        'bio' => ['type' => 'text', 'null' => true, 'default' => null],
        'status' => ['type' => 'boolean', 'null' => false, 'default' => 0],
        'timezone' => ['type' => 'string', 'null' => false, 'default' => 'UTC', 'length' => 40],
        'created' => ['type' => 'datetime', 'null' => true, 'default' => null],
        'modified' => ['type' => 'datetime', 'null' => true, 'default' => null],
        'created_by' => ['type' => 'integer', 'null' => false, 'default' => null],
        'modified_by' => ['type' => 'integer', 'null' => true, 'default' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id']],
        ],
    ];

    public array $records = [
        [
            'id' => 1,
            'role_id' => 1,
            'username' => 'admin',
            'password' => '',
            'name' => 'admin',
            'email' => '',
            'status' => 1,
            'timezone' => 'UTC',
            'created' => '2026-01-01 00:00:00',
            'created_by' => 1,
        ],
    ];
}
