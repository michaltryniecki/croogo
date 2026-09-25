<?php

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Croogo\Core\Croogo;

// Guarded: integration tests bootstrap the app once per request in one process.
if (!in_array('croogo_menus', Cache::configured(), true)) {
    Cache::setConfig('croogo_menus', array_merge(
        Configure::read('Croogo.Cache.defaultConfig'),
        ['groups' => ['menus']]
    ));
}

Croogo::hookHelper('*', 'Croogo/Menus.Menus');

Croogo::translateModel('Croogo/Menus.Links', [
    'fields' => [
        'title',
        'description',
    ],
]);
