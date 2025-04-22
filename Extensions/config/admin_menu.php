<?php

namespace Croogo\Extensions\Config;

use Croogo\Core\Nav;

Nav::add('sidebar', 'extensions', [
    'icon' => 'magic',
    'title' => __d('croogo', 'Extensions'),
    'url' => [
        'prefix' => 'admin',
        'plugin' => 'Croogo/Extensions',
        'controller' => 'Plugins',
        'action' => 'index',
    ],
    'weight' => 35
]);
