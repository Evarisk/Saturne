/* Copyright (C) 2025-2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    js/modules/mediaPending.js
 * \ingroup saturne
 * \brief   JavaScript mediaPending file
 */

/**
 * Init mediaPending JS
 *
 * @memberof Saturne_Framework_Init
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @type {Object}
 */
window.saturne.mediaPending = {};

/**
 * MediaPending init
 *
 * @memberof Saturne_MediaPending
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void}
 */
window.saturne.mediaPending.init = function() {
  window.saturne.mediaPending.event();
  window.saturne.mediaPending.initTypeSelect($(document));
  window.saturne.mediaPending.applyMode($(document));
};

/**
 * MediaPending event bindings
 *
 * @memberof Saturne_MediaPending
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void}
 */
window.saturne.mediaPending.event = function() {
  $(document).on('click', '.saturne-media-tab', window.saturne.mediaPending.selectTab);
  $(document).on('click', '.saturne-pending-mode', window.saturne.mediaPending.selectMode);
  $(document).on('click', '.saturne-pending-unlink', window.saturne.mediaPending.unlinkFromRecord);
  $(document).on('click', '.saturne-pending-row', window.saturne.mediaPending.previewMedia);
  $(document).on('change', '.saturne-pending-select-all', window.saturne.mediaPending.selectAll);
  $(document).on('change', '.saturne-pending-select', window.saturne.mediaPending.refreshSelection);
  $(document).on('change', '.saturne-pending-object-type', window.saturne.mediaPending.loadTargets);
  $(document).on('change', '.saturne-pending-object', window.saturne.mediaPending.refreshSelection);
  $(document).on('click', '.saturne-pending-assign', window.saturne.mediaPending.assignMedias);
  $(document).on('change', '.saturne-pending-description', window.saturne.mediaPending.saveDescription);
};

/**
 * Turn the object type list into a searchable select showing each type with its picto
 *
 * The picto is rendered server side and carried by the option, so the list reads the same here
 * as everywhere else in Dolibarr.
 *
 * @memberof Saturne_MediaPending
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param   {jQuery} container Element holding the select
 * @returns {void}
 */
window.saturne.mediaPending.initTypeSelect = function(container) {
  var select = container.find('.saturne-pending-object-type');

  if (!select.length || typeof $.fn.select2 !== 'function' || select.hasClass('select2-hidden-accessible')) {
    return;
  }

  var withPicto = function(state) {
    if (!state.id) {
      return state.text;
    }

    var picto = $(state.element).data('picto');

    if (!picto) {
      return state.text;
    }

    return $('<span class="saturne-pending-type-option"></span>').html(picto).append(document.createTextNode(' ' + state.text));
  };

  select.select2({
    width            : 'resolve',
    templateResult   : withPicto,
    templateSelection: withPicto
  });
};

/**
 * Name of the display mode the viewer picked last
 *
 * @memberof Saturne_MediaPending
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {string} Mode to apply
 */
window.saturne.mediaPending.storedMode = function() {
  try {
    return window.localStorage.getItem('saturneMediaViewMode') || 'details';
  } catch (e) {
    // Storage can be refused, the list simply opens on its default mode
    return 'details';
  }
};

/**
 * Lay the list out in the mode the viewer picked
 *
 * Every mode keeps the same rows, only their layout changes, so selection, description and
 * assignment behave the same in all of them.
 *
 * @memberof Saturne_MediaPending
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param   {jQuery} container Element holding the list
 * @param   {string} mode      Mode to apply, the remembered one when left out
 * @returns {void}
 */
window.saturne.mediaPending.applyMode = function(container, mode) {
  var pending = container.find('.saturne-pending');

  if (!pending.length) {
    return;
  }

  var wanted = mode || window.saturne.mediaPending.storedMode();

  pending.removeClass('mode-details mode-tiles mode-compact').addClass('mode-' + wanted);
  pending.find('.saturne-pending-mode').removeClass('active');
  pending.find('.saturne-pending-mode[data-mode="' + wanted + '"]').addClass('active');
};

/**
 * Switch the list to the mode that was clicked and remember it
 *
 * @memberof Saturne_MediaPending
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void}
 */
window.saturne.mediaPending.selectMode = function() {
  var mode = $(this).data('mode');

  try {
    window.localStorage.setItem('saturneMediaViewMode', mode);
  } catch (e) {
    // Storage can be refused, the mode then lasts for this page only
  }

  window.saturne.mediaPending.applyMode($(this).closest('.saturne-pending').parent(), mode);
};

