/**
 * Image lightbox for the admin panel.
 *
 * Replaces ekko-lightbox, which drives the modal through Bootstrap 4's jQuery
 * plugin API (`$el.modal('show')`) - removed in Bootstrap 5, so it fails on the
 * first click under Tabler.
 *
 * The trigger attribute stays `data-toggle="lightbox"` rather than becoming
 * `data-bs-toggle`: this is Croogo's own hook, not a Bootstrap component, and
 * `data-bs-toggle` would advertise it to Bootstrap's own delegated handlers.
 *
 * Usage, unchanged from before:
 *
 *   <a href="/large.jpg" data-toggle="lightbox" data-title="Caption">
 *     <img src="/thumb.jpg">
 *   </a>
 */
var Admin = typeof Admin == 'undefined' ? {} : Admin;

Admin.lightbox = function () {
  var MODAL_ID = 'croogo-lightbox';

  var getModal = function () {
    var el = document.getElementById(MODAL_ID);
    if (el) {
      return el;
    }

    el = document.createElement('div');
    el.id = MODAL_ID;
    el.className = 'modal modal-blur fade';
    el.tabIndex = -1;
    el.setAttribute('aria-hidden', 'true');
    el.innerHTML =
      '<div class="modal-dialog modal-lg modal-dialog-centered" role="document">' +
      '  <div class="modal-content">' +
      '    <div class="modal-header">' +
      '      <h5 class="modal-title"></h5>' +
      '      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
      '    </div>' +
      '    <div class="modal-body text-center">' +
      '      <img src="" alt="" class="img-fluid rounded">' +
      '    </div>' +
      '  </div>' +
      '</div>';
    document.body.appendChild(el);

    return el;
  };

  $(document).on('click', '[data-toggle="lightbox"]', function (event) {
    event.preventDefault();

    var $trigger = $(this);
    var href = $trigger.attr('href');
    if (!href) {
      return;
    }

    // The caption falls back to the trigger's own title/alt so existing markup
    // that never set data-title still gets a labelled dialog.
    var title = $trigger.data('title') ||
      $trigger.attr('title') ||
      $trigger.find('img').attr('alt') ||
      '';

    var modal = getModal();
    modal.querySelector('.modal-title').textContent = title;

    var img = modal.querySelector('.modal-body img');
    img.setAttribute('src', href);
    img.setAttribute('alt', title);

    bootstrap.Modal.getOrCreateInstance(modal).show();
  });
};
