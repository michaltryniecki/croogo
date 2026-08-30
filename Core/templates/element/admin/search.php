<?php
/**
 * Filter bar for admin index pages, rendered into the card header.
 *
 * @var \Croogo\Core\View\CroogoView $this
 */

use Cake\Utility\Hash;
use Cake\Utility\Inflector;

if (empty($modelClass)) {
    $modelClass = $this->name;
}

if (empty($searchFields)) {
    return;
}

// No layout classes of our own here: BootstrapUI's inline align already puts
// `row g-3` on the form and wraps each control in a `.col-auto`. Adding `d-flex`
// on top of `.row` is what made the buttons span the whole card header - a direct
// child of `.row` without a column class gets `width: 100%`.
echo $this->Form->create(null, [
    'align' => 'inline',
    'novalidate' => true,
    // `gy-0` kills the row's vertical gutter: with a single line of controls
    // it is 16px of dead height inside the card header, nothing more.
    'class' => 'align-items-center gy-0',
    'url' => [
        'plugin' => $this->getRequest()->getParam('plugin'),
        'controller' => $this->getRequest()->getParam('controller'),
        'action' => $this->getRequest()->getParam('action'),
    ],
]);

$this->Form->setTemplates([
    'submitContainer' => '{{content}}',
]);

if ($this->getRequest()->getQuery('chooser')) :
    echo $this->Form->input('chooser', [
        'type' => 'hidden',
        'value' => $this->getRequest()->getQuery('chooser'),
    ]);
endif;

foreach ($searchFields as $field => $fieldOptions) {
    if (is_numeric($field) && is_string($fieldOptions)) {
        $field = $fieldOptions;
        $fieldOptions = [];
    }

    $label = $field;
    if (substr($label, -3) === '_id') {
        $label = substr($label, 0, -3);
    }
    $label = __(Inflector::humanize(Inflector::underscore($label)));

    // The filter bar carries no labels, so the field name has to live somewhere
    // the user can see it. For a text input that is the placeholder; for a
    // select it is the text of the blank option, because `placeholder` means
    // nothing to a <select> - which is why the role filter used to render as an
    // unexplained empty dropdown.
    $options = [
        'empty' => __d('croogo', 'Any %s', mb_strtolower($label)),
        'required' => false,
        'label' => false,
        'placeholder' => __d('croogo', $label),
    ];
    if (!empty($fieldOptions)) {
        $options = Hash::merge($options, $fieldOptions);
    }

    $options['default'] = $this->getRequest()->getQuery($field);

    $this->Form->unlockField($field);
    echo $this->Form->input($field, $options);
}

// Both buttons in one column, so the row treats them as a single item and
// they keep their natural width.
$buttons = $this->Form->button(
    $this->Html->icon('search', ['class' => 'me-1']) . __d('croogo', 'Filter'),
    ['type' => 'submit', 'class' => 'btn btn-primary', 'escapeTitle' => false]
);

$buttons .= $this->Html->link(
    $this->Html->icon('remove', ['class' => 'me-1']) . __d('croogo', 'Reset'),
    ['action' => 'index'],
    ['class' => 'btn btn-outline-secondary', 'escapeTitle' => false]
);

echo $this->Html->div('col-auto', $this->Html->div('btn-list', $buttons));

echo $this->Form->end();
