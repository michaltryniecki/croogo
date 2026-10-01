<?php

namespace Croogo\Core\View\Helper;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
use Cake\View\Helper;
use Cake\View\Helper\HtmlHelper;
use Cake\View\View;
use Croogo\Core\Database\Type\ParamsType;
use Croogo\Core\PluginManager;
use Croogo\Core\Status;

/**
 * Croogo Helper
 *
 * @category Helper
 * @package  Croogo.Croogo.View.Helper
 * @version  1.0
 * @author   Fahad Ibnay Heylaal <contact@fahad19.com>
 * @license  http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link     http://www.croogo.org
 */
class CroogoHelper extends Helper
{

    /**
     * @var array
     */
    public array $helpers = [
        'Form',
        'Html' => [
            'className' => 'Croogo/Core.Html'
        ],
        'Url',
        'Croogo/Core.Layout',
        'Croogo/Core.Theme',
        'Croogo/Menus.Menus',
        'Croogo/Acl.Acl',
    ];

    /**
     * ParamsType instance
     *
     * @var Croogo\Core\Database\Type\ParamsType;
     */
    protected $_ParamsType;

    /**
     * Status instance
     *
     * @var \Croogo\Core\Status
     */
    protected $_CroogoStatus;

    /**
     * @var \Croogo\Users\Model\Table\RolesTable
     */
    protected $Role;

    /**
     * Czy wyrenderowano zakładki admina (ustawiane w adminTabs())
     *
     * @var bool|null
     */
    protected $adminTabs;

    /**
     * Boxy już wyrenderowane na stronie
     *
     * @var array|null
     */
    protected $boxAlreadyPrinted;

    /**
     * Default Constructor
     *
     * @param View $View The View this helper is being attached to.
     * @param array $settings Configuration settings for the helper.
     */
    public function __construct(View $View, $settings = [])
    {
        $this->helpers[] = Configure::read('Site.acl_plugin') . '.' . Configure::read('Site.acl_plugin');
        parent::__construct($View, $settings);
        $this->_CroogoStatus = new Status();
        $this->_ParamsType = new ParamsType;
    }

    /**
     * @return array
     */
    public function statuses()
    {
        return $this->_CroogoStatus->statuses();
    }

    /**
     * Convenience method to Html::script() for admin views
     *
     * This method does nothing if request is ajax or not in admin prefix.
     *
     * @param string|array  $url Javascript files to include
     * @param array|bool $options Options or Html attributes
     * @return string|null String of <script /> tags or null
     * @see HtmlHelper::script()
     */
    public function adminScript($url, $options = [])
    {
        $options = Hash::merge(['block' => true, 'defer' => true], $options);
        $request = $this->getView()->getRequest();
        if ($request->is('ajax') || $request->getParam('prefix') !== 'Admin') {
            return null;
        }

        return $this->Html->script($url, $options);
    }

