<?php

namespace Croogo\Acl\Test\TestCase\Model\Behavior;

use Cake\ORM\Table;
use Cake\TestSuite\TestCase;

/**
 * `roleHierarchy` finder in an app skeleton, which disables the fallback
 * table class.
 *
 * Fixture AROs: admin (lft 3, parent 2), registered (lft 2, parent 3),
 * public (lft 1, no parent).
 */
class RoleAroBehaviorTest extends TestCase
{
    protected array $fixtures = [
        'plugin.Croogo/Users.Role',
        'plugin.Croogo/Users.Aro',
    ];

    protected Table $Roles;

    public function setUp(): void
    {
        parent::setUp();
        $this->Roles = $this->getTableLocator()->get('Roles', [
            'className' => Table::class,
            'table' => 'roles',
        ]);
        $this->Roles->addBehavior('Croogo/Acl.RoleAro');
        $this->getTableLocator()->allowFallbackClass(false);
    }

    public function tearDown(): void
    {
        $this->getTableLocator()->allowFallbackClass(true);
        unset($this->Roles);
        parent::tearDown();
    }

    protected function findHierarchy(): array
    {
        return $this->Roles->find('roleHierarchy')
            ->orderBy(['ParentAro.lft' => 'DESC'])
            ->all()
            ->map(fn ($role) => [$role->alias, $role->parent_id, $role->lft])
            ->toList();
    }

    public function testFindRoleHierarchyWithoutFallbackClass(): void
    {
        $this->assertSame([
            ['admin', 2, 3],
            ['registered', 3, 2],
            ['public', null, 1],
        ], $this->findHierarchy());
    }

    /**
     * Users admin runs the finder for both the filter and the form; Cake 5
     * throws when an association alias is added twice.
     */
    public function testFindRoleHierarchyTwice(): void
    {
        $first = $this->findHierarchy();

        $this->assertSame($first, $this->findHierarchy());
    }
}
