<?php

namespace Croogo\Settings\Config;

use Croogo\Core\Nav;

Nav::add('sidebar', 'settings', [
    'icon' => 'cog',
    'title' => __d('croogo', 'Settings'),
    'url' => [
        'prefix' => 'admin',
        'plugin' => 'Croogo/Settings',
        'controller' => 'Settings',
        'action' => 'prefix',
        'Site',
    ],
    'weight' => 60
]);