    /**
     * Generate Admin menus added by Nav::add()
     *
     * Emits Tabler's navbar markup. Both menu types are the same Bootstrap 5
     * dropdown component, they only differ in where the submenu opens:
     *
     * - `sidebar` - the vertical navbar down the left. Items carry
     *   `.nav-link-icon` / `.nav-link-title` spans so the labels collapse away
     *   with the sidebar, and submenus open as `.dropdown-menu`.
     * - `dropdown` - the horizontal top bar. Submenus are end-aligned so they do
     *   not hang off the right edge of the window.
     *
     * Everything is plain Bootstrap 5 behaviour driven by `data-bs-toggle`; there
     * is no Croogo JavaScript behind it any more.
     *
     * The entry for the current page is marked `.active`, and so is every
     * ancestor on the way to it. In the sidebar those ancestors are also rendered
     * open (`.show`), so after a reload the submenu you are in stays expanded
     * instead of folding away - the same markup Tabler uses for its own demo.
     *
     * @param array $menus Menu items, as collected by Nav::items()
     * @param array $options Rendering options
     * @param int $depth Current nesting level, 0 for the top level
     * @return string menu html tags
     */
    public function adminMenus($menus, $options = [], $depth = 0)
    {
        $options = Hash::merge([
            'type' => 'sidebar',
            'children' => true,
            'htmlAttributes' => [
                'class' => 'navbar-nav',
            ],
            'activeTrail' => null,
        ], $options);

        $userId = $this->getView()->getRequest()->getSession()->read('Auth.User.id');
        if (empty($userId)) {
            return '';
        }

        $sidebar = $options['type'] === 'sidebar';
        $htmlAttributes = $options['htmlAttributes'];
        $isSubmenu = $depth > 0;

        $out = '';
        $sorted = Hash::sort($menus, '{s}.weight', 'ASC');
        if (empty($this->Role)) {
            $this->Role = TableRegistry::getTableLocator()->get('Croogo/Users.Roles');
            $this->Role->addBehavior('Croogo/Core.Aliasable');
        }
        $currentRole = $this->Role->getBehavior('Aliasable')->byId($this->Layout->getRoleId());

        $activeTrail = $options['activeTrail'];
        if ($activeTrail === null) {
            $activeTrail = $this->_activeMenuTrail($sorted, $userId, $currentRole)[1];
        }

        foreach ($sorted as $key => $menu) {
            if (isset($menu['separator'])) {
                // A submenu is a <div class="dropdown-menu"> of <a>s, so its
                // divider is a <hr>; the top level is a <ul>, where it has to be
                // an <li> to stay valid markup.
                $out .= $isSubmenu
                    ? '<hr class="dropdown-divider">'
                    : $this->Html->tag('li', '', ['class' => 'nav-item dropdown-divider']);
                continue;
            }
            if (!$this->_adminMenuAllowed($menu, $userId, $currentRole)) {
                continue;
            }

            $attributes = $menu['htmlAttributes'] ?? [];
            if (empty($attributes['class'])) {
                $attributes['class'] = Text::slug(strtolower('menu-' . $menu['title']), '-');
            }

            $isActive = $activeTrail && (string)$activeTrail[0] === (string)$key;
            // Only the sidebar keeps the current branch open: in the top bar an
            // open dropdown would hang over the page until clicked away.
            $isOpen = $isActive && $sidebar && count($activeTrail) > 1;

            $children = '';
            if (!empty($menu['children'])) {
                $menuClass = $sidebar ? 'dropdown-menu' : 'dropdown-menu dropdown-menu-end';
                $children = $this->adminMenus($menu['children'], [
                    'type' => $options['type'],
                    'children' => true,
                    'htmlAttributes' => [
                        'class' => $isOpen ? $menuClass . ' show' : $menuClass,
                    ],
                    'activeTrail' => $isActive ? array_slice($activeTrail, 1) : [],
                ], $depth + 1);
            }

            if ($isActive) {
                $attributes['class'] .= ' active';
            }

            $attributes['class'] .= $isSubmenu ? ' dropdown-item' : ' nav-link';
            if ($children) {
                // Bootstrap reads the open state off `.show` on the toggle and
                // the menu, so a branch rendered open still closes on first click.
                $attributes['class'] .= ' dropdown-toggle' . ($isOpen ? ' show' : '');
                $attributes['data-bs-toggle'] = 'dropdown';
                $attributes['data-bs-auto-close'] = 'outside';
                $attributes['role'] = 'button';
                $attributes['aria-expanded'] = $isOpen ? 'true' : 'false';
            }

            $out .= $this->_adminMenuItem($menu, $attributes, $children, $sidebar, $isSubmenu, $isActive);
        }

        // (string) cast, and an early return for an empty menu: HtmlHelper::tag()
        // emits ONLY the opening tag when the content is null, and an unclosed
        // <ul> swallows whatever markup follows it - which is how the always-empty
        // `top-left` menu used to eat the account dropdown next to it.
        if ($out === '') {
            return '';
        }

        return $this->Html->tag($isSubmenu ? 'div' : 'ul', $out, $htmlAttributes);
    }

