<?php

namespace Croogo\Extensions;

use Cake\Cache\Cache;
use Cake\Core\App;
use Cake\Core\Exception\MissingPluginException;
use Cake\Core\Plugin;
use Croogo\Core\Utility\FsUtils;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Utility\Hash;
use Croogo\Core\PluginManager;
use Croogo\Extensions\Exception\MissingThemeException;

use UnexpectedValueException;

/**
 * CroogoTheme class
 *
 * @category Component
 * @package  Croogo.Extensions.Lib
 * @version  1.4
 * @since    1.4
 * @author   Fahad Ibnay Heylaal <contact@fahad19.com>
 * @license  http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link     http://www.croogo.org
 */
class CroogoTheme
{

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Get theme aliases (folder names)
     *
     * @return array
     */
    public function getThemes()
    {
        return PluginManager::instance()
            ->getPlugins('theme');
    }

    /**
     * Get the content of theme.json or composer.json file from a theme
     *
     * @param string $theme theme plugin name
     * @return array
     */
    public function getData($theme = null, $path = null)
    {
        $themeData = [
            'name' => $theme,
            'isFrontendTheme' => true,
            'isBackendTheme' => false,
            'regions' => [],
            'screenshot' => null,
            'settings' => [
                'templates' => [
                    'input' => '<input type="{{type}}" name="{{name}}"{{attrs}}/>',
                    'select' => '<select name="{{name}}"{{attrs}}>{{content}}</select>',
                    'selectMultiple' => '<select name="{{name}}[]" multiple="multiple"{{attrs}}>{{content}}</select>',
                    'radio' => '<input type="radio" name="{{name}}" value="{{value}}"{{attrs}}>',
                    'textarea' => '<textarea name="{{name}}"{{attrs}}>{{value}}</textarea>',
                ],
                'css' => [
                    'columnFull' => 'col-12',
                    'columnLeft' => 'col-lg-8',
                    'columnRight' => 'col-lg-4',
                    'container' => 'container',
                    'containerFluid' => 'container-fluid',
                    'dashboardFull' => 'col-12',
                    'dashboardLeft' => 'col-sm-6',
                    'dashboardRight' => 'col-sm-6',
                    'dashboardClass' => 'sortable-column',
                    'formInput' => 'input-block-level',
                    'imageClass' => 'img-fluid',
                    'row' => 'row',
                    // Tabler styles the header of a `.card-table` itself, so the
                    // Bootstrap 4 `thead-light` this used to carry is both a no-op
                    // under Bootstrap 5 and heavier than the intended look.
                    'tableHeaderClass' => '',
                    'tableClass' => 'table table-vcenter table-hover card-table',
                    'tableContainerClass' => 'table-responsive',
                    'thumbnailClass' => 'img-thumbnail',
                    'tabContentClass' => 'tab-content',
                    'boxContainerClass' => 'card',
                    'boxHeaderClass' => 'card-header',
                    'boxBodyClass' => 'card-body',
                ],
                // Tabler Icons ship a single `ti` namespace with `ti-<name>`
                // classes and no size modifiers, so `size` stays empty - a `ti-sm`
                // would just be a class nothing defines.
                'iconDefaults' => [
                    'iconSet' => 'ti',
                    'prefix' => 'ti',
                    'size' => '',
                ],
                // Maps the icon names used across Croogo onto Tabler Icons.
                //
                // Two kinds of key live here on purpose: semantic ones the CMS
                // owns (`create`, `delete`, `power-off`) and the plain Font
                // Awesome names that call sites still pass literally (`cog`,
                // `magic`, `suitcase`). ThemeHelper::getIcon() returns unknown
                // keys VERBATIM, so anything missing here silently renders as
                // `ti-<fontawesome-name>` - an empty box. Keep both kinds.
                'icons' => [
                    'arrows-alt' => 'arrows-maximize',
                    'asterisk' => 'asterisk',
                    'attach' => 'paperclip',
                    'bars' => 'menu-2',
                    'bolt' => 'bolt',
                    'briefcase' => 'briefcase',
                    'calendar' => 'calendar',
                    'check' => 'check',
                    'check-mark' => 'check',
                    'chevron-down' => 'chevron-down',
                    'chevron-left' => 'chevron-left',
                    'chevron-right' => 'chevron-right',
                    'chevron-up' => 'chevron-up',
                    'clock' => 'clock',
                    'cog' => 'settings',
                    'comment' => 'message',
                    'comment-alt' => 'message',
                    'copy' => 'copy',
                    'create' => 'plus',
                    'delete' => 'trash',
                    'edit' => 'pencil',
                    'envelope' => 'mail',
                    'error-sign' => 'alert-circle',
                    'exclamation-sign' => 'alert-triangle',
                    'eye' => 'eye',
                    'file' => 'file',
                    'flag' => 'flag',
                    'folder' => 'folder',
                    'folder-open' => 'folder-open',
                    'home' => 'home',
                    'image' => 'photo',
                    'info-circle' => 'info-circle',
                    'info-sign' => 'info-circle',
                    'inspect' => 'search',
                    'key' => 'key',
                    'link' => 'link',
                    'list' => 'list',
                    'lock' => 'lock',
                    'login' => 'login',
                    'magic' => 'wand',
                    'minus' => 'minus',
                    'moon' => 'moon',
                    'move-down' => 'chevron-down',
                    'move-up' => 'chevron-up',
                    'ok' => 'check',
                    'ok-sign' => 'circle-check',
                    'paperclip' => 'paperclip',
                    'pencil' => 'pencil',
                    'photo' => 'photo',
                    'plus' => 'plus',
                    'power-off' => 'power',
                    'power-on' => 'bolt',
                    'question-sign' => 'help-circle',
                    'read' => 'eye',
                    'refresh' => 'refresh',
                    'remove' => 'x',
                    'resize' => 'arrows-maximize',
                    'save' => 'device-floppy',
                    'screenshot' => 'crosshair',
                    'search' => 'search',
                    'settings' => 'settings',
                    'sign-out' => 'logout',
                    'spinner' => 'loader-2',
                    'star' => 'star',
                    'success-sign' => 'circle-check',
                    'suitcase' => 'briefcase',
                    'sun' => 'sun',
                    'sync' => 'refresh',
                    'times' => 'x',
                    'translate' => 'language',
                    'trash' => 'trash',
                    'unlock' => 'lock-open',
                    'update' => 'pencil',
                    'upload' => 'upload',
                    'user' => 'user',
                    'view' => 'eye',
                    'warning-sign' => 'alert-triangle',
                    // Croogo's fallback for a menu entry that declares no icon
                    // (CroogoHelper::adminMenus()). A bullet reads as "child of
                    // the row above" in a submenu; the old `white` rendered as
                    // nothing at all.
                    'white' => 'point',
                    'x-mark' => 'x',
                ],
                'prefixes' => [
                    '' => [
                        'helpers' => [
                            'Html' => [
                                'className' => 'Croogo/Core.Html',
                            ],
                            'Form' => [
                                'className' => 'Croogo/Core.Form',
                            ],
                            'Paginator' => [
                                'className' => 'Croogo/Core.Paginator',
                            ],
                        ],
                    ],
                    'admin' => [
                        'helpers' => [
                            'Html' => [
                                'className' => 'Croogo/Core.Html',
                            ],
                            'Form' => [
                                'className' => 'Croogo/Core.Form',
                            ],
                            'Paginator' => [
                                'className' => 'Croogo/Core.Paginator',
                            ],
                            'Breadcrumbs'
                        ],
                    ],
                ],
            ],
        ];

        $themeConfigs = [
            DS . 'config' . DS . 'theme.json',
            DS . 'webroot' . DS . 'theme.json',
        ];

        if ($theme) {
            if (empty($path)) {
                try {
                    $path = Plugin::path($theme);
                } catch (MissingPluginException $exception) {
                    throw new MissingThemeException([$theme], $exception->getCode(), $exception);
                }
            }
        } else {
            $path = ROOT . DS;
        }

        foreach ($themeConfigs as $themeManifestFile) {
            if (!file_exists($path . $themeManifestFile)) {
                continue;
            }

            $manifestFile = $path . $themeManifestFile;
        }

        if (file_exists($path . 'composer.json')) {
            $composerJson = $path . 'composer.json';
        }

        if (!isset($manifestFile) && !isset($composerJson)) {
            return [];
        }

        if (isset($manifestFile)) {
            $json = json_decode(file_get_contents($manifestFile), true);
            if ($json) {
                $themeData = Hash::merge($themeData, $json);
            }
        }

        if (isset($composerJson)) {
            $json = json_decode(file_get_contents($composerJson), true);
            if ($json) {
                $json['vendor'] = $json['name'];
                unset($json['name']);
                if (isset($manifestFile) && isset($themeData['description'])) {
                    unset($json['description']);
                }
                $themeData = Hash::merge($themeData, $json);
            }
        }

        return $themeData;
    }

