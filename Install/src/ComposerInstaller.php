<?php

namespace Croogo\Install;

use Cake\Composer\Installer\PluginInstaller;
use Composer\Composer;
use Composer\Script\Event;
use DirectoryIterator;

/**
 * Class ComposerInstaller
 */
class ComposerInstaller extends PluginInstaller
{

    /**
     * @param Event $event
     * @return void
     */
    public static function postAutoloadDump(Event $event)
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