    /**
     * Render one entry of an admin menu.
     *
     * Split out of adminMenus() because a top-level entry and a submenu entry are
     * different elements, not the same element with different classes: the former
     * is an <li> wrapping an <a>, the latter is a bare <a> inside the parent's
     * .dropdown-menu.
     *
     * @param array $menu The menu item
     * @param array $attributes Html attributes for the link
     * @param string $children Rendered submenu, empty when there is none
     * @param bool $sidebar Whether this belongs to the vertical navbar
     * @param bool $isSubmenu Whether this item sits inside a .dropdown-menu
     * @param bool $isActive Whether this item is the current page or leads to it
     * @return string
     */
    protected function _adminMenuItem(
        array $menu,
        array $attributes,
        $children,
        $sidebar,
        $isSubmenu,
        $isActive = false,
    ) {
        $title = h($menu['title']);
        if ($sidebar && !$isSubmenu) {
            // `.nav-link-title` is what Tabler hides when the sidebar collapses,
            // so the label has to be inside it rather than a bare text node.
            $title = '<span class="nav-link-title">' . $title . '</span>';
        }

        if (!empty($menu['icon'])) {
            // A submenu entry is a `.dropdown-item`, where the icon is just an
            // inline glyph. Only a nav link gets `.nav-link-icon`, which is the
            // hook Tabler uses to keep the icon visible while the sidebar is
            // collapsed to icons only.
            $title = $isSubmenu
                ? $this->Html->icon($menu['icon'], ['class' => 'dropdown-item-icon']) . $title
                : '<span class="nav-link-icon">' . $this->Html->icon($menu['icon']) . '</span>' . $title;
        }

        if (isset($menu['before'])) {
            $title = $menu['before'] . $title;
        }
        if (isset($menu['after'])) {
            $title = $title . $menu['after'];
        }

        $attributes['escape'] = false;
        $link = $this->Html->link($title, $menu['url'], $attributes);

        if ($isSubmenu) {
            // Nested one level deeper still: Bootstrap has no submenu component,
            // so the child menu is wrapped in a `.dropend` that opens sideways.
            return $children
                ? $this->Html->tag('div', $link . $children, ['class' => 'dropend'])
                : $link;
        }

        $liClass = 'nav-item';
        if ($isActive) {
            // Tabler draws the sidebar's "you are here" bar off `.nav-item.active`.
            $liClass .= ' active';
        }
        if ($children) {
            $liClass .= ' dropdown';
        }

        return $this->Html->tag('li', $link . $children, ['class' => $liClass]);
    }

    /**
     * Whether the current user may see a menu entry.
     *
     * @param array $menu The menu item
     * @param string|int $userId Current user id
     * @param string|false $currentRole Alias of the current user's role, false when unknown
     * @return bool
     */
    protected function _adminMenuAllowed(array $menu, int|string $userId, string|false $currentRole): bool
    {
        return $currentRole == 'superadmin' || $this->Acl->linkIsAllowedByUserId($userId, $menu['url']);
    }

    /**
     * Find the menu entry that best stands for the current page.
     *
     * An entry with children is a toggle rather than a page, so it only counts
     * when none of its children match. The best match across the whole tree
     * wins, so an edit form lights up its controller's list even though no menu
     * entry links to the form itself, while an exact link (e.g. a filtered list)
     * still beats the plain list. On a tie the earlier entry, by weight, wins.
     *
     * @param array $menus Menu items, already sorted by weight
     * @param string|int $userId Current user id
     * @param string|false $currentRole Alias of the current user's role, false when unknown
     * @return array [score, keys from the top level down to the matched entry]
     */
    protected function _activeMenuTrail(array $menus, int|string $userId, string|false $currentRole): array
    {
        $best = [0, []];
        foreach ($menus as $key => $menu) {
            if (isset($menu['separator']) || !$this->_adminMenuAllowed($menu, $userId, $currentRole)) {
                continue;
            }
            if (!empty($menu['children'])) {
                $children = Hash::sort($menu['children'], '{s}.weight', 'ASC');
                [$score, $trail] = $this->_activeMenuTrail($children, $userId, $currentRole);
                $trail = array_merge([$key], $trail);
                if (!$score) {
                    // No child stands for this page, but the toggle's own link
                    // may - then it is lit without being opened.
                    $score = $this->_menuMatchScore($menu['url']);
                    $trail = [$key];
                }
            } else {
                $score = $this->_menuMatchScore($menu['url']);
                $trail = [$key];
            }
            if ($score > $best[0]) {
                $best = [$score, $trail];
            }
        }

        return $best;
    }

