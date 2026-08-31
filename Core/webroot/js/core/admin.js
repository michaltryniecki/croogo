/**
 * Admin
 *
 * for admin pages
 */
var Admin = typeof Admin == 'undefined' ? {} : Admin;

/**
 * Where the admin page content lives. Tabler's shell has no `#content`; the
 * equivalent wrapper is `.page-body`.
 */
Admin.TABS_SELECTOR = '.page-body .nav-tabs';

/**
 * Show a tab.
 *
 * Bootstrap 5 removed the jQuery plugin API, so `$a.tab('show')` is now a call
 * against the component class. Silently does nothing when the selector matched
 * nothing, which is the common case: most admin pages have no tabs at all.
 */
Admin.showTab = function ($link) {
  var el = $link && $link.get(0);
  if (!el) {
    return;
  }
  bootstrap.Tab.getOrCreateInstance(el).show();
};

/**
 * Gets spinner class
 */
Admin.spinnerClass = function () {
  return Admin.iconClass('spinner') + ' ' + Admin.iconClass('spin', false);
};

// https://stackoverflow.com/a/26234977
Admin.getCookie = function(cookieName) {
    if (!cookieName) { return null; }
    return decodeURIComponent(
      document.cookie.replace(
        new RegExp("(?:(?:^|.*;)\\s*" + encodeURIComponent(cookieName)
          .replace(/[\-\.\+\*]/g, "\\$&")
          + "\\s*\\=\\s*([^;]*).*$)|^.*$"), "$1"
      )
    ) || null;
}

/**
 * Forms
 *
 * @return void
 */
Admin.form = function () {
  // Tooltips activation
  $('[rel=tooltip],*[data-title]:not([data-content]),input[title],textarea[title]').tooltip();

  var ajaxToggle = function (e) {
    var $this = $(this);
    var spinnerClass = Admin.spinnerClass();
    $this.find('i').attr('class', spinnerClass);
    var url = $this.data('url');
    $.post({
      url: url,
      headers: {
        'X-CSRF-Token': Admin.getCookie('csrfToken'),
      },
      success: function (data) {
        $this.parent().html(data);
      },
    });
  };

  // Autocomplete
  if (typeof $.fn.typeahead_autocomplete === 'function') {
    $('input.typeahead-autocomplete').typeahead_autocomplete();
  }

  // Row Actions
  $('body')
    .on('click', 'a[data-row-action]', Admin.processLink)
    .on('click', 'a.ajax-toggle', ajaxToggle);
};

/**
 * Protect forms for accidental page refresh
 */
Admin.protectForms = function () {
  var forms = document.getElementsByClassName('protected-form');
  if (forms.length > 0) {
    var watchElements = ['input', 'select', 'textarea'];
    var ignored = ['button', '[type=submit]', '.cancel'];
    for (var i = 0; i < forms.length; i++) {
      var $form = $(forms[i]);
      var customIgnore = $form.data('ignore-elements');
      var whitelist = ignored.join(',');
      if (customIgnore) {
        whitelist += ',' + customIgnore;
      }
      $form
        .on('change', watchElements.join(','), function (e) {
          $form.data('dirty', true);
        })
        .on('click', whitelist, function (e) {
          $form.data('dirty', false);
          if (typeof Croogo.Wysiwyg !== 'undefined' && typeof Croogo.Wysiwyg.resetDirty == 'function') {
            Croogo.Wysiwyg.resetDirty();
          }
        });
    }

    window.onbeforeunload = function (e) {
      var dirty = false;
      for (var i = 0; i < forms.length; i++) {
        if ($(forms[i]).data('dirty') === true) {
          dirty = true;
          break;
        }
      }
      if (!dirty) {
        if (typeof Croogo.Wysiwyg !== 'undefined' && typeof Croogo.Wysiwyg.isDirty == 'function' && !Croogo.Wysiwyg.isDirty()) {
          return;
        } else {
          return;
        }
      }

      var confirmationMessage = 'Please save your changes';
      (
      e || window.event
      ).returnValue = confirmationMessage;
      return confirmationMessage;
    };
  }
};

Admin.formFeedback = function () {
  $('body').on('submit', 'form', function (el) {
    var submitButtons = $(this).find('[type=submit]');

    if( submitButtons.hasClass('noFormFeedback') ) {
        return;
    }

    submitButtons
      .addClass('disabled');

    if (el.originalEvent && el.originalEvent.submitter) {
      var $button = $(el.originalEvent.submitter);
      if ($button.find('i').length == 0) {
        $button
          .prepend(' ')
          .prepend($('<i />').addClass(Admin.spinnerClass()));
      } else {
        $button.find('i').attr('class', Admin.spinnerClass());
      }
    }
  });

  var activateErrorTab = function(e) {
    var pane = $(e.target).closest('.tab-pane').get(0);
    var selector = 'a[href="#' + pane.attributes['id'].value + '"]';
    Admin.showTab($(Admin.TABS_SELECTOR).find(selector));
  };
  $('form input').on('invalid', _.debounce(activateErrorTab, 150))
};

