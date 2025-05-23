<?php

if (!$this->getRequest()->is('ajax')) :
    echo $this->Layout->js();
    echo $this->Html->script([
        'Croogo/Core.jquery/jquery.min.js',
        // '/js/jquery-3.3.1.min.js',
        'Croogo/Core.core/moment-with-locales',
        'Croogo/Core.core/underscore-min',
    ]);
    echo $this->Html->script([
        'Croogo/Core.jquery/jquery-ui.min.js',
        'Croogo/Core.core/popper.min.js',
        'Croogo/Core.core/bootstrap.min.js',
        'Croogo/Core.jquery/jquery.slug',
        'Croogo/Core.jquery/jquery.hoverIntent.minified',
        'Croogo/Core.core/bootstrap3-typeahead.min',
        'Croogo/Core.core/moment-timezone-with-data',
        'Croogo/Core.core/tempusdominus-bootstrap-4.min',
        'Croogo/Core.core/typeahead_autocomplete',
//        'Croogo/Core.core/ekko-lightbox.min.js',
        'Croogo/Core.core/ekko-lightbox.js',
        'Croogo/Core.core/select2.full.min.js',
        'Croogo/Core.core/sidebar',
        'Croogo/Core.core/choose',
        'Croogo/Core.core/modal',
        '/js/jquery.scrollTo-2.1.2.js',
        '/js/jquery.waypoints.min.js',
        '/js/jquery.validate.js',
        '/js/custom.js'
    ], [
        'async' => false,
    ]);
    echo $this->Html->script([
        'Croogo/Core.core/admin',
    ], [
        'defer' => false,
    ]);
endif;
