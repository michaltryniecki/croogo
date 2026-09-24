<?php

namespace Croogo\FileManager\Test\Fixture;

use Croogo\Core\TestSuite\CroogoTestFixture;

/**
 * Tree used by the tests:
 *
 *   Produkty (1)
 *     Głowice (2)
 *       Mindray (3)
 *     Aparaty (4)
 *   Strony (5)
 */
class AttachmentFoldersFixture extends CroogoTestFixture
{
    public string $table = 'attachment_folders';

    public $fields = [
        'id' => ['type' => 'integer', 'null' => false, 'autoIncrement' => true],
        'parent_id' => ['type' => 'integer', 'null' => true, 'default' => null],
        'lft' => ['type' => 'integer', 'null' => true, 'default' => null],
        'rght' => ['type' => 'integer', 'null' => true, 'default' => null],
        'name' => ['type' => 'string', 'null' => false, 'length' => 150],
        'slug' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => 150],
        'created' => ['type' => 'datetime', 'null' => true, 'default' => null],
        'modified' => ['type' => 'datetime', 'null' => true, 'default' => null],
        'created_by' => ['type' => 'integer', 'null' => true, 'default' => null],
        'modified_by' => ['type' => 'integer', 'null' => true, 'default' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id']],
        ],
    ];

    public array $records = [
        ['id' => 1, 'parent_id' => null, 'lft' => 1, 'rght' => 8, 'name' => 'Produkty', 'slug' => 'produkty'],
        ['id' => 2, 'parent_id' => 1, 'lft' => 2, 'rght' => 5, 'name' => 'Głowice', 'slug' => 'glowice'],
        ['id' => 3, 'parent_id' => 2, 'lft' => 3, 'rght' => 4, 'name' => 'Mindray', 'slug' => 'mindray'],
        ['id' => 4, 'parent_id' => 1, 'lft' => 6, 'rght' => 7, 'name' => 'Aparaty', 'slug' => 'aparaty'],
        ['id' => 5, 'parent_id' => null, 'lft' => 9, 'rght' => 10, 'name' => 'Strony', 'slug' => 'strony'],
    ];
}