/**
 * Helper to process row action links
 */
Admin.processLink = function (event) {
  var $el = $(event.currentTarget);
  var checkbox = $(event.currentTarget.attributes["href"].value);
  var form = checkbox.get(0).form;
  var action = $el.data('row-action');
  var confirmMessage = $el.data('confirm-message');
  if (confirmMessage && !confirm(confirmMessage)) {
    return false;
  }
  $('input[type=checkbox]', form).prop('checked', false);
  checkbox.prop("checked", true);
  $('#bulk-action select', form).val(action);
  form.submit();
  return false;
};

Admin.removeHash = function() {
  var scrollV, scrollH, loc = window.location;
  if ("pushState" in history)
    history.pushState("", document.title, loc.pathname + loc.search);
  else {
    // Prevent scrolling by storing the page's current scroll offset
    scrollV = document.body.scrollTop;
    scrollH = document.body.scrollLeft;

    loc.hash = "";

    // Restore the scroll offset, should be flicker free
    document.body.scrollTop = scrollV;
    document.body.scrollLeft = scrollH;
  }
};

/**
 * Extra stuff
 *
 * rounded corners, striped table rows, etc
 *
 * @return void
 */
Admin.extra = function () {
  var hash = document.location.hash;
  var $tabs = $(Admin.TABS_SELECTOR);
  if (hash && hash.match("^#tab_")) {
    // Activates tab if hash starting with tab_* is given
    Admin.showTab($tabs.find('a[href="' + hash.replace('tab_', '') + '"]'));
    Admin.removeHash();
  } else {
    // Activates the first tab by default
    Admin.showTab($tabs.find('li:first-child a'));
  }

  // Apply buttons jump to current tab for persistence
  $('.page-body [name="_apply"]').click(function () {
    var activeTab = $tabs.find('.active[data-bs-toggle=tab]').attr('href');
    if (!activeTab) {
      return;
    }
    var form = $('.page-body form:first');
    var action = form.attr('action').split('#')[0];
    form.attr('action', action + activeTab.replace('#', '#tab_'));
  });

  if (typeof $.prototype.elastic == 'function') {
    $('textarea').not('.content').elastic();
  }
  $("div.message").addClass("notice");
  $('#loading p').addClass('ui-corner-bl ui-corner-br');

  if (typeof $.fn.select2 !== 'undefined') {
    $('select:not(".no-select2")').select2(Croogo.themeSettings.select2Defaults);
  }

  Admin.tooltips();
};

/**
 * Bootstrap 5 does not initialise tooltips on its own - opting in is the
 * documented behaviour, not an optimisation.
 */
Admin.tooltips = function (context) {
  var root = context || document;
  Array.prototype.forEach.call(
    root.querySelectorAll('[data-bs-toggle="tooltip"]'),
    function (el) {
      bootstrap.Tooltip.getOrCreateInstance(el);
    }
  );
};

/**
 * Initialize boxes to enable to toggling Box content
 */
Admin.slideBoxToggle = function () {
  var iconMinus = '.' + Admin.iconClass('minus', false);
  var iconPlus = '.' + Admin.iconClass('plus', false);
  $('body').on('click', '.box-title', function () {
    $(this)
      .next().slideToggle(function () {
      $(this).trigger('slide.toggle');
    }).end()
      .find(iconMinus)
      .switchClass(iconMinus.substring(1), iconPlus.substring(1)).end()
      .find(iconPlus)
      .switchClass(iconPlus.substring(1), iconMinus.substring(1));
  });
};

/**
 * Helper callback for toggling record selection
 */
Admin.toggleRowSelection = function (selector, checkboxSelector) {
  var $selector = $(selector);
  if (typeof checkboxSelector == 'undefined') {
    checkboxSelector = "input.row-select[type='checkbox']";
  }
  $selector.on('click', function (e) {
    $(checkboxSelector).prop('checked', $selector.is(':checked'));
  });
};

/**
 * Helper method to get the proper icon class name based on theme settings
 */
Admin.iconClass = function (icon, includeDefault) {
  var result = '';
  if (typeof Croogo.themeSettings.icons[icon] === 'string') {
    icon = Croogo.themeSettings.icons[icon];
  }
  if (typeof includeDefault === 'undefined') {
    includeDefault = true;
  }
  if (includeDefault) {
    result = Croogo.themeSettings.iconDefaults['iconSet'] + ' ';
  }
  result += Croogo.themeSettings.iconDefaults['prefix'] + '-' + icon;
  return result.trim();
};

