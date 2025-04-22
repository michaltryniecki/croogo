<?php

namespace Croogo\Core\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Event\Event;
use Cake\Network\Exception\MethodNotAllowedException;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Croogo\Core\Exception\Exception;
use Croogo\Core\Nav;

/**
 * Croogo Component
 *
 * @category Component
 * @package  Croogo.Croogo.Controller.Component
 * @version  1.0
 * @author   Fahad Ibnay Heylaal <contact@fahad19.com>
 * @license  http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link     http://www.croogo.org
 */
class CroogoComponent extends Component
{

    /**
     * Blocks data: contains parsed value of bb-code like strings
     *
     * @var array
     * @access public
     */
    public $blocksData = [
        'menus' => [],
        'vocabularies' => [],
        'nodes' => [],
    ];

    /**
     * controller
     *
     * @var Controller
     */
    protected $_controller = null;

    /**
     * Method to lazy load classes
     *
     * @return mixed
     */
    public function __get($name)
    {
        switch ($name) {
            case '_CroogoPlugin':
            case '_CroogoTheme':
                if (!isset($this->{$name})) {
                    $class = 'Croogo\\Extensions\\' . substr($name, 1);
                    $this->{$name} = new $class();
                    if (method_exists($this->{$name}, 'setController')) {
                        $this->{$name}->setController($this->_controller);
                    }
                }

                return $this->{$name};
            case 'roleId':
                return $this->roleId();
            default:
                return parent::__get($name);
        }
    }

    /**
     * Startup
     *
     * @param object $event instance of controller
     * @return void
     */
    public function startup(Event $event)
    {
        $this->_controller = $event->getSubject();

        if ($this->_controller->request->getParam('prefix') == 'admin') {
            if (!$this->_controller->request->getParam('requested')) {
                $this->_adminData();
            }
        }
    }

    /**
     * Set variables for admin layout
     *
     * @return void
     */
    protected function _adminData()
    {
        $_siteTitle = Configure::read('Site.title');
        $this->_controller->set(compact('_siteTitle'));
        $this->_adminMenus();
    }

