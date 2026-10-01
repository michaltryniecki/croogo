<?php
declare(strict_types=1);

namespace Croogo\Install\Test\Fixture;

use Croogo\Core\TestSuite\CroogoTestFixture;

/**
 * The HABTM join table. It starts EMPTY on purpose: that is the state the installer
 * used to leave behind, and the reason a fresh install with `multiRole` enabled gave
 * the admin no roles at all.
 *
 * Schema mirrors Users\config\Migrations\20160807105314_UsersInitialMigration.php;
 * `granted_by` is nullable, so the fix legitimately leaves it NULL.
 */
class AdminRolesUsersFixture extends CroogoTestFixture
{
    public string $table = 'roles_users';

    public $fields = [
        'id' => ['type' => 'integer', 'null' => false, 'default' => null],
        'user_id' => ['type' => 'integer', 'null' => false, 'default' => null],
        'role_id' => ['type' => 'integer', 'null' => false, 'default' => null],
        'granted_by' => ['type' => 'integer', 'null' => true, 'default' => null],
        'created' => ['type' => 'datetime', 'null' => true, 'default' => null],
        'modified' => ['type' => 'datetime', 'null' => true, 'default' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id']],
            'unique_pair' => ['type' => 'unique', 'columns' => ['user_id', 'role_id']],
        ],
    ];

    public array $records = [];
}