/**
 * Date and time controls.
 *
 * The visible control is the browser's own `<input type="date|time|datetime-local">`
 * (see Croogo\Core\View\Widget\DateTimeWidget), which speaks naive wall-clock time
 * and knows nothing about time zones. What actually gets posted is the hidden input
 * beside it, named by `data-related`, holding an ISO-8601 string with an explicit
 * offset - one of CakePHP's `DateTimeType` marshal formats, so the server reads back
 * exactly the instant it rendered.
 *
 * PHP fills both fields on render; everything below is the inbound half, run when
 * the user changes the control. The wall clock is read in the user's own zone
 * (`Auth.User.timezone`, published as `data-timezone`) through `Intl`, which is what
 * moment-timezone used to be loaded for.
 */

/**
 * Offset of `timeZone` from UTC, in minutes, at the given instant.
 *
 * Intl has no API for this, but it will format an instant *into* a zone; reading
 * those parts back as though they were UTC and subtracting gives the offset, DST
 * and historical changes included.
 */
Admin.zoneOffset = function (instant, timeZone) {
  var parts = {};
  new Intl.DateTimeFormat('en-US', {
    timeZone: timeZone,
    hour12: false,
    year: 'numeric', month: '2-digit', day: '2-digit',
    hour: '2-digit', minute: '2-digit', second: '2-digit'
  }).formatToParts(instant).forEach(function (part) {
    parts[part.type] = part.value;
  });

  // `hour` comes back as 24 rather than 0 at midnight in some ICU versions.
  var asUtc = Date.UTC(
    parts.year, parts.month - 1, parts.day,
    parts.hour % 24, parts.minute, parts.second
  );

  return (asUtc - (instant.getTime() - instant.getUTCMilliseconds())) / 60000;
};

/**
 * The instant named by a wall clock in `timeZone`.
 *
 * Two passes: the first offset is the one in force at the *nominal* UTC time, which
 * is the wrong side of a DST boundary for values within an hour of the jump. Asking
 * again at the candidate instant settles it.
 */
Admin.instantFromZoned = function (parts, timeZone) {
  var naive = Date.UTC(parts[0], parts[1] - 1, parts[2], parts[3], parts[4], parts[5]);
  var offset = Admin.zoneOffset(new Date(naive), timeZone);
  var instant = new Date(naive - offset * 60000);
  var settled = Admin.zoneOffset(instant, timeZone);
  if (settled !== offset) {
    instant = new Date(naive - settled * 60000);
  }

  return instant;
};

/**
 * Formats an instant as ISO-8601 with the offset `timeZone` had then, e.g.
 * `2026-08-30T12:00:00+02:00` - PHP's DateTime::ATOM, the same shape the widget
 * rendered into the hidden field in the first place.
 */
Admin.toAtom = function (instant, timeZone) {
  var offset = Admin.zoneOffset(instant, timeZone);
  var magnitude = Math.abs(offset);
  var pad = function (n) {
    return (n < 10 ? '0' : '') + n;
  };

  return new Date(instant.getTime() + offset * 60000).toISOString().slice(0, 19) +
    (offset < 0 ? '-' : '+') +
    pad(Math.floor(magnitude / 60)) + ':' + pad(magnitude % 60);
};

/**
 * Translates one native control's value into the string its hidden field posts.
 */
Admin.dateTimeValue = function (value, inputType, timeZone) {
  if (!value) {
    return '';
  }

  // A date names a day and a time names a time of day; neither is an instant, so
  // there is nothing to shift and the native value is already what Cake marshals.
  if (inputType !== 'datetime-local') {
    return value;
  }

  var parts = value.match(/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})(?::(\d{2}))?/);
  if (!parts) {
    return value;
  }

  try {
    return Admin.toAtom(Admin.instantFromZoned([
      +parts[1], +parts[2], +parts[3], +parts[4], +parts[5], +(parts[6] || 0)
    ], timeZone), timeZone);
  } catch (e) {
    // An unknown zone name makes Intl throw. Posting the wall clock unqualified is
    // wrong by an offset, but it is still a readable date rather than nothing.
    return value.replace('T', ' ');
  }
};

Admin.dateTimeFields = function(datePickers) {
  datePickers = typeof datePickers !== 'undefined' ? datePickers : $('[role=datetime-picker]');

  datePickers.each(function () {
    var group = $(this);
    var input = group.find('input[data-related]').first();
    if (!input.length) {
      return;
    }

    var hidden = $(document.getElementById(input.data('related')));
    if (!hidden.length) {
      return;
    }

    var timeZone = group.data('timezone') || 'UTC';
    var inputType = input.attr('type');

    input.on('change', function () {
      hidden.val(Admin.dateTimeValue(input.val(), inputType, timeZone));
    });
  });
};
