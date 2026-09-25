<?php

use Cake\Cache\Cache;
use Cake\Core\App;
use Cake\Core\Configure;
use Croogo\Core\Croogo;

/*
 * UserAro/RoleAro are hooked unconditionally by PluginManager::croogoBootstrap()
 * and clear this cache group on every save, so the config has to exist even before
 * the settings are in the database (seeding roles, the installer). Guarded, because
 * an app may already have registered it.
 */
if (!in_array('permissions', Cache::configured(), true)) {
    Cache::setConfig('permissions', [
        'duration' => '+1 hour',
        'path' => CACHE . 'acl' . DS,
        'groups' => ['acl']
    ] + (Configure::read('Croogo.Cache.defaultConfig') ?: Cache::getConfig('default')));
}

if (Configure::read('Site.acl_plugin') == 'Croogo/Acl') {
    // activate AclFilter component only until after a succesfull install
    if (Configure::read('Croogo.installed')) {
        Croogo::hookComponent('*', 'Croogo/Acl.Filter');
        Croogo::hookComponent('*', 'Croogo/Acl.Access');
    }

    Croogo::hookBehavior('Croogo/Users.Users', 'Croogo/Acl.UserAro', ['priority' => 20]);
    Croogo::hookBehavior('Croogo/Users.Roles', 'Croogo/Acl.RoleAro', ['priority' => 20]);

    if (Configure::read('Access Control.multiRole')) {
        Configure::write('Acl.classname', App::className('Croogo/Acl.HabtmDbAcl', 'Adapter'));
    }
}
