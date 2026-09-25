<?php
declare(strict_types=1);

namespace Croogo\Core\Test\TestCase;

use Cake\Cache\Cache;
use Cake\Core\PluginApplicationInterface;
use Cake\TestSuite\TestCase;
use Croogo\Core\PluginManager;

/**
 * PluginManager::setup() registers the caches Core itself relies on.
 */
class PluginManagerSetupTest extends TestCase
{
    /**
     * @var array|null
     */
    protected $croogoMenusConfig;

    /**
     * @var string
     */
    protected $timezone;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->croogoMenusConfig = Cache::getConfig('croogo_menus');
        Cache::drop('croogo_menus');
        $this->timezone = date_default_timezone_get();
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        Cache::drop('croogo_menus');
        if ($this->croogoMenusConfig) {
            Cache::setConfig('croogo_menus', $this->croogoMenusConfig);
        }
        date_default_timezone_set($this->timezone);

        parent::tearDown();
    }

    /**
     * The admin navigation element caches into croogo_menus, so an app that
     * does not load Menus still needs the config.
     *
     * @return void
     */
    public function testSetupConfiguresMenusCacheWithoutMenusPlugin(): void
    {
        PluginManager::setup($this->createStub(PluginApplicationInterface::class));

        $this->assertContains('croogo_menus', Cache::configured());
        $this->assertSame(['menus'], Cache::getConfig('croogo_menus')['groups']);
    }
}