/**
 * Show the tab that was clicked, loading the pending list the first time it is opened
 *
 * @memberof Saturne_MediaPending
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void}
 */
window.saturne.mediaPending.selectTab = function() {
  var tab   = $(this);
  var name  = tab.data('tab');
  var modal = tab.closest('.modal-container');

  modal.find('.saturne-media-tab').removeClass('active');
  tab.addClass('active');

  modal.find('.saturne-media-tab-pane').addClass('hidden');
  var pane = modal.find('.saturne-media-tab-pane[data-tab="' + name + '"]').removeClass('hidden');

  // The gallery footer acts on the grid selection, which the pending list does not share
  modal.toggleClass('pending-tab-active', name === 'pending');

  if (name === 'pending' && pane.attr('data-loaded') !== '1') {
    window.saturne.mediaPending.load(pane);
  }
};

/**
 * Fetch the pending list and put it in its pane
 *
 * @memberof Saturne_MediaPending
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param   {jQuery} pane Pane receiving the list
 * @returns {void}
 */
window.saturne.mediaPending.load = function(pane) {
  var token          = window.saturne.toolbox.getToken();
  var querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);

  window.saturne.loader.display(pane);

  $.ajax({
    url    : document.URL + querySeparator + 'subaction=pendingMedias&token=' + token,
    type   : 'POST',
    success: function(resp) {
      pane.removeClass('wpeo-loader').html($(resp).find('.saturne-media-tab-pane[data-tab="pending"]').html()).attr('data-loaded', '1');
      pane.find('.wpeo-loader').removeClass('wpeo-loader');
      window.saturne.mediaPending.initTypeSelect(pane);
      window.saturne.mediaPending.applyMode(pane);
      window.saturne.modal.loadLazyImages();
    },
    error  : function() {
      pane.removeClass('wpeo-loader');
    }
  });
};

/**
 * Show the media of the row that was clicked in the preview pane
 *
 * @memberof Saturne_MediaPending
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param   {Object} event Click event
 * @returns {void}
 */
window.saturne.mediaPending.previewMedia = function(event) {
  // The checkbox and the description are controls of their own, they do not drive the preview
  if ($(event.target).is('input')) {
    return;
  }

  var row     = $(this);
  var pending = row.closest('.saturne-pending');

  pending.find('.saturne-pending-row').removeClass('selected');
  row.addClass('selected');

  pending.find('.saturne-pending-preview-placeholder').addClass('hidden');
  pending.find('.saturne-pending-preview-image').attr('src', row.data('url')).removeClass('hidden');
  pending.find('.saturne-pending-preview-name').text(row.data('filename'));
};

/**
 * Tick or untick every media of the list at once
 *
 * @memberof Saturne_MediaPending
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void}
 */
window.saturne.mediaPending.selectAll = function() {
  var pending = $(this).closest('.saturne-pending');

  pending.find('.saturne-pending-select').prop('checked', $(this).prop('checked'));
  window.saturne.mediaPending.refreshSelection.call(this);
};

/**
 * Keep the counter and the assign button in step with what is ticked
 *
 * @memberof Saturne_MediaPending
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void}
 */
window.saturne.mediaPending.refreshSelection = function() {
  var pending  = $(this).closest('.saturne-pending');
  var selected = pending.find('.saturne-pending-select:checked').length;
  var counter  = pending.find('.saturne-pending-counter');
  var target   = pending.find('.saturne-pending-object').val();

  if (selected > 0) {
    counter.text(selected + ' ' + counter.data('label'));
  } else {
    counter.text(counter.data('total'));
  }

  pending.find('.saturne-pending-assign').toggleClass('button-disable', !(selected > 0 && target));
};

/**
 * List the records of the chosen type so medias can be assigned to one of them
 *
 * @memberof Saturne_MediaPending
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void}
 */
window.saturne.mediaPending.loadTargets = function() {
  var pending        = $(this).closest('.saturne-pending');
  var objectType     = $(this).val();
  var targetSelect   = pending.find('.saturne-pending-object');
  var token          = window.saturne.toolbox.getToken();
  var querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);

  targetSelect.prop('disabled', true).html('<option value=""></option>');

  if (!objectType) {
    window.saturne.mediaPending.refreshSelection.call(this);
    return;
  }

  $.ajax({
    url    : document.URL + querySeparator + 'subaction=listMediaTargets&object_type=' + encodeURIComponent(objectType) + '&token=' + token,
    type   : 'POST',
    success: function(resp) {
      targetSelect.html($(resp).find('.saturne-pending-target-options').html()).prop('disabled', false);
    },
    error  : function() {
      targetSelect.prop('disabled', false);
    }
  });
};

