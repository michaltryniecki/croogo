<?php

namespace Croogo\FileManager\Test\Fixture;

use Croogo\Core\TestSuite\CroogoTestFixture;

/**
 * Bare `users` table: TrackableBehavior sets up a belongsTo Users on every
 * table with created_by/modified_by, and initialising UsersTable describes it.
 */
class UsersFixture extends CroogoTestFixture
{
    public string $table = 'users';

    public $fields = [
        'id' => ['type' => 'integer', 'null' => false, 'autoIncrement' => true],
        'role_id' => ['type' => 'integer', 'null' => true, 'default' => null],
        'username' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => 60],
        'name' => ['type' => 'string', 'null' => true, 'default' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id']],
        ],
    ];

    public array $records = [];
}
