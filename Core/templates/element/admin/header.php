<?php
/**
 * Admin top bar - Tabler's horizontal navbar.
 *
 * The sidebar carries the site title and the section navigation, so this row is
 * only the two Nav slots plugins hang their own entries off: `top-left` for
 * site-wide links and `top-right` for the account menu.
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

// `ms-auto` on the list itself rather than on a wrapper: it is a flex item of
// the container, so an extra div would just move the problem one level out.
$rightMenu = $isLoggedIn ? $this->Croogo->adminMenus(Nav::items('top-right'), [
    'type' => 'dropdown',
    'htmlAttributes' => [
        'id' => 'top-right-menu',
        'class' => 'navbar-nav flex-row ms-auto',
    ],
]) : '';

// With both slots empty the bar would be a stripe of blank chrome above every
// page, so it is not rendered at all.
if (!$leftMenu && !$rightMenu) {
    return;
}
?>
<header class="navbar navbar-expand-md d-print-none">
    <div class="container-fluid">
        <?= $leftMenu ?>
        <?= $rightMenu ?>
    </div>
</header>
