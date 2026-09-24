<?php

namespace Croogo\FileManager\Test\Fixture;

use Croogo\Core\TestSuite\CroogoTestFixture;

class AssetUsagesFixture extends CroogoTestFixture
{
    public string $table = 'asset_usages';

    public $fields = [
        'id' => ['type' => 'integer', 'null' => false, 'autoIncrement' => true],
        'asset_id' => ['type' => 'integer', 'null' => false],
        'model' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => 64],
        'foreign_key' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => 36],
        'type' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => 20],
        'url' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => 512],
        'params' => ['type' => 'text', 'null' => true, 'default' => null],
        'created' => ['type' => 'datetime', 'null' => true, 'default' => null],
        'modified' => ['type' => 'datetime', 'null' => true, 'default' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id']],
        ],
    ];

    public array $records = [];
}
