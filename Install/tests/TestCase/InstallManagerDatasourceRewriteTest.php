<?php
declare(strict_types=1);

namespace Croogo\Install\Test\TestCase;

use Cake\Core\Configure;
use Croogo\Install\InstallManager;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Regression tests for InstallManager::_updateDatasourceConfig().
 *
 * The rewrite built its replacement as
 *
 *     '$1' . addslashes($value) . '$2'
 *
 * In preg_replace() that string is a TEMPLATE, not literal text: `$1` and `$2` are
 * group references, and so is every `$<digits>` that follows. A value whose first
 * character is a digit is therefore read as a higher-numbered group:
 *
 *     host => 127.0.0.1   gives the template `$1127.0.0.1$2`, where `$11` is
 *                         group 11 -> the whole line collapses to `27.0.0.1',`
 *
 * which is a PHP parse error, and createDatabaseFile() returned true regardless
 * because the return value of preg_replace() was never inspected. `port` is not
 * affected (createDatabaseFile() never passes it), but any host given as a literal
 * address is.
 *
 * What the old pattern did NOT do, despite appearances: the trailing `.*` is greedy,
 * but the second capture group puts the final comma back, so a single field is still
 * replaced in practice for ordinary values. The defect is the interpolation alone.
 * The tests below pin that distinction down, so a future "optimisation" back to a
 * single template does not come back unnoticed.
 *
 * Pure string in / string out: no database, no application bootstrap.
 */
class InstallManagerDatasourceRewriteTest extends TestCase
{
    /**
     * Config shaped like the CakePHP app skeleton, with the `port` key commented out
     * exactly as the skeleton and croogo's own tests/test_app/config/app.php ship it.
     *
     * @var string
     */
    protected const APP_CONFIG = <<<'PHP'
<?php
return [
    'EmailTransport' => [
        'default' => [
            'host' => 'localhost',
            'port' => 25,
        ],
    ],
    'Datasources' => [
        'default' => [
            'className' => 'Cake\Database\Connection',
            'driver' => 'Cake\Database\Driver\Mysql',
            'persistent' => false,
            'host' => 'localhost',
            //'port' => 'nonstandard_port_number',
            'username' => 'my_app',
            'password' => 'secret',
            'database' => 'my_app',
        ],
        'test' => [
            'className' => 'Cake\Database\Connection',
            'host' => 'localhost',
            'username' => 'my_app',
            'password' => 'secret',
            'database' => 'test_my_app',
        ],
    ],
];

PHP;

    /**
     * @var string
     */
    protected string $path;

    protected function setUp(): void
    {
        parent::setUp();
        $this->path = (string)tempnam(sys_get_temp_dir(), 'app-config-') . '.php';
        file_put_contents($this->path, static::APP_CONFIG);
    }

    protected function tearDown(): void
    {
        if (is_file($this->path)) {
            unlink($this->path);
        }
        parent::tearDown();
    }

    /**
     * The class surface itself, not just the datasource rewrite.
     *
     * While replacing _updateDatasourceConfig() a helper script cut the region
     * between the two markers and took five members with it: $defaultConfig,
     * $_croogoPlugin, $controller, __construct() and versionCheck(). Only the
     * datasource tests noticed, and they noticed it late — the first symptom was a
     * TypeError on an install that had nothing to do with config rewriting.
     *
     * A whitelist, not a reflection dump: it states what this class owes its callers
     * and fails the moment one is missing, which is the check that would have caught
     * the original accident.
     *
     * @return void
     */
    public function testClassKeepsItsPublicSurface(): void
    {
        $manager = new InstallManager();

        foreach (['defaultConfig', 'controller'] as $property) {
            $this->assertTrue(
                property_exists($manager, $property),
                sprintf('InstallManager::$%s must exist', $property),
            );
        }

        // Read by _getCroogoPlugin(); a missing declaration is a warning first and a
        // broken migration run second.
        $reflection = new ReflectionClass($manager);
        $this->assertTrue(
            $reflection->hasProperty('_croogoPlugin'),
            'InstallManager::$_croogoPlugin is read by _getCroogoPlugin()',
        );

        // The constructor seeds Trackable.Auth.User.id, which is what keeps
        // settings.created_by (int NOT NULL) writable during the install.
        $this->assertTrue(
            $reflection->getConstructor() !== null,
            'InstallManager::__construct() seeds Trackable.Auth.User.id',
        );
        $this->assertSame(
            1,
            Configure::read('Trackable.Auth.User.id'),
            'the constructor must set Trackable.Auth.User.id to 1',
        );

        // Public API, called by the installer UI.
        $this->assertTrue(
            $reflection->hasMethod('versionCheck'),
            'InstallManager::versionCheck() is public API',
        );
    }

    /**
     * createDatabaseFile() does `$config += $this->defaultConfig` as its very first
     * statement, so the property has to exist on the class.
     *
     * It was dropped by accident while the rewrite method was being replaced, and the
     * regression was invisible: every test in this file drives
     * _updateDatasourceConfig() through reflection, and the suite that runs
     * createDatabaseFile() for real (crm-v2's install smoke test) was not here. The
     * first symptom was a TypeError, "Unsupported operand types: array + null", on an
     * otherwise clean install.
     *
     * @return void
     */
    public function testDefaultConfigPropertyExistsAndIsComplete(): void
    {
        $manager = new InstallManager();

        $this->assertTrue(
            property_exists($manager, 'defaultConfig'),
            'InstallManager::$defaultConfig is required by createDatabaseFile()',
        );

        $config = $manager->defaultConfig;
        $this->assertIsArray($config);

        foreach (['driver', 'host', 'username', 'password', 'database', 'encoding'] as $key) {
            $this->assertArrayHasKey(
                $key,
                $config,
                "defaultConfig must default `$key`, createDatabaseFile() reads it unconditionally",
            );
        }

        // The encoding default is load-bearing: createDatabaseFile() compares it to
        // 'utf8' to decide whether to switch the connection to utf8mb4. A null there
        // would silently skip the languages seed that needs 4-byte characters.
        $this->assertSame('utf8', $config['encoding']);
    }

