<?php

use Cake\Core\Configure;
use Croogo\Install\InstallManager;

$this->assign('title', __d('croogo', 'Welcome'));
$check = true;

/**
 * One requirement row: a green tick or a red cross and the message.
 *
 * @param \Croogo\Core\View\CroogoView $view
 * @param bool $ok Whether the requirement is met
 * @param string $message
 * @return string
 */
$requirement = function ($view, $ok, $message) {
    $icon = $view->Html->icon($ok ? 'success-sign' : 'error-sign', [
        'class' => ($ok ? 'text-green' : 'text-red') . ' me-2',
    ]);

    return $view->Html->tag('li', $icon . h($message), [
        'class' => 'list-group-item d-flex align-items-center',
    ]);
};

$rows = '';

// tmp is writable
if (is_writable(TMP)) {
    $rows .= $requirement($this, true, __d('croogo', 'Your tmp directory is writable.'));
} else {
    $check = false;
    $rows .= $requirement($this, false, __d('croogo', 'Your tmp directory is NOT writable.'));
}

// config is writable
if (is_writable(ROOT . DS . 'config')) {
    $rows .= $requirement($this, true, __d('croogo', 'Your config directory is writable.'));
} else {
    $check = false;
    $rows .= $requirement($this, false, __d('croogo', 'Your config directory is NOT writable.'));
}

$versions = InstallManager::versionCheck();
if ($versions['php']) {
    $rows .= $requirement($this, true, sprintf(
        __d('croogo', 'PHP version %s >= %s'),
        phpversion(),
        InstallManager::PHP_VERSION
    ));
} else {
    $check = false;
    $rows .= $requirement($this, false, sprintf(
        __d('croogo', 'PHP version %s < %s'),
        phpversion(),
        InstallManager::PHP_VERSION
    ));
}

// cakephp version
if ($versions['cake']) {
    $rows .= $requirement($this, true, __d(
        'croogo',
        'CakePhp version %s >= %s',
        Configure::version(),
        InstallManager::CAKE_VERSION
    ));
} else {
    $check = false;
    $rows .= $requirement($this, false, __d(
        'croogo',
        'CakePHP version %s < %s',
        Configure::version(),
        InstallManager::CAKE_VERSION
    ));
}

echo $this->Html->tag('ul', $rows, ['class' => 'list-group list-group-flush mb-0']);

if ($check) {
    $out = $this->Html->link(__d('croogo', 'Start installation'), [
        'action' => 'database',
    ], [
        'button' => 'primary',
        'tooltip' => __d('croogo', 'Click here to begin installation'),
    ]);
} else {
    $out = '<p>' . __d('croogo', 'Installation cannot continue as minimum requirements are not met.') . '</p>';
}
$this->assign('buttons', $out);
