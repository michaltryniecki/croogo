<?php
declare(strict_types=1);

namespace Croogo\Install\Test\Fixture;

use Croogo\Core\TestSuite\CroogoTestFixture;

/**
 * Just enough roles for the superadmin alias lookup the installer performs.
 *
 * Schema mirrors Users\config\Migrations\20160807105314_UsersInitialMigration.php.
 */
class AdminRolesFixture extends CroogoTestFixture
{
    public string $table = 'roles';

    public $fields = [
        'id' => ['type' => 'integer', 'null' => false, 'default' => null],
        'title' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 100],
        'alias' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => 100],
        'created' => ['type' => 'datetime', 'null' => true, 'default' => null],
        'modified' => ['type' => 'datetime', 'null' => true, 'default' => null],
        // Nullable on purpose: the shipped Acl RolesFixture inserts rows without these
        // columns, and this table is shared by every suite. NOT NULL here made those
        // fixtures fail with "NOT NULL constraint failed: roles.created_by".
        'created_by' => ['type' => 'integer', 'null' => true, 'default' => null],
        'modified_by' => ['type' => 'integer', 'null' => true, 'default' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id']],
            'unique_alias' => ['type' => 'unique', 'columns' => ['alias']],
        ],
    ];

    public array $records = [
        [
            'id' => 1,
            'title' => 'SuperAdmin',
            'alias' => 'superadmin',
            'created' => '2026-01-01 00:00:00',
            'created_by' => 1,
        ],
    ];
}
