<?php

namespace Croogo\Install;

use Composer\Composer;
use Composer\Script\Event;
use DirectoryIterator;

/**
 * Class ComposerInstaller
 *
 * Cake 5 / plugin-installer 2.x: PluginInstaller usunięty, a postAutoloadDump
 * i tak niczego nie dziedziczył — samodzielna klasa statyczna.
 */
class ComposerInstaller
{

    /**
     * @param Event $event
     * @return void
     */
    public static function postAutoloadDump(Event $event): void
    {
        $composer = $event->getComposer();
        $config = $composer->getConfig();
        $vendorDir = realpath($config->get('vendor-dir'));

        $corePlugins = [
            'Acl', 'Core', 'Dashboards', 'Extensions', 'FileManager', 'Install', 'Menus',
            'Settings', 'Users'
        ];

        $plugins = [];
        foreach ($corePlugins as $plugin) {
            $plugins[] = "\t\t'Croogo/" . $plugin . "' => \$baseDir . '" .
                DIRECTORY_SEPARATOR . "vendor" .
                DIRECTORY_SEPARATOR . "croogo" .
                DIRECTORY_SEPARATOR . "croogo" .
                DIRECTORY_SEPARATOR . $plugin .
                DIRECTORY_SEPARATOR . "',";
        }
        $plugins[] = "\t],";

        $cakephp_plugins = file_get_contents( $vendorDir . DIRECTORY_SEPARATOR . 'cakephp-plugins.php' );

        if ($cakephp_plugins !== false) {
            $cakephp_plugins = str_replace("],", implode("\n", $plugins), $cakephp_plugins);
            file_put_contents($vendorDir . DIRECTORY_SEPARATOR . 'cakephp-plugins.php', $cakephp_plugins);
        }
    }
}