    /**
     * Activate theme $alias
     *
     * @param $theme theme alias
     * @return mixed On success Setting::$data or true, false on failure
     */
    public function activate($theme, $type = 'theme')
    {
        if (!in_array($type, ['theme', 'admin_theme'])) {
            throw new BadRequestException('Invalid theme type');
        }
        $themes = $this->getThemes();
        if (!$this->getData($theme, isset($themes[$theme]) ? $themes[$theme] : null)) {
            return false;
        }

        Cache::clearAll();
        (new PluginManager())->activate($theme);
        $settings = \Cake\ORM\TableRegistry::getTableLocator()->get('Croogo/Settings.Settings');

        return $settings->write('Site.' . $type, $theme);
    }

    /**
     * Delete theme
     *
     * @param string $alias Theme alias
     * @return bool true when successful, false or array or error messages when failed
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     */
    public function delete($alias)
    {
        if (empty($alias)) {
            throw new InvalidArgumentException(__d('croogo', 'Invalid theme'));
        }
        $paths = App::path('Plugin', $alias);
        $paths = array_map(function ($path) use ($alias) {
            return $path . $alias;
        }, $paths);

        foreach ($paths as $path) {
            if (!file_exists($path)) {
                continue;
            }

            $themeManifest = $path . '/config/theme.json';
            if (!file_exists($themeManifest)) {
                continue;
            }

            if (is_link($path)) {
                return unlink($path);
            } elseif (is_dir($path)) {
                if (FsUtils::deleteTree($path)) {
                    return true;
                } else {
                    return [__d('croogo', 'Could not delete %s', $path)];
                }
            }
        }
        throw new UnexpectedValueException(__d('croogo', 'Theme %s not found', $alias));
    }

    /**
     * Helper method to retrieve given $theme settings
     *
     * @param string $theme Theme name
     * @return array Theme configuration data
     */
    public static function config($theme = null)
    {
        // PHP 8.5: null jako offset tablicy ($themeData[$theme]) jest deprecated.
        // Prefix pusty na froncie/instalatorze wołał config(null) — normalizujemy na wejściu.
        $theme = $theme ?? '';
        static $croogoTheme = null;
        static $themeData = [];
        if ($croogoTheme === null) {
            $croogoTheme = new CroogoTheme();
        }

        if (empty($themeData[$theme])) {
            $data = $croogoTheme->getData($theme);
            $request = Router::getRequest();
            if ($request) {
                // PHP 8.5: null jako offset tablicy deprecated (prefix pusty na froncie/instalatorze)
                $prefix = $request->getParam('prefix') ?? '';
                if (isset($data['settings']['prefixes'][$prefix]['css'])) {
                    $data['settings']['css'] = Hash::merge(
                        $data['settings']['prefixes'][$prefix]['css'],
                        $data['settings']['css']
                    );
                }
            }
            $themeData[$theme] = $data;
        }

        return $themeData[$theme];
    }
}