/**
 * Assign every ticked media to the chosen record
 *
 * @memberof Saturne_MediaPending
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void}
 */
window.saturne.mediaPending.assignMedias = function() {
  var button = $(this);

  if (button.hasClass('button-disable')) {
    return;
  }

  var pending        = button.closest('.saturne-pending');
  var objectType     = pending.find('.saturne-pending-object-type').val();
  var objectId       = pending.find('.saturne-pending-object').val();
  var token          = window.saturne.toolbox.getToken();
  var querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);
  var filenames      = [];

  pending.find('.saturne-pending-select:checked').each(function() {
    filenames.push($(this).closest('.saturne-pending-row').data('filename'));
  });

  if (!filenames.length || !objectId) {
    return;
  }

  window.saturne.loader.display(pending);

  $.ajax({
    url        : document.URL + querySeparator + 'subaction=assignMedias&token=' + token,
    type       : 'POST',
    data       : JSON.stringify({ objectType: objectType, objectId: objectId, filenames: filenames }),
    processData: false,
    contentType: 'application/json',
    success    : function(resp) {
      var jsonStr = $(resp).find('#saturne-assign-medias-response').text();
      if (jsonStr) {
        try { resp = JSON.parse(jsonStr); } catch (e) {}
      } else if (typeof resp === 'string') {
        try { resp = JSON.parse(resp); } catch (e) {}
      }
      if (resp && resp.message) {
        if (typeof $.jnotify === 'function') {
          $.jnotify(resp.message, {color: 'green'});
        } else {
          alert(resp.message);
        }
      }
      // Assigned medias are no longer pending: the list is rebuilt rather than patched
      pending.removeClass('wpeo-loader');
      window.saturne.mediaPending.load(pending.closest('.saturne-media-tab-pane').attr('data-loaded', '0'));
    },
    error      : function() {
      $('.wpeo-loader').removeClass('wpeo-loader');
    }
  });
};

/**
 * Take the media off the record that was clicked, leaving the library copy alone
 *
 * @memberof Saturne_MediaPending
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param   {Object} event Click event
 * @returns {void}
 */
window.saturne.mediaPending.unlinkFromRecord = function(event) {
  // The row reacts to a click by previewing its media, unlinking is not that
  event.stopPropagation();

  var button = $(this);
  var pane   = button.closest('.saturne-media-tab-pane');
  var confirmMsg = button.attr('data-confirm') || button.attr('title');

  var dialogDiv = $('<div title="Confirmation"></div>').html(confirmMsg);
  dialogDiv.dialog({
    resizable: false,
    height: "auto",
    width: 400,
    modal: true,
    buttons: [
      {
        text: "Ok",
        click: function() {
          $(this).dialog("close");

          var token          = window.saturne.toolbox.getToken();
          var querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);

          window.saturne.loader.display(button.closest('.saturne-pending'));

          $.ajax({
            url        : document.URL + querySeparator + 'subaction=unlinkMediaFromRecord&token=' + token,
            type       : 'POST',
            data       : JSON.stringify({
              element : button.data('element'),
              ref     : button.data('ref'),
              filename: button.closest('.saturne-pending-row').data('filename')
            }),
            processData: false,
            contentType: 'application/json',
            success    : function() {
              // The media may have gone back to being pending: the list is rebuilt rather than patched
              window.saturne.mediaPending.load(pane.attr('data-loaded', '0'));
            },
            error      : function() {
              $('.wpeo-loader').removeClass('wpeo-loader');
            }
          });
        }
      },
      {
        text: "Annuler",
        click: function() {
          $(this).dialog("close");
        }
      }
    ]
  });
};

/**
 * Save the description of a media, kept next to the file in the ECM index
 *
 * @memberof Saturne_MediaPending
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void}
 */
window.saturne.mediaPending.saveDescription = function() {
  var input          = $(this);
  var token          = window.saturne.toolbox.getToken();
  var querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);

  $.ajax({
    url        : document.URL + querySeparator + 'subaction=setMediaDescription&token=' + token,
    type       : 'POST',
    data       : JSON.stringify({
      filename   : input.closest('.saturne-pending-row').data('filename'),
      description: input.val()
    }),
    processData: false,
    contentType: 'application/json',
    success    : function() {
      input.addClass('saturne-pending-description-saved');
      setTimeout(function() {
        input.removeClass('saturne-pending-description-saved');
      }, 1200);
    }
  });
};
