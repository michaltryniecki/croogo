<?php
declare(strict_types=1);

namespace Croogo\Install\Test\Fixture;

use Croogo\Core\TestSuite\CroogoTestFixture;

/**
 * `aros` is required because Croogo/Users\Users attaches Croogo/Acl.UserAro, whose
 * afterSave() syncs the user's role into the ARO tree and therefore describes the
 * table. Saving a user without it dies with "Cannot describe aros".
 *
 * Schema mirrors the Acl plugin's migration.
 */
class AdminArosFixture extends CroogoTestFixture
{
    public string $table = 'aros';

    public $fields = [
        'id' => ['type' => 'integer', 'null' => false, 'default' => null],
        'parent_id' => ['type' => 'integer', 'null' => true, 'default' => null],
        'model' => ['type' => 'string', 'null' => true, 'default' => null],
        'foreign_key' => ['type' => 'integer', 'null' => true, 'default' => null],
        'alias' => ['type' => 'string', 'null' => true, 'default' => null],
        'lft' => ['type' => 'integer', 'null' => true, 'default' => null],
        'rght' => ['type' => 'integer', 'null' => true, 'default' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id']],
        ],
    ];

    public array $records = [
        // UserAroBehavior::afterSave() looks the role's ARO up by
        // (model = 'Roles', foreign_key = role id) and throws
        // "AclNode::node() - Couldn't find node" when it is missing.
        [
            'id' => 1,
            'parent_id' => null,
            'model' => 'Roles',
            'foreign_key' => 1,
            'alias' => 'Role-superadmin',
            'lft' => 1,
            'rght' => 2,
        ],
        // The user's own ARO. AclBehavior::afterSave() creates it on save and
        // UserAroBehavior::afterSave() then reads it back with firstOrFail(); on a
        // database where the nested-set columns are not written the row never
        // appears, and the read is what throws. Seeded explicitly so the test does
        // not depend on that ordering.
        [
            'id' => 2,
            'parent_id' => null,
            'model' => 'Users',
            'foreign_key' => 1,
            'alias' => 'admin',
            'lft' => 3,
            'rght' => 4,
        ],
    ];
}
