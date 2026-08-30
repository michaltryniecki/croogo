<?php
/**
 * Installer progress, as Tabler's numbered steps.
 *
 * @var \Croogo\Core\View\CroogoView $this
 */

use Croogo\Install\Controller\InstallController;

$steps = '';

foreach (InstallController::STEPS as $key => $step) {
    // `.active` marks the step you are on; Tabler colours every step up to and
    // including it, so only the current one carries the class.
    $class = 'step-item';
    if ($onStep === $key + 1) {
        $class .= ' active';
    }

    $steps .= $this->Html->tag('div', h($step), [
        'class' => $class,
        'title' => $step,
    ]);
}

echo $this->Html->div('steps steps-counter steps-blue mb-4', $steps);
