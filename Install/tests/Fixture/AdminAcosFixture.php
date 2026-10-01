<?php
declare(strict_types=1);

namespace Croogo\Install\Test\Fixture;

use Croogo\Core\TestSuite\CroogoTestFixture;

/**
 * `acos` holds the permission tree. AclBehavior::check() looks the request's node up
 * here, and UserAroBehavior writes grants into aros_acos against those ids, so the
 * table has to exist and be non-empty for a user save to get through.
 *
 * The `controllers` root mirrors what AclGenerator::insertAcos() creates during a real
 * install; the exact lft/rght values do not matter, only that the alias is resolvable.
 */
class AdminAcosFixture extends CroogoTestFixture
{
    public string $table = 'acos';

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
        [
            'id' => 1,
            'parent_id' => null,
            'model' => null,
            'foreign_key' => null,
            'alias' => 'controllers',
            'lft' => 1,
            'rght' => 4,
        ],
    ];
}