    /**
     * How closely a menu link matches the current request, 0 for not at all.
     *
     * 4 - the very same URL, query string included
     * 3 - same path, and the link's query is part of the request's
     *     (a filtered list, viewed on page 2)
     * 2 - same path, the link has no query (a plain list, paginated or sorted)
     * 1 - same prefix, plugin and controller (an edit/add form of that list)
     *
     * @param array|string $url Menu link
     * @return int
     */
    protected function _menuMatchScore(array|string $url): int
    {
        $request = $this->getView()->getRequest();
        $built = $this->Url->build($url);
        $requestUri = (string)env('REQUEST_URI');
        if ($built === $requestUri) {
            return 4;
        }

        $builtPath = (string)parse_url($built, PHP_URL_PATH);
        if ($builtPath !== '' && $builtPath === (string)parse_url($requestUri, PHP_URL_PATH)) {
            parse_str((string)parse_url($built, PHP_URL_QUERY), $linkQuery);
            if (!$linkQuery) {
                return 2;
            }
            $requestQuery = $request->getQueryParams();
            $contained = true;
            foreach ($linkQuery as $name => $value) {
                $contained = $contained && ($requestQuery[$name] ?? null) == $value;
            }
            if ($contained) {
                return 3;
            }
        }

        if (!is_array($url) || empty($url['controller'])) {
            return 0;
        }
        // A key left out of a URL array is inherited from the current request
        // when the link is built, so only the keys that are there can differ.
        foreach (['prefix', 'plugin', 'controller'] as $part) {
            if (!array_key_exists($part, $url)) {
                continue;
            }
            if (Inflector::camelize((string)$url[$part]) !== Inflector::camelize((string)$request->getParam($part))) {
                return 0;
            }
        }

        return 1;
    }

    /**
     * Show links under Actions column
     *
     * @param int $id
     * @param array $options
     * @return string
     */
    public function adminRowActions($id, $options = [])
    {
        $request = $this->getView()->getRequest();
        $key = $output = '';
        $plugin = $request->getParam('plugin');
        if ($plugin) {
            $key .= $plugin . '.';
        }
        $prefix = $request->getParam('prefix');
        if ($prefix) {
            $key .= Inflector::camelize($prefix) . '/';
        }
        $key .= Inflector::camelize($this->getView()->getRequest()->getParam('controller')) . '/';
        $key .= $request->getParam('action');
        $encodedKey = base64_encode($key);
        $rowActions = Configure::read('Admin.rowActions.' . $encodedKey);
        if (is_array($rowActions)) {
            foreach ($rowActions as $title => $link) {
                $linkOptions = $options;
                if (is_array($link)) {
                    $config = $link[key($link)];
                    if (isset($config['options'])) {
                        $linkOptions = Hash::merge($options, $config['options']);
                    }
                    if (isset($config['confirm'])) {
                        $linkOptions['confirm'] = $config['confirm'];
                        unset($config['confirm']);
                    }
                    if (isset($config['title'])) {
                        $title = $config['title'];
                    }
                    $link = key($link);
                }
                $link = $this->Menus->linkStringToArray(str_replace(':id', $id, $link));
                if (isset($linkOptions['icon'])) {
                    $linkOptions['escapeTitle'] = false;
                }
                $output .= $this->adminRowAction($title, $link, $linkOptions);
            }
        }

        return $output;
    }

    /**
     * Show link under Actions column
     *
     * ### Options:
     *
     * - `method` - when 'POST' is specified, the FormHelper::postLink() will be
     *              used instead of HtmlHelper::link()
     * - `rowAction` when bulk submissions is used, defines which action to use.
     *
     * @param string $title The content to be wrapped by <a> tags.
     * @param string|array $url Cake-relative URL or array of URL parameters, or external URL (starts with http://)
     * @param array $options Array of HTML attributes.
     * @param string $confirmMessage JavaScript confirmation message.
     * @return string An `<a />` element
     */
    public function adminRowAction($title, $url = null, $options = [], $confirmMessage = false)
    {
        $action = false;
        $options = Hash::merge([
            'escapeTitle' => true,
            'escape' => true,
            'confirm' => $confirmMessage,
        ], $options);

        if (is_array($url)) {
            $action = $url['action'];
            if (isset($options['class'])) {
                $options['class'] .= ' ' . $url['action'];
            } else {
                $options['class'] = $url['action'];
            }
        }
        if (isset($options['icon']) && empty($title)) {
            $options['iconInline'] = false;
        }

        if (!empty($options['rowAction'])) {
            $options['data-row-action'] = $options['rowAction'];
            unset($options['rowAction']);

            return $this->_bulkRowAction($title, $url, $options);
        }

        if (!empty($options['method']) && strcasecmp($options['method'], 'post') == 0) {
            $usePost = true;
            unset($options['method']);
        }

        if ($action == 'delete' || isset($usePost)) {
            $options['block'] = true;
            $postLink = $this->Form->postLink($title, $url, $options);

            return $postLink;
        }

        return $this->Html->link($title, $url, $options);
    }

