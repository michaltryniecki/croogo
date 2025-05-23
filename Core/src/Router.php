<?php

namespace Croogo\Core;

use Cake\Core\Configure;
use Cake\Database\Exception\MissingConnectionException;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router as CakeRouter;
use Cake\Utility\Inflector;
use Croogo\Core\Utility\StringConverter;

/**
 * Router
 *
 * NOTE: Do not use this class as a substitute of Router class.
 * Use it only for Router::connect()
 *
 * @package  Croogo.Croogo.Lib
 * @version  1.0
 * @author   Fahad Ibnay Heylaal <contact@fahad19.com>
 * @license  http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link     http://www.croogo.org
 */
class Router extends CakeRouter
{

    /**
     * Helper method to setup both default and localized route
     */
    public static function build(RouteBuilder $builder, $path, $defaults, $options = [])
    {
        if (PluginManager::isLoaded('Croogo/Translate')) {
            $languages = Configure::read('I18n.languages');
            $i18nPath = '/:lang' . $path;
            $i18nOptions = array_merge($options, ['lang' => implode('|', $languages)]);
            $builder->connect($i18nPath, $defaults, $i18nOptions);
        }
        $builder->connect($path, $defaults, $options);
    }

    /**
     * Check wether request is a API call.
     *
     * @see Request::addDetector()
     * @param $request Request Request object
     * @return bool True when request contains the necessary route parameters
     */
    public static function isApiRequest(ServerRequest $request)
    {
        if (!$request) {
            return false;
        }
        if (empty($request['api']) || empty($request['prefix'])) {
            return false;
        }
//        if ($request['api'] !== Configure::read('Croogo.Api.path')) {
//            return false;
//        }

        return true;
    }

    /**
     * Check wether request is from a whitelisted IP address
     *
     * @see Request::addDetector()
     * @param $request Request Request object
     * @return bool True when request is from a whitelisted IP Address
     */
    public static function isWhitelistedRequest(ServerRequest $request)
    {
        if (!$request) {
            return false;
        }

        $clientIp = $request->clientIp();
        $whitelist = array_map(
            'trim',
            (array)explode(',', Configure::read('Site.ipWhitelist'))
        );

        return in_array($clientIp, $whitelist);
    }

    /**
     * Creates REST resource routes for the given controller(s).
     *
     * @param string|array $controller string or array of controller names
     * @return array Array of mapped resources
     * @see Router::mapResources()
     */
    public static function mapResources($controller, $options = [])
    {
        $options = array_merge([
            'connectOptions' => [
                'Croogo\Core\Routing\Route\ApiRoute',
            ],
        ], $options);

        return static::mapResources($controller, $options);
    }

    /**
     * If you want your non-routed controler actions (like /users/add) to support locale based urls,
     * this method must be called AFTER all the routes.
     *
     * @return void
     */
    public static function localize()
    {
        if (PluginManager::isLoaded('Croogo/Translate')) {
            static::connect('/:locale/:plugin/:controller/:action/*', [], ['locale' => '[a-z]{3}']);
            static::connect('/:locale/:controller/:action/*', [], ['locale' => '[a-z]{3}']);
        }
    }

    /**
     * Routes for content types
     *
     * @param string $aliasRegex
     * @return void
     */
    public static function contentType($aliasRegex, $routeBuilder)
    {
        static::build($routeBuilder, '/:type', [
            'plugin' => 'Croogo/Nodes', 'controller' => 'Nodes',
            'action' => 'index',
        ], [
            'type' => $aliasRegex,
        ]);
        static::build($routeBuilder, '/:type/archives/*', [
            'plugin' => 'Croogo/Nodes', 'controller' => 'Nodes',
            'action' => 'index',
        ], [
            'type' => $aliasRegex,
        ]);
        static::build($routeBuilder, '/:type/:slug', [
            'plugin' => 'Croogo/Nodes', 'controller' => 'Nodes',
            'action' => 'view',
        ], [
            'type' => $aliasRegex,
            'slug' => '[a-z0-9-_]+',
        ]);
        static::build($routeBuilder, '/:type/term/:term/*', [
            'plugin' => 'Croogo/Nodes', 'controller' => 'Nodes',
            'action' => 'term',
        ], [
            'type' => $aliasRegex,
        ]);
        static::build($routeBuilder, '/:type/:vocab/:term/*', [
            'plugin' => 'Croogo/Nodes', 'controller' => 'Nodes',
            'action' => 'term',
        ], [
            'type' => $aliasRegex,
        ]);
    }

    /**
     * Apply routes for content types with routes enabled
     *
     * @return void
     */
    public static function routableContentTypes($routeBuilder)
    {
        try {
            $types = TableRegistry::get('Croogo/Taxonomy.Types')->find('all', [
                'cache' => [
                    'name' => 'types',
                    'config' => 'croogo_types',
                ],
            ]);
            $aliases = [];
            foreach ($types as $type) {
                if (isset($type->params['routes']) && $type->params['routes']) {
                    $aliases[] = $type->alias;
                }
            }
            $aliasRegex = implode('|', $aliases);
            static::contentType($aliasRegex, $routeBuilder);
        } catch (MissingConnectionException $e) {
            Log::write('critical', __d('croogo', 'Unable to get routeable content types: %s', $e->getMessage()));
        }
    }

    public static function url($url = null, $full = false)
    {
        if ($url instanceof Link) {
            $url = $url->getUrl();
        }

        return parent::url($url, $full);
    }

    public static function getActionPath(ServerRequest $request, $encode = false)
    {
        $plugin = $request->getParam('plugin');
        $prefix = $request->getParam('prefix');
        $val = $plugin ? $plugin . '.' : null;
        $val .= $prefix ? Inflector::camelize($prefix) . '/' : null;
        $val .= $request->getParam('controller') . '/' . $request->getParam('action');
        if ($encode) {
            $val = base64_encode($val);
        }

        return $val;
    }

    /**
     * Setup Site.home_url
     *
     * @return void
     */
    public static function homepage()
    {
        $homeUrl = Configure::read('Site.home_url');
        if ($homeUrl && strpos($homeUrl, ':') !== false) {
            $converter = new StringConverter();
            $url = $converter->linkStringToArray($homeUrl);
            Router::connect('/', $url);
        }
    }
}