    /**
     * Setup admin menu
     */
    protected function _adminMenus()
    {
        $user = $this->getController()->request->getSession()->read('Auth.User');
        if (empty($user)) {
            return;
        }
//        $gravatarUrl = '<img src="//www.gravatar.com/avatar/' . md5($user['email']) . '?s=23" class="rounded mx-auto"/> ';
//        Nav::add('top-right', 'events', [
//            'icon' => 'bell',
//            'title' => '',
//            'url' => '#'
//        ]);
        Nav::add('top-right', 'user', [
            'icon' => 'user',
            'title' => '',
//            'title' => $user['username'],
            // 'before' => $gravatarUrl,
            'url' => '#',
            'children' => [
                'profile' => [
                    'title' => __d('croogo', 'Profile'),
                    'icon' => 'user',
                    'url' => [
                        'prefix' => 'admin',
                        'plugin' => 'Croogo/Users',
                        'controller' => 'Users',
                        'action' => 'view',
                        $user['id'],
                    ],
                ],
                'separator-1' => [
                    'separator' => true,
                ],
                'users' => [
                    'icon' => 'users',
                    'title' => __d('croogo', 'Users'),
                    'url' => [
                        'prefix' => 'admin',
                        'plugin' => 'Croogo/Users',
                        'controller' => 'Users',
                        'action' => 'index',
                    ]
                ],
                'roles' => [
                    'icon' => 'user-tag',
                    'title' => __d('croogo', 'Roles'),
                    'url' => [
                        'prefix' => 'admin',
                        'plugin' => 'Croogo/Users',
                        'controller' => 'Roles',
                        'action' => 'index',
                    ]
                ],
                'permissions' => [
                    'icon' => 'user-lock',
                    'title' => __d('croogo', 'Permissions'),
                    'url' => [
                        'prefix' => 'admin',
                        'plugin' => 'Croogo/Acl',
                        'controller' => 'Permissions',
                        'action' => 'index',
                    ],
                ],
                'separator-2' => [
                    'separator' => true,
                ],
                'cron' => [
                    'icon' => 'stopwatch',
                    'title' => __d('croogo', 'Jobs monitor'),
                    'url' => [
                        'prefix' => 'admin',
                        'plugin' => 'GpQueue',
                        'controller' => 'QueuedJobs',
                        'action' => 'index'
                    ],
                ],
                'relist' => [
                    'icon' => 'redo',
                    'title' => __d('croogo', 'Relist monitor'),
                    'url' => [
                        'prefix' => 'admin',
                        'plugin' => 'Products',
                        'controller' => 'relist',
                        'action' => 'index'
                    ],
                ],
                'separator-3' => [
                    'separator' => true,
                ],
                'dictionaries' => [
                    'icon' => 'book',
                    'title' => __d('products', 'Dictionaries'),
                    'url' => [
                        'prefix' => 'admin',
                        'plugin' => 'products',
                        'controller' => 'dictionaries',
                        'action' => 'index'
                    ]
                ],
                'ebay_store' => [
                    'icon' => 'store',
                    'title' => __d('products', 'eBay Store Cat.'),
                    'url' => [
                        'prefix' => 'admin',
                        'plugin' => 'products',
                        'controller' => 'ebay-store-categories',
                        'action' => 'index'
                    ]
                ],
                'templates' => [
                    'icon' => 'columns',
                    'title' => __d('products', 'Templates'),
                    'url' => [
                        'prefix' => 'admin',
                        'plugin' => 'products',
                        'controller' => 'templates',
                        'action' => 'index'
                    ]
                ],
                'orders' => [
                    'icon' => 'shopping-cart',
                    'title' => __d('products', 'Orders'),
                    'url' => [
                        'prefix' => 'admin',
                        'plugin' => 'orders',
                        'controller' => 'orders',
                        'action' => 'index'
                    ]
                ],
                'warehouses' => [
                    'icon' => 'warehouse',
                    'title' => __d('products', 'Warehouses'),
                    'url' => [
                        'prefix' => 'admin',
                        'plugin' => 'products',
                        'controller' => 'warehouses',
                        'action' => 'index'
                    ]
                ],
                'transfers' => [
                    'icon' => 'list',
                    'title' => __d('products', 'Transfers'),
                    'url' => [
                        'prefix' => 'admin',
                        'plugin' => 'products',
                        'controller' => 'warehouse-transfers',
                        'action' => 'index'
                    ]
                ],
                'vehicles' => [
                    'icon' => 'car',
                    'title' => __d('products', 'Vehicles'),
                    'url' => [
                        'prefix' => 'admin',
                        'plugin' => 'products',
                        'controller' => 'vehicles',
                        'action' => 'index'
                    ]
                ],
                'vin_vehicles' => [
                    'icon' => 'car',
                    'title' => __d('products', 'Vin database'),
                    'url' => [
                        'prefix' => 'admin',
                        'plugin' => 'products',
                        'controller' => 'vin-vehicles',
                        'action' => 'index'
                    ]
                ],
                'statistics' => [
                    'icon' => 'chart-line',
                    'title' => __d('products', 'Statistics'),
                    'url' => [
                        'prefix' => 'admin',
                        'plugin' => 'statistics',
                        'controller' => 'statistics',
                        'action' => 'index'
                    ]
                ],
                'events' => [
                    'icon' => 'history',
                    'title' => __d('products', 'Events'),
                    'url' => [
                        'prefix' => 'admin',
                        'plugin' => 'events',
                        'controller' => 'events',
                        'action' => 'index'
                    ]
                ],
                'extensions' => [
                    'icon' => 'magic',
                    'title' => __d('croogo', 'Extensions'),
                    'url' => [
                        'prefix' => 'admin',
                        'plugin' => 'Croogo/Extensions',
                        'controller' => 'Plugins',
                        'action' => 'index',
                    ]
                ],
                'settings' => [
                    'icon' => 'cog',
                    'title' => __d('croogo', 'Settings'),
                    'url' => [
                        'prefix' => 'admin',
                        'plugin' => 'Croogo/Settings',
                        'controller' => 'Settings',
                        'action' => 'prefix',
                        'Currency Exchange',
                    ]
                ],
                'separator-4' => [
                    'separator' => true,
                ],
                'logout' => [
                    'icon' => 'power-off',
                    'title' => __d('croogo', 'Logout'),
                    'url' => [
                        'prefix' => 'admin',
                        'plugin' => 'Croogo/Users',
                        'controller' => 'Users',
                        'action' => 'logout',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Gets the Role Id of the current user
     *
     * @return int Role Id
     */
    public function roleId()
    {
        $roleId = $this->_controller->request->getSession()->read('Auth.User.role_id');
        if ($roleId) {
            return $roleId;
        }

        return TableRegistry::get('Croogo/Users.Roles')->byAlias('public');
    }

    /**
     * ACL: add ACO
     *
     * Creates ACOs with permissions for roles.
     *
     * @param string $action possible values: ControllerName, ControllerName/method_name
     * @param array $allowRoles Role aliases
     * @return void
     */
    public function addAco($action, $allowRoles = [])
    {
        $this->_controller->CroogoAccess->addAco($action, $allowRoles);
    }

    /**
     * ACL: remove ACO
     *
     * Removes ACOs and their Permissions
     *
     * @param string $action possible values: ControllerName, ControllerName/method_name
     * @return void
     */
    public function removeAco($action)
    {
        $this->_controller->CroogoAccess->removeAco($action);
    }

    /**
     * Toggle field status
     *
     * @param $table Table instance
     * @param $id integer Model id
     * @param $status integer current status
     * @param $field string field name to toggle
     * @throws Exception
     */
    public function fieldToggle(Table $table, $id, $status, $field = 'status')
    {
        if (empty($id) || $status === null) {
            throw new Exception(__d('croogo', 'Invalid content'));
        }

        $status = (int)!$status;

        $entity = $table->get($id);
        $entity->{$field} = $status;
        $this->_controller->viewBuilder()->setLayout('ajax');
        if ($table->save($entity)) {
            $this->_controller->set(compact('id', 'status'));
            $this->_controller->render('Croogo/Core./Common/admin_toggle');
        } else {
            throw new Exception(__d('croogo', 'Failed toggling field %s to %s', $field, $status));
        }
    }

    /**
     * Get a list of possible view paths for current request
     *
     * The default view paths are retrieved view App::path('View').  This method
     * injects the theme path and also considers whether a plugin is used.
     *
     * The paths that will be used for fallback is typically:
     *
     *   - APP/View/<Controller>
     *   - APP/Themed/<Theme>/<Controller>
     *   - APP/Themed/<Theme>/Plugin/<Plugin>/<Controller>
     *   - APP/Plugin/<Plugin/View/<Controller>
     *   - APP/Vendor/croogo/croogo/Croogo/View
     *
     * @param Controller $controller
     * @return array A list of view paths
     */
    protected function _setupViewPaths(Controller $controller)
    {
        $defaultViewPaths = App::path('Template');
        $pos = array_search(APP . 'Template' . DS, $defaultViewPaths);
        if ($pos !== false) {
            $viewPaths = array_splice($defaultViewPaths, 0, $pos + 1);
        } else {
            $viewPaths = $defaultViewPaths;
        }
        if ($controller->viewBuilder()->getTheme()) {
            $themePaths = App::path('Template', $controller->viewBuilder()->getTheme());
            foreach ($themePaths as $themePath) {
                $viewPaths[] = $themePath;
                if ($controller->getPlugin()) {
                    $viewPaths[] = $themePath . 'Plugin' . DS . $controller->getPlugin() . DS;
                }
            }
        }
        if ($controller->getPlugin()) {
            $viewPaths = array_merge($viewPaths, App::path('Template', $controller->getPlugin()));
        }
        $viewPaths = array_merge($viewPaths, $defaultViewPaths);

        return $viewPaths;
    }

    /**
     * View Fallback
     *
     * Looks for view file through the available view paths.  If the view is found,
     * set Controller::$view variable.
     *
     * @param string|array $templates view path or array of view paths
     * @return void
     */
    public function viewFallback($templates)
    {
        $templates = (array)$templates;
        $controller = $this->_controller;
        $templatePaths = $this->_setupViewPaths($controller);
        foreach ($templates as $template) {
            foreach ($templatePaths as $templatePath) {
                $templatePath = $templatePath . $this->_viewPath() . DS . $template;
                if (file_exists($templatePath . '.ctp')) {
                    $controller->viewBuilder()->template($this->_viewPath() . DS . $template);

                    return;
                }
            }
        }
    }

    protected function _viewPath()
    {
        $viewPath = $this->_controller->getName();
        if (!empty($this->request->getParam('prefix'))) {
            $prefixes = array_map(
                'Cake\Utility\Inflector::camelize',
                explode('/', $this->_controller->request->params['prefix'])
            );
            $viewPath = implode(DS, $prefixes) . DS . $viewPath;
        }

        return $viewPath;
    }

    public function protectToggleAction()
    {
        $controller = $this->getController();
        if ($controller->request->getParam('action') !== 'toggle') {
            return;
        }
        if (!$controller->request->is('post')) {
            throw new MethodNotAllowedException();
        }
        $controller->Security->setConfig('validatePost', false);
    }
}
