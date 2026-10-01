<?php
declare(strict_types=1);

namespace Croogo\Install\Test\Fixture;

use Croogo\Core\TestSuite\CroogoTestFixture;

/**
 * The ARO/ACO join table, same reason as AdminArosFixture: UserAro reads and writes
 * it while syncing the role on save.
 *
 * Named `aros_acos` (not `aros_acl`) — that is the table the Acl plugin migrates; the
 * CakePHP 2-era docs called it aroc_acl/aro_acl.
 */
class AdminArosAcosFixture extends CroogoTestFixture
{
    public string $table = 'aros_acos';

    public $fields = [
        'id' => ['type' => 'integer', 'null' => false, 'default' => null],
        'aro_id' => ['type' => 'integer', 'null' => false, 'default' => null],
        'aco_id' => ['type' => 'integer', 'null' => false, 'default' => null],
        '_create' => ['type' => 'string', 'null' => false, 'default' => '0', 'length' => 2],
        '_read' => ['type' => 'string', 'null' => false, 'default' => '0', 'length' => 2],
        '_update' => ['type' => 'string', 'null' => false, 'default' => '0', 'length' => 2],
        '_delete' => ['type' => 'string', 'null' => false, 'default' => '0', 'length' => 2],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id']],
        ],
    ];

    public array $records = [];
}
