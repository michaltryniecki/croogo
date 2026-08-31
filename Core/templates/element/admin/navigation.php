<?php
/**
 * Admin sidebar - Tabler's vertical navbar.
 *
 * `data-bs-theme="dark"` is pinned rather than left to follow the page: the dark
 * rail against a light page is what the admin looks like, and letting it follow
 * the switch would turn the light theme into a white-on-white panel. In dark mode
 * the attribute is redundant but not a no-op - it keeps the sidebar on the navbar
 * surface, a step lighter than the page body, so the two stay distinguishable.
 *
 * Croogo/Core.core/croogo-tabler.css leans on this: the sidebar's scrollbar and
 * submenu tints are mixed out of `--tblr-navbar-color`, which is what this
 * attribute selects.
 *
 * @var \Croogo\Core\View\CroogoView $this
 */

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Croogo\Core\Nav;
use Croogo\Core\Utility\StringConverter;

$dashboardUrl = (new StringConverter())->linkStringToArray(
    Configure::read('Site.dashboard_url')
);

// Keyed by role and current URL because the menu is both ACL-filtered and
// self-highlighting - the same markup is not valid for two different pages.
$cacheKey = 'adminnav_' . $this->Layout->getRoleId() . '_' . $this->getRequest()->getPath() .
    '_' . md5(serialize($this->getRequest()->getQuery()));
$menu = Cache::remember($cacheKey, function () {
    return $this->Croogo->adminMenus(Nav::items(), [
        'htmlAttributes' => [
            'id' => 'sidebar-menu',
            'class' => 'navbar-nav pt-lg-3',
        ],
    ]);
}, 'croogo_menus');
?>
<aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#sidebar-collapse" aria-controls="sidebar-collapse"
                aria-expanded="false" aria-label="<?= __d('croogo', 'Toggle navigation') ?>">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="navbar-brand navbar-brand-autodark">
            <?= $this->Html->link(Configure::read('Site.title'), $dashboardUrl) ?>
        </div>
        <div class="collapse navbar-collapse" id="sidebar-collapse">
            <?= $menu ?>
        </div>
    </div>
</aside>
