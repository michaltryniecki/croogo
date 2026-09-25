<?php
/** @var \Cake\Routing\RouteBuilder $routes */
use Cake\Core\Configure;
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Croogo\Core\Utility\StringConverter;

$routes->prefix('admin', function (RouteBuilder $routeBuilder) {
    // Plugin routes load after the app's, so an app that registered its own
    // `csrf` (cookie options, skipped actions) would silently lose it here.
    if (!Router::getRouteCollection()->hasMiddleware('csrf')) {
        $routeBuilder->registerMiddleware('csrf', new CsrfProtectionMiddleware());
    }
    $routeBuilder->applyMiddleware('csrf');

    $dashboardUrl = Configure::read('Site.dashboard_url');
    if (!$dashboardUrl) {
        return;
    }

    if (is_string($dashboardUrl)) {
        $converter = new StringConverter();
        $dashboardUrl = $converter->linkStringToArray($dashboardUrl);
    }

    $routeBuilder->connect('/', $dashboardUrl);
});

$routes->plugin('Croogo/Core', ['path' => '/'], function (RouteBuilder $routeBuilder) {
    $routeBuilder->prefix('Admin', function (RouteBuilder $routeBuilder) {
        $routeBuilder->setExtensions(['json']);

        $routeBuilder->connect('/link-chooser/*', ['controller' => 'LinkChooser', 'action' => 'linkChooser']);
    });
});
