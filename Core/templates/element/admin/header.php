<?php
/**
 * Admin top bar - Tabler's horizontal navbar.
 *
 * The sidebar carries the site title and the section navigation, so this row is
 * the light/dark switch plus the two Nav slots plugins hang their own entries off:
 * `top-left` for site-wide links and `top-right` for the account menu.
 *
 * @var \Croogo\Core\View\CroogoView $this
 */

use Croogo\Core\Nav;

$isLoggedIn = (bool)$this->getRequest()->getSession()->read('Auth.User.id');

$leftMenu = $this->Croogo->adminMenus(Nav::items('top-left'), [
    'type' => 'dropdown',
    'htmlAttributes' => [
        'id' => 'top-left-menu',
        'class' => 'navbar-nav',
    ],
]);

$rightMenu = $isLoggedIn ? $this->Croogo->adminMenus(Nav::items('top-right'), [
    'type' => 'dropdown',
    'htmlAttributes' => [
        'id' => 'top-right-menu',
        'class' => 'navbar-nav flex-row',
    ],
]) : '';

// The `?theme=` hrefs are the no-JavaScript path: `tabler/tabler-theme.min.js`
// reads that parameter on load, stores the choice and applies it. Admin.themeToggle()
// intercepts the click so the usual case is a switch in place rather than a page
// load; the current query string is carried over so the fallback does not drop
// the page you were on out of a paginated or filtered list.
$themeUrl = function (string $theme) {
    return '?' . http_build_query(['theme' => $theme] + $this->getRequest()->getQueryParams());
};
?>
<header class="navbar navbar-expand-md d-print-none">
    <div class="container-fluid">
        <?= $leftMenu ?>
        <?php
        // `ms-auto` sits on the switch rather than on the account menu because the
        // switch is the first thing in the right-hand group, and it is there for
        // logged-out pages (the error layouts) where the account menu is not.
        ?>
        <div class="navbar-nav flex-row ms-auto">
            <div class="nav-item">
                <a href="<?= h($themeUrl('dark')) ?>" class="nav-link px-0 hide-theme-dark"
                   data-theme-toggle="dark" title="<?= __d('croogo', 'Enable dark mode') ?>"
                   aria-label="<?= __d('croogo', 'Enable dark mode') ?>">
                    <?= $this->Html->icon('moon') ?>
                </a>
                <a href="<?= h($themeUrl('light')) ?>" class="nav-link px-0 hide-theme-light"
                   data-theme-toggle="light" title="<?= __d('croogo', 'Enable light mode') ?>"
                   aria-label="<?= __d('croogo', 'Enable light mode') ?>">
                    <?= $this->Html->icon('sun') ?>
                </a>
            </div>
        </div>
        <?= $rightMenu ?>
    </div>
</header>
