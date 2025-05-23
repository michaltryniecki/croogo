<?php
namespace Croogo\Settings\Configure\Engine;

use Cake\Cache\Cache;
use Cake\Core\Configure\ConfigEngineInterface;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Croogo\Settings\Model\Entity\Setting;
use function Croogo\Core\timerStart;
use function Croogo\Core\timerStop;

class DatabaseConfig implements ConfigEngineInterface
{
    /**
     * Read method is used for reading configuration information from sources.
     * These sources can either be static resources like files, or dynamic ones like
     * a database, or other datasource.
     *
     * @param string $key Key to read.
     * @return array An array of data to merge into the runtime configuration
     */
    public function read($key)
    {
        timerStart('Loading settings from database');

        $values = Cache::remember('configure-settings-' . $key, function () use ($key) {
            $settings = TableRegistry::get('Croogo/Settings.Settings')->find('list', [
                'keyField' => 'key',
                'valueField' => function (Setting $setting) {
                    if ($setting->type === 'integer') {
                        return (int)$setting->value;
                    }

                    return $setting->value;
                }
            ])->cache('configure-settings-query-' . $key, 'cached_settings')->toArray();

            $settings = Hash::expand($settings);

            return $settings;
        }, 'cached_settings');

        timerStop('Loading settings from database');

        return $values;
    }

    /**
     * Dumps the configure data into source.
     *
     * @param string $key The identifier to write to.
     * @param array $data The data to dump.
     * @return bool True on success or false on failure.
     */
    public function dump($key, array $data)
    {
        Log::debug($key);
        Log::debug($data);

        return true;
    }
}