    /**
     * Creates a special type of link for use in admin area.
     *
     * Clicking the link will automatically check a corresponding checkbox
     * where element id is equal to $url parameter and immediately submit the form
     * it's on.  This works in tandem with Admin.processLink() in javascript.
     */
    protected function _bulkRowAction($title, $url = null, $options = [])
    {
        if (!empty($options['confirm'])) {
            $options['data-confirm-message'] = $options['confirm'];
            unset($options['confirm']);
        }
        if (isset($options['icon'])) {
            $options['iconInline'] = false;
        }
        $output = $this->Html->link($title, $url, $options);

        return $output;
    }

    /**
     * Create an action button
     *
     * @param string $title Button title
     * @param url|string $url URL
     * @param array $options Options array
     * @param string $confirmMessage Confirmation message
     * @return string
     */
    public function adminAction($title, $url, $options = [], $confirmMessage = false)
    {
        // `outline-primary`, not `outline-secondary`: these sit in the page
        // header against Tabler's light grey page background, where a grey
        // outline on a near-white button all but disappears. Primary-outlined
        // still reads as secondary to the filled primary button next to it.
        //
        // No `btn-sm` either: these are a page's main actions and were coming
        // out 28px tall next to the 40px controls in the card below them.
        $options = Hash::merge([
            'button' => 'outline-primary',
            'list' => false,
            'confirm' => $confirmMessage,
            'escape' => false,
        ], $options);
        if ($options['list'] === true) {
            $list = true;
            unset($options['list']);
        }
        if (isset($options['method']) && strcasecmp($options['method'], 'post') == 0) {
            $options['block'] = 'scriptBottom';
            $out = $this->Form->postLink($title, $url, $options);
        } else {
            $out = $this->Html->link($title, $url, $options);
        }
        if (isset($list)) {
            $out = $this->Html->tag('li', $out);
        } else {
            $out = $this->Html->div('btn-group', $out);
        }

        return $out;
    }

    /**
     * Create a tab title/link
     */
    public function adminTab($title, $url, $options = [])
    {
        // Real Bootstrap 5 tabs. The `scroll` class this used to carry belonged to
        // a scroll-to-section behaviour built on jQuery Waypoints, a library the
        // admin panel no longer loads - so the tabs did nothing at all.
        $options = Hash::merge([
            'data-bs-toggle' => 'tab',
            'role' => 'tab',
        ], $options);

        $options = $this->addClass($options, 'nav-link');

        return $this->Html->tag('li', $this->Html->link($title, $url, $options), [
            'class' => 'nav-item',
        ]);
    }

    /**
     * Show tabs
     *
     * @return string
     */
    public function adminTabs($show = null)
    {
        if (!isset($this->adminTabs)) {
            $this->adminTabs = false;
        }

        $output = '';
        $actions = '';
        $request = $this->getView()->getRequest();
        if ($request->getParam('prefix')) {
            $actions .= Inflector::camelize($request->getParam('prefix')) . '/';
        }
        $actions .= Inflector::camelize($request->getParam('controller')) . '/' . $request->getParam('action');
        $tabs = Configure::read('Admin.tabs.' . $actions);
        if (is_array($tabs)) {
            foreach ($tabs as $title => $tab) {
                $tab = Hash::merge([
                    'options' => [
                        'linkOptions' => [],
                        'elementData' => [],
                        'elementOptions' => [],
                    ],
                ], $tab);

                if (!isset($tab['options']['type']) ||
                    (isset($tab['options']['type']) &&
                        (in_array($this->_View->get('typeAlias'), $tab['options']['type'])))
                ) {
                    $domId = strtolower(Inflector::singularize($request->getParam('controller'))) .
                        '-' .
                        strtolower(Text::slug($title, '-'));
                    if ($this->adminTabs) {
                        if ($this->_View->get('viewVar') !== null) {
                            $entity = $this->_View->get($this->_View->get('viewVar'));
                            $tab['options']['elementData']['entity'] = $entity;
                        }
                        $output .= $this->Html->tabStart($domId, ['class' => 'wayPoint']);
                        $output .= $this->_View->element(
                            $tab['element'],
                            $tab['options']['elementData'],
                            $tab['options']['elementOptions']
                        );
                        $output .= $this->Html->tabEnd();
                    } else {
                        $output .= $this->adminTab(__d('croogo', $title), '#' . $domId, $tab['options']['linkOptions']);
                    }
                }
            }
        }

        $this->adminTabs = true;

        return $output;
    }

