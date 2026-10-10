var Admin = typeof Admin == 'undefined' ? {} : Admin;

/**
 * Modals whose body is loaded over XHR.
 *
 * A trigger with `data-remote="<url>"` loads that URL into the body of the modal
 * it opens; the chooser (core/choose.js) loads its list the same way. Links and
 * forms inside such a body are then kept in the modal: a link reloads the body,
 * a form is sent over XHR and its response replaces the body.
 *
 * Only a modal marked with `data-remote-loaded` gets that treatment. The handlers
 * used to match every `.modal-dialog form` and `.modal-dialog a`, so an ordinary
 * Bootstrap modal with a form in an application never submitted it (the button
 * got the Admin.formFeedback spinner, no request went out) and its links went
 * nowhere. The mark is set by whoever loads the body and dropped when the modal
 * closes, so the same modal element can be reused for static content.
 */
Admin.modal = function() {
  var remoteBody = '.modal[' + Admin.modal.remoteAttr + '] .modal-body';

  $(document).on('show.bs.modal', function (e) {
    var $button = $(e.relatedTarget);
    var $modal = $(e.target);

    if (!$button.data('remote')) {
      return;
    }

    var remote = $button.data('remote');
    Admin.modal.markRemote($modal);
    $modal.find('.modal-body')
      .load(remote, function () {
        // Bootstrap 5 keeps component instances in its own registry instead of
        // jQuery data, so the old `$modal.data('bs.modal')` reads undefined here
        // and reflowing the dialog after the XHR would throw.
        var instance = bootstrap.Modal.getInstance($modal.get(0));
        if (instance) {
          instance.handleUpdate();
        }
      });
  });

  $(document).on('hidden.bs.modal', function (e) {
    $(e.target).removeAttr(Admin.modal.remoteAttr);
  });

  $('body').on('click', remoteBody + ' a:not(.item-choose,.popovers)', function(event) {
    var $el = $(event.currentTarget);
    var href = $el.attr('href');
    if (href) {
      $el.closest('.modal-body').load(href);
    }
    event.preventDefault();
    return false;
  });

  // One delegated handler sends the form right away. It used to bind another
  // `$form.submit()` on every submit, so the first submit only attached a handler
  // (nothing was sent) and each later one sent one request more than before.
  $('body').on('submit', remoteBody + ' form', function(event) {
    event.preventDefault();
    var $form = $(event.currentTarget);
    var $body = $form.closest('.modal-body');
    $.ajax({
      type: ($form.attr('method') || 'POST').toUpperCase(),
      url: $form.attr('action'),
      data: $form.serialize()
    })
      .done(function(data) {
        $body.html(data);
      })
      .fail(function() {
        // The body stays as it was: take the submit buttons back from the spinner
        // Admin.formFeedback gave them, so the user can try again.
        if (typeof Admin.resetFormFeedback == 'function') {
          Admin.resetFormFeedback();
        }
      });
    return false;
  });
};

Admin.modal.remoteAttr = 'data-remote-loaded';

/**
 * Mark a modal as one whose body is loaded over XHR (see Admin.modal). Call it
 * before loading content into a modal by hand; the mark is dropped on close.
 */
Admin.modal.markRemote = function($modal) {
  $($modal).attr(Admin.modal.remoteAttr, '');
};
