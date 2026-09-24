<?php

namespace Croogo\FileManager\Test\Fixture;

use Croogo\Core\TestSuite\CroogoTestFixture;

class AssetsFixture extends CroogoTestFixture
{
    public string $table = 'assets';

    public $fields = [
        'id' => ['type' => 'integer', 'null' => false, 'autoIncrement' => true],
        'parent_asset_id' => ['type' => 'integer', 'null' => true, 'default' => null],
        'foreign_key' => ['type' => 'integer', 'null' => true, 'default' => null],
        'model' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => 64],
        'filename' => ['type' => 'string', 'null' => false],
        'filesize' => ['type' => 'integer', 'null' => true, 'default' => null],
        'width' => ['type' => 'integer', 'null' => true, 'default' => null],
        'height' => ['type' => 'integer', 'null' => true, 'default' => null],
        'mime_type' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => 32],
        'extension' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => 5],
        'hash' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => 64],
        'path' => ['type' => 'string', 'null' => false],
        'adapter' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => 32],
        'created' => ['type' => 'datetime', 'null' => true, 'default' => null],
        'modified' => ['type' => 'datetime', 'null' => true, 'default' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id']],
        ],
    ];

    public array $records = [];
}