    /**
     * Show Boxes
     *
     * @param array $boxName
     */
    public function adminBoxes($boxName = null)
    {
        if (!isset($this->boxAlreadyPrinted)) {
            $this->boxAlreadyPrinted = [];
        }

        $output = '';
        $request = $this->getView()->getRequest();
        $box = $request->getParam('controller') . '/' . $request->getParam('action');
        if ($request->getParam('prefix')) {
            $box = Inflector::camelize($request->getParam('prefix')) . '/' . $box;
        }
        $allBoxes = Configure::read('Admin.boxes.' . $box);
        $allBoxes = empty($allBoxes) ? [] : $allBoxes;
        $boxNames = [];

        if (is_null($boxName)) {
            foreach ($allBoxes as $boxName => $value) {
                if (!in_array($boxName, $this->boxAlreadyPrinted)) {
                    $boxNames[$boxName] = $allBoxes[$boxName];
                }
            }
        } elseif (!in_array($boxName, $this->boxAlreadyPrinted)) {
            if (array_key_exists($boxName, $allBoxes)) {
                $boxNames[$boxName] = $allBoxes[$boxName];
            }
        }

        foreach ($boxNames as $title => $box) {
            $box = Hash::merge([
                'options' => [
                    'linkOptions' => [],
                    'elementData' => [],
                    'elementOptions' => [],
                ],
            ], $box);
            $issetType = isset($box['options']['type']);
            $typeInTypeAlias = $issetType && in_array($this->_View->get('typeAlias'), $box['options']['type']);
            if (!$issetType || $typeInTypeAlias) {
                if ($this->_View->get('viewVar') !== null) {
                    $entity = $this->_View->get($this->_View->get('viewVar'));
                    $box['options']['elementData']['entity'] = $entity;
                }
                $output .= $this->Html->beginBox($title);
                $output .= $this->_View->element(
                    $box['element'],
                    $box['options']['elementData'],
                    $box['options']['elementOptions']
                );
                $output .= $this->Html->endBox();
                $this->boxAlreadyPrinted[] = $title;
            }
        }

        return $output;
    }

    /**
     * @param $target
     *
     * @return \Cake\View\Cell
     */
    public function linkChooser($target)
    {
        $linkChooser = $this->_View->element('Croogo/Core.admin/modal', [
            'id' => 'link-chooser',
            'modalSize' => 'modal-lg'
        ]);
        if (!strstr($this->_View->fetch('page-footer'), $linkChooser)) {
            $this->_View->append('page-footer', $linkChooser);
        }

        return $this->_View->cell('Croogo/Core.Admin/LinkChooser', [$target]);
    }

    /**
     * @param $theme
     * @param $path
     * @param null $allowedMimeTypes
     *
     * @return string|null
     */
    public function dataUri($theme, $path, $allowedMimeTypes = null)
    {
        $allowedMimeTypes = array_filter(array_merge([
            'image/jpeg',
            'image/png',
        ], (array)$allowedMimeTypes));
        if ($theme) {
            $file = PluginManager::path($theme) . '/webroot/' . $path;
        } else {
            $file = WWW_ROOT . $path;
        }
        if (!file_exists($file)) {
            return null;
        }
        $mimeType = mime_content_type($file);
        if (!in_array($mimeType, $allowedMimeTypes)) {
            return null;
        }
        $dataUri = sprintf(
            'data:%s;base64,%s',
            $mimeType,
            base64_encode(file_get_contents($file))
        );

        return $dataUri;
    }
}
