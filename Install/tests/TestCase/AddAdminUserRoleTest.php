<?php
declare(strict_types=1);

namespace Croogo\Install\Test\TestCase\Model;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Croogo\Install\Model\Table\InstallTable;
use Croogo\Install\Test\Fixture\AdminRolesUsersFixture;
use Croogo\Install\Test\Fixture\AdminRolesFixture;
use Croogo\Install\Test\Fixture\AdminUsersFixture;
use Croogo\Users\Model\Table\RolesTable;
use Croogo\Users\Model\Table\UsersTable;

/**
 * Regression test for InstallTable::addAdminUser() not granting the role through the
 * HABTM join table.
 *
 * `Access Control.multiRole` switches UsersTable::initialize() from
 * belongsTo('Roles') (which reads users.role_id) to belongsToMany('Roles', ['through'
 * => 'Croogo/Users.RolesUsers']) (which reads roles_users). The installer only ever
 * wrote users.role_id, so an install that was perfectly fine with multiRole off
 * produced an admin with a role id and no roles the moment multiRole was turned on —
 * an app where the administrator cannot read a single ACL-protected page.
 *
 * The test asserts both rows exist, because the fix has to keep working with
 * multiRole off, and that a second run does not duplicate the grant.
 */
class AddAdminUserRoleTest extends TestCase
{
    protected array $fixtures = [
        'plugin.Croogo/Install.AdminUsers',
        'plugin.Croogo/Install.AdminRoles',
        'plugin.Croogo/Install.AdminRolesUsers',
        // UsersTable attaches Croogo/Acl.UserAro, whose afterSave() reads the user's
        // ARO with firstOrFail() and writes grants through aros_acos. Without these
        // rows the save dies with "AclNode::node() - Couldn't find node".
        'plugin.Croogo/Install.AdminAcos',
        'plugin.Croogo/Install.AdminAros',
        'plugin.Croogo/Install.AdminArosAcos',
    ];

    protected InstallTable $Install;

    protected function setUp(): void
    {
        parent::setUp();

        // TrackableBehavior fills created_by from Configure, and the column is
        // NOT NULL. InstallManager's constructor sets this for the CLI install; the
        // test has to stand in for it, because the command path is not under test here.
        if (!Configure::read('Trackable.Auth.User.id')) {
            Configure::write('Trackable.Auth.User', ['id' => 1, 'username' => 'admin']);
        }

        $Users = TableRegistry::getTableLocator()->get('Croogo/Users.Users');
        // Mirror the installer: it drops Cached so the save is visible, and
        // removeBehavior() throws when the behavior is not there.
        $Users->addBehavior('Croogo/Core.Cached', ['groups' => ['users']]);
        $Users->removeBehavior('Cached');

        $Roles = TableRegistry::getTableLocator()->get('Croogo/Users.Roles');
        $Roles->addBehavior('Croogo/Core.Aliasable');

        $this->Install = new InstallTable(['table' => 'install', 'alias' => 'Install']);
    }

    /**
     * The role must be readable both ways: through the column (multiRole off) and
     * through the join table (multiRole on).
     *
     * @return void
     */
    public function testAdminIsGrantedTheSuperadminRoleOnBothPaths(): void
    {
        $result = $this->Install->addAdminUser([
            'username' => 'admin',
            'password' => 'secret1234',
        ]);

        $this->assertNotFalse($result, 'addAdminUser() should have saved the user');

        $Users = TableRegistry::getTableLocator()->get('Croogo/Users.Users');
        $admin = $Users->find()->where(['username' => 'admin'])->firstOrFail();

        // The column: this is what the installer always wrote.
        $this->assertNotNull(
            $admin->get('role_id'),
            'users.role_id must still be set, that is how multiRole=off reads the role',
        );

        // The join table: this is what multiRole=on reads, and what was missing.
        $RolesUsers = TableRegistry::getTableLocator()->get('Croogo/Users.RolesUsers');
        $granted = $RolesUsers->find()
            ->where([
                'RolesUsers.user_id' => $admin->get('id'),
                'RolesUsers.role_id' => $admin->get('role_id'),
            ])
            ->count();

        $this->assertSame(
            1,
            $granted,
            'roles_users must hold the grant, otherwise multi-role gives the admin no roles',
        );
    }

    /**
     * Re-running the installer must not stack duplicate grants.
     *
     * @return void
     */
    public function testGrantIsIdempotent(): void
    {
        $this->Install->addAdminUser(['username' => 'admin', 'password' => 'secret1234']);
        $this->Install->addAdminUser(['username' => 'admin', 'password' => 'secret1234']);

        $RolesUsers = TableRegistry::getTableLocator()->get('Croogo/Users.RolesUsers');
        $this->assertSame(
            1,
            $RolesUsers->find()->where(['RolesUsers.user_id' => 1])->count(),
            'a second install run must not duplicate the roles_users row',
        );
    }

    /**
     * With multiRole switched ON, the association UsersTable declares must actually
     * resolve the role for the installed admin. This is the assertion that failed in
     * practice.
     *
     * @return void
     */
    public function testRolesAssociationResolvesUnderMultiRole(): void
    {
        // The whole point of the test: the association UsersTable builds depends on
        // this setting, so it has to be on before the table is first resolved.
        Configure::write('Access Control.multiRole', 1);
        TableRegistry::getTableLocator()->clear();

        $this->Install->addAdminUser(['username' => 'admin', 'password' => 'secret1234']);

        // Rebuild the table so initialize() re-reads the setting, exactly as a fresh
        // request would.
        TableRegistry::getTableLocator()->clear();
        $Users = TableRegistry::getTableLocator()->get('Croogo/Users.Users');
        $Users->addBehavior('Croogo/Core.Cached', ['groups' => ['users']]);
        $Users->removeBehavior('Cached');

        // SettingsTable::write() stores the flag as the string '1', and
        // UsersTable::initialize() only checks truthiness — so assert the truthy
        // value rather than a strict boolean, which is what the setting really is.
        $this->assertSame(
            1,
            (int)Configure::read('Access Control.multiRole'),
            'precondition: this test is only meaningful with multiRole on',
        );

        $admin = $Users->find()->contain(['Roles'])->where(['Users.username' => 'admin'])
            ->firstOrFail();

        $roles = $admin->get('roles');
        $this->assertNotEmpty($roles, 'belongsToMany must resolve the admin role');
    }

    protected function tearDown(): void
    {
        // The setting is global; leaving it on would change what the other tests in
        // this suite build.
        Configure::write('Access Control.multiRole', 0);
        TableRegistry::getTableLocator()->clear();

        parent::tearDown();
    }
}
