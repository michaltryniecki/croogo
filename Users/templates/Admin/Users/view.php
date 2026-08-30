<?php
/**
 * @var \Croogo\Core\View\CroogoView $this
 * @var \Croogo\Users\Model\Entity\User $user
 */

$this->extend('Croogo/Core./Common/admin_view');

$displayName = $user->name ?: $user->username;

$this->assign('title', $displayName);

$this->Breadcrumbs
    ->add(__d('croogo', 'Users'), ['action' => 'index'])
    ->add($displayName, $this->getRequest()->getRequestTarget());

$this->append('action-buttons');
echo $this->Croogo->adminAction(
    __d('croogo', 'Reset password'),
    ['action' => 'reset_password', $user->id]
);
echo $this->Croogo->adminAction(
    __d('croogo', 'Edit User'),
    ['action' => 'edit', $user->id],
    ['button' => 'primary', 'icon' => 'update']
);
$this->end();

// Gravatar with `d=blank`, so a user without one falls back to the initial
// underneath rather than to a random identicon.
$email = trim((string)$user->email);
$avatarStyle = $email === '' ? '' : sprintf(
    ' style="background-image: url(%s)"',
    h(sprintf('https://www.gravatar.com/avatar/%s?s=160&d=blank', md5(strtolower($email))))
);

$this->append('main');
?>
<div class="row row-cards">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <span class="avatar avatar-xl mb-3 rounded"<?= $avatarStyle ?>>
                    <?= h(mb_strtoupper(mb_substr($displayName, 0, 1))) ?>
                </span>
                <h3 class="mb-0"><?= h($displayName) ?></h3>
                <div class="text-secondary">@<?= h($user->username) ?></div>
                <div class="badges-list justify-content-center mt-3">
                    <?php if ($user->has('role')) : ?>
                        <?= $this->Html->link(
                            h($user->role->title),
                            ['controller' => 'Roles', 'action' => 'edit', $user->role->id],
                            ['class' => 'badge text-bg-primary', 'escape' => false]
                        ) ?>
                    <?php endif ?>
                    <span class="badge <?= $user->status ? 'text-bg-success' : 'text-bg-secondary' ?>">
                        <?= $user->status ? __d('croogo', 'Active') : __d('croogo', 'Inactive') ?>
                    </span>
                </div>
            </div>
            <div class="list-group list-group-flush">
                <div class="list-group-item d-flex align-items-center">
                    <?= $this->Html->icon('envelope', ['class' => 'me-2 text-secondary']) ?>
                    <?php if ($email !== '') : ?>
                        <?= $this->Html->link(h($email), 'mailto:' . $email, ['escape' => false]) ?>
                    <?php else : ?>
                        <span class="text-secondary"><?= __d('croogo', 'No email address') ?></span>
                    <?php endif ?>
                </div>
                <div class="list-group-item d-flex align-items-center">
                    <?= $this->Html->icon('link', ['class' => 'me-2 text-secondary']) ?>
                    <?php if (!empty($user->website)) : ?>
                        <?= $this->Html->link(
                            h($user->website),
                            $user->website,
                            ['escape' => false, 'target' => '_blank', 'rel' => 'noopener']
                        ) ?>
                    <?php else : ?>
                        <span class="text-secondary"><?= __d('croogo', 'No website') ?></span>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?= __d('croogo', 'Account') ?></h3>
            </div>
            <div class="card-body">
                <div class="datagrid">
                    <div class="datagrid-item">
                        <div class="datagrid-title"><?= __d('croogo', 'Username') ?></div>
                        <div class="datagrid-content"><?= h($user->username) ?></div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title"><?= __d('croogo', 'Role') ?></div>
                        <div class="datagrid-content">
                            <?= $user->has('role') ? h($user->role->title) : '—' ?>
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title"><?= __d('croogo', 'Timezone') ?></div>
                        <div class="datagrid-content"><?= h($user->timezone) ?: '—' ?></div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title"><?= __d('croogo', 'Created') ?></div>
                        <div class="datagrid-content"><?= $this->Time->i18nFormat($user->created) ?></div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title"><?= __d('croogo', 'Modified') ?></div>
                        <div class="datagrid-content"><?= $this->Time->i18nFormat($user->modified) ?></div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title"><?= __d('croogo', 'Status') ?></div>
                        <div class="datagrid-content">
                            <?= $this->Layout->status((int)$user->status) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty(trim((string)$user->bio))) : ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title"><?= __d('croogo', 'Bio') ?></h3>
                </div>
                <div class="card-body">
                    <?= $this->Text->autoParagraph(h($user->bio)) ?>
                </div>
            </div>
        <?php endif ?>
    </div>
</div>
<?php
$this->end();
