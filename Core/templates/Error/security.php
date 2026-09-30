<?php
/**
 * Shown when FormProtection rejects a posted form.
 *
 * @var \Croogo\Core\View\CroogoView $this
 * @var string|null $message
 */

use Cake\Core\Configure;

// The prefix is `Admin` since CakePHP 4. Compared with `admin`, this always fell through
// to the public `error` layout, so an admin saw an unstyled page from another era.
$isAdmin = strtolower((string)$this->getRequest()->getParam('prefix')) === 'admin';
$this->setLayout($isAdmin ? 'admin_error' : 'error');

?>
<div class="text-center">
<h6><?= __d('cake', 'A security error has occurred') ?></h6>
<p>
    <?= __d(
        'croogo',
        'The form could not be verified and nothing was saved. Go back, reload the page and try again.',
    ) ?>
</p>
<p class="error">
    <?php if (Configure::read('debug')) : ?>
    <strong><?= __d('cake', 'Error') ?>: </strong>
        <?= h($message) ?>
    <?php endif ?>
</p>
</div>