    /**
     * Calls the protected method without booting an application.
     *
     * @param string $field The datasource key to rewrite.
     * @param string $value The value to write.
     * @return void
     */
    protected function rewrite(string $field, string $value): void
    {
        $manager = new InstallManager();
        $method = new \ReflectionMethod($manager, '_updateDatasourceConfig');
        $method->setAccessible(true);
        $method->invoke($manager, $this->path, $field, $value);
    }

    /**
     * @return string The rewritten file.
     */
    protected function config(): string
    {
        return (string)file_get_contents($this->path);
    }

    /**
     * The core defect: an IPv4 literal must land verbatim, not be read as a
     * backreference. Before the fix this produced `27.0.0.1',`.
     *
     * @return void
     */
    public function testWritesAnIpv4HostVerbatim(): void
    {
        $this->rewrite('host', '127.0.0.1');

        $this->assertStringContainsString("'host' => '127.0.0.1',", $this->config());
    }

    /**
     * The rewritten file must still be valid PHP — that is what actually broke.
     *
     * @return void
     */
    public function testRewritingAnIpv4HostLeavesTheFileParseable(): void
    {
        $this->rewrite('host', '127.0.0.1');

        $this->assertNoSyntaxError($this->config());
    }

    /**
     * Same, for a key whose value is a bare number, in a config that actually
     * declares it (the skeleton's `port` is commented out, so the default fixture
     * cannot be used here).
     *
     * @return void
     */
    public function testWritesANumericPortWhenTheKeyIsActive(): void
    {
        $file = str_replace(
            "            //'port' => 'nonstandard_port_number',",
            "            'port' => '3306',",
            static::APP_CONFIG,
        );
        file_put_contents($this->path, $file);

        $this->rewrite('port', '3309');

        $config = $this->config();
        $this->assertStringContainsString("'port' => '3309',", $config);
        $this->assertNoSyntaxError($config);
    }

    /**
     * A value that itself contains a digit group after a backreference-looking run,
     * e.g. a database name, must not be reinterpreted either.
     *
     * @return void
     */
    public function testWritesAValueStartingWithDigitsVerbatim(): void
    {
        $this->rewrite('database', '2024_crm');

        $config = $this->config();
        $this->assertStringContainsString("'database' => '2024_crm',", $config);
        $this->assertNoSyntaxError($config);
    }

    /**
     * The ordinary case must keep working: a plain word value.
     *
     * @return void
     */
    public function testWritesAHostNameVerbatim(): void
    {
        $this->rewrite('host', 'db');

        $this->assertStringContainsString("'host' => 'db',", $this->config());
    }

    /**
     * Only the `default` block may change. `host` also appears in EmailTransport and
     * in the `test` datasource, and `port` in EmailTransport as a bare integer.
     *
     * @return void
     */
    public function testTouchesOnlyTheDefaultDatasourceBlock(): void
    {
        $this->rewrite('host', 'db');

        $config = $this->config();
        $this->assertSame(1, substr_count($config, "'host' => 'db',"));
        $this->assertStringContainsString("'host' => 'localhost',\n            'port' => 25,", $config);
    }

    /**
     * A commented-out copy of the key must neither be rewritten nor hide the active
     * one. croogo's own tests/test_app/config/app.php has exactly this shape.
     *
     * @return void
     */
    public function testRewritesTheActiveKeyAndLeavesTheCommentedOneAlone(): void
    {
        $this->rewrite('username', 'crm');

        $config = $this->config();
        $this->assertStringContainsString("'username' => 'crm',", $config);
        $this->assertStringContainsString("//'port' => 'nonstandard_port_number',", $config);
    }

    /**
     * A key that is not an active entry is now an explicit failure. The old code
     * wrote the file back unchanged and reported success.
     *
     * @return void
     */
    public function testThrowsWhenTheKeyIsNotAnActiveEntry(): void
    {
        $file = str_replace("'database' => 'my_app',\n", '', static::APP_CONFIG);
        file_put_contents($this->path, $file);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not found as an active entry');

        $this->rewrite('database', 'crm');
    }

    /**
     * A quote in the value is escaped rather than injected, and the file still parses.
     *
     * @return void
     */
    public function testEscapesQuotesInTheValueAndKeepsTheFileParseable(): void
    {
        $this->rewrite('password', "se'cret");

        $config = $this->config();
        $this->assertStringContainsString("'password' => 'se\\'cret',", $config);
        $this->assertNoSyntaxError($config);
    }

    /**
     * @param string $code PHP source to check.
     * @return void
     */
    protected function assertNoSyntaxError(string $code): void
    {
        $file = (string)tempnam(sys_get_temp_dir(), 'app-lint-') . '.php';
        file_put_contents($file, $code);
        exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $out, $status);
        unlink($file);

        $this->assertSame(0, $status, 'rewritten config is not valid PHP: ' . implode("\n", $out));
    }
}
