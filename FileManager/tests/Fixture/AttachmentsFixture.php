<?php

namespace Croogo\FileManager\Test\Fixture;

use Croogo\Core\TestSuite\CroogoTestFixture;

class AttachmentsFixture extends CroogoTestFixture
{
    public string $table = 'attachments';

    public $fields = [
        'id' => ['type' => 'integer', 'null' => false, 'autoIncrement' => true],
        'folder_id' => ['type' => 'integer', 'null' => true, 'default' => null],
        'title' => ['type' => 'string', 'null' => true, 'default' => null],
        'slug' => ['type' => 'string', 'null' => true, 'default' => null],
        'body' => ['type' => 'text', 'null' => true, 'default' => null],
        'excerpt' => ['type' => 'text', 'null' => true, 'default' => null],
        'status' => ['type' => 'boolean', 'null' => false, 'default' => false],
        'sticky' => ['type' => 'boolean', 'null' => false, 'default' => false],
        'visibility_roles' => ['type' => 'text', 'null' => true, 'default' => null],
        'hash' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => 64],
        'plugin' => ['type' => 'string', 'null' => true, 'default' => null],
        'import_path' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => 512],
        'asset_count' => ['type' => 'integer', 'null' => true, 'default' => null],
        'created' => ['type' => 'datetime', 'null' => true, 'default' => null],
        'modified' => ['type' => 'datetime', 'null' => true, 'default' => null],
        'created_by' => ['type' => 'integer', 'null' => true, 'default' => null],
        'modified_by' => ['type' => 'integer', 'null' => true, 'default' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id']],
        ],
    ];

    public array $records = [
        ['id' => 1, 'folder_id' => null, 'title' => 'logo.png', 'slug' => 'logo.png', 'status' => true],
        ['id' => 2, 'folder_id' => null, 'title' => 'baner.jpg', 'slug' => 'baner.jpg', 'status' => true],
        ['id' => 3, 'folder_id' => 1, 'title' => 'produkt.jpg', 'slug' => 'produkt.jpg', 'status' => true],
        ['id' => 4, 'folder_id' => 2, 'title' => 'glowica.jpg', 'slug' => 'glowica.jpg', 'status' => true],
        ['id' => 5, 'folder_id' => 3, 'title' => 'mindray-1.jpg', 'slug' => 'mindray-1.jpg', 'status' => true],
        ['id' => 6, 'folder_id' => 3, 'title' => 'mindray-2.jpg', 'slug' => 'mindray-2.jpg', 'status' => true],
    ];
}
