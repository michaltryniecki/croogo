<?php
$chooserType = isset($this->getRequest()->query['chooser_type']) ? $this->getRequest()->query['chooser_type'] : 'attachment';
?>
<?php
    echo $this->Form->create(
        null,
        [
            'align' => 'inline',
            // Same as the shared admin/search element: BootstrapUI's inline
            // align supplies the row, we only align it vertically.
            'class' => 'align-items-center',
        ]
    );
    $this->Form->templates(
        [
            'label' => false,
            'submitContainer' => '{{content}}',
        ]
    );
    echo $this->Form->input(
        'chooser_type',
        [
            'type' => 'hidden',
            'value' => $chooserType,
        ]
    );

    echo $this->Form->input(
        'chooser',
        [
            'type' => 'hidden',
            'value' => isset($this->getRequest()->query['chooser']),
        ]
    );

    echo $this->Form->input(
        'filter',
        [
            'label' => false,
            'title' => __d('croogo', 'Search'),
            'placeholder' => __d('croogo', 'Search...'),
            'tooltip' => false,
        ]
    );

    // Form->input() with a label as the field name wraps the submit in the
    // full form-group machinery, which is what made this button span the card.
    echo $this->Html->div('col-auto', $this->Form->button(
        $this->Html->icon('search', ['class' => 'me-1']) . __d('croogo', 'Filter'),
        ['type' => 'submit', 'class' => 'btn btn-primary', 'escapeTitle' => false]
    ));
    echo $this->Form->end();
    ?>
