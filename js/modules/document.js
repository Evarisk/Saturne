/* Copyright (C) 2022-2023 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * Library javascript to enable Browser notifications
 */

/**
 * \file    js/modules/document.js
 * \ingroup saturne
 * \brief   JavaScript file document for module Saturne.
 */


/**
 * Initialise l'objet "document" ainsi que la méthode "init" obligatoire pour la bibliothèque Saturne.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.saturne.document = {};

/**
 * La méthode appelée automatiquement par la bibliothèque Saturne.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.document.init = function() {
	window.saturne.document.event();
	window.saturne.document.setupDropZone();
  window.saturne.document.initFileLists();
};

/**
 * La méthode contenant tous les événements pour les documents.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.document.event = function() {
  $(document).on('click', '#builddoc_generatebutton', window.saturne.document.displayLoader);
  $(document).on('click', '.pdf-generation', window.saturne.document.displayLoader);
  $(document).on('click', '.download-template', window.saturne.document.autoDownloadTemplate);
  $(document).on('dragenter dragover', window.saturne.document.onDragOver);
  $(document).on('dragleave', window.saturne.document.onDragLeave);
  $(document).on('drop', window.saturne.document.onDrop);
  $(document).on('click', '.saturne-documents__sort', window.saturne.document.onFileListSort);
  $(document).on('input', '.saturne-documents__search-input', window.saturne.document.onFileListSearch);
  $(document).on('keydown', '.saturne-documents__search-input', window.saturne.document.onFileListSearchKeydown);
  $(document).on('click', '.saturne-documents__type', window.saturne.document.onFileListType);
  $(document).on('click', '.saturne-documents__more-button', window.saturne.document.onFileListMore);
  // A documents box loaded by AJAX is set up as soon as it is in the page
  $(document).on('ajaxComplete', window.saturne.document.initFileLists);
};

/**
 * Display loader on generation document.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.document.displayLoader = function(  ) {
	window.saturne.loader.display($(this).closest('.div-table-responsive-no-min'));
};

/**
 * Auto download document template
 *
 * @memberof Saturne_Framework_Document
 *
 * @since   1.3.0
 * @version 23.7.1
 *
 * @return {void}
 */
window.saturne.document.autoDownloadTemplate = function() {
  let token          = window.saturne.toolbox.getToken();
  let url            = document.URL.replace(/#.*$/, '');
  let querySeparator = window.saturne.toolbox.getQuerySeparator(url);
  // A single .file-generation block holds every template of the type: each button reads the
  // hidden fields printed right before it, those of its own file
  let $button        = $(this);
  let type           = $button.prevAll('.template-type').first().val();
  let filename       = $button.prevAll('.template-name').first().val();
  let path           = $button.prevAll('.template-path').first().val();

  $.ajax({
    url: url + querySeparator + 'action=download_template&filename=' + encodeURIComponent(filename) + '&type=' + encodeURIComponent(type) + '&token=' + token,
    type: 'POST',
    success: function() {
      window.saturne.signature.download(path + filename, filename);
      $.ajax({
        url: url + querySeparator + 'action=remove_file&filename=' + encodeURIComponent(filename) + '&token=' + token,
        type: 'POST',
        success: function () {},
        error: function() {}
      });
    },
    error: function () {}
  });
};

/**
 * Tell whether the current drag event carries files.
 *
 * @memberof Saturne_Framework_Document
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @param  {Object}  event Drag event.
 * @return {boolean}       True when files are being dragged.
 */
window.saturne.document.isFileDrag = function(event) {
  let dataTransfer = event.originalEvent ? event.originalEvent.dataTransfer : null;
  return !!(dataTransfer && dataTransfer.types && Array.prototype.indexOf.call(dataTransfer.types, 'Files') !== -1);
};

/**
 * Return the attached-files upload zone of the current page (empty jQuery set if none).
 *
 * @memberof Saturne_Framework_Document
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @return {Object} jQuery set for the upload zone.
 */
window.saturne.document.getUploadZone = function() {
  return $('.attachareaformuserfile').first();
};

/**
 * Turn the upload zone into a visible dropzone by appending a hint (icon + label)
 * so the user knows where files can be dropped.
 *
 * @memberof Saturne_Framework_Document
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @return {void}
 */
window.saturne.document.setupDropZone = function() {
  let zone = window.saturne.document.getUploadZone();
  if (!zone.length || zone.find('.saturne-dropzone-hint').length) {
    return;
  }
  let label = $('#saturne-drop-files-label').text();
  if (!label) {
    return;
  }
  zone.addClass('saturne-dropzone');
  zone.append('<div class="saturne-dropzone-hint"><i class="fas fa-cloud-upload-alt"></i> ' + label + '</div>');
};

/**
 * While a file is dragged anywhere on a page exposing the upload zone, make the
 * whole page a valid drop target (so the browser never opens the file) and
 * highlight the upload zone to show where the file will be sent.
 *
 * @memberof Saturne_Framework_Document
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @param  {Object} event Drag event.
 * @return {void}
 */
window.saturne.document.onDragOver = function(event) {
  if (!window.saturne.document.isFileDrag(event)) {
    return;
  }
  let zone = window.saturne.document.getUploadZone();
  if (!zone.length) {
    return;
  }
  event.preventDefault();
  zone.addClass('attacharea-dragover');
};

/**
 * Remove the highlight once the file leaves the window.
 *
 * @memberof Saturne_Framework_Document
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @param  {Object} event Drag event.
 * @return {void}
 */
window.saturne.document.onDragLeave = function(event) {
  // relatedTarget is null only when the pointer actually leaves the window
  if (event.originalEvent && event.originalEvent.relatedTarget === null) {
    window.saturne.document.getUploadZone().removeClass('attacharea-dragover');
  }
};

/**
 * Inject the files dropped anywhere on the page into the upload form then trigger
 * the upload.
 *
 * The dropped files are staged into the native file input (so the file name is
 * shown like a manual selection), then the real "ENVOYER FICHIER" button is
 * clicked : this is required because Dolibarr's actions_linkedfiles.inc.php only
 * processes the upload when the "sendit" parameter is present (which a plain
 * form submit would not send).
 *
 * @memberof Saturne_Framework_Document
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @param  {Object} event Drop event.
 * @return {void}
 */
window.saturne.document.onDrop = function(event) {
  if (!window.saturne.document.isFileDrag(event)) {
    return;
  }
  let zone = window.saturne.document.getUploadZone();
  if (!zone.length) {
    return;
  }
  event.preventDefault();
  zone.removeClass('attacharea-dragover');

  let fileInput = zone.find('input[type="file"][name^="userfile"]')[0];
  let sendButton = zone.find('input[type="submit"][name="sendit"]')[0];
  // Upload disabled (MAIN_UPLOAD_DOC off or no write permission) : do nothing
  if (!fileInput || fileInput.disabled || !sendButton) {
    return;
  }

  let files = event.originalEvent.dataTransfer.files;
  if (!files || files.length === 0) {
    return;
  }

  let dataTransfer = new window.DataTransfer();
  for (let i = 0; i < files.length; i++) {
    dataTransfer.items.add(files[i]);
  }
  fileInput.files = dataTransfer.files;

  // Visual feedback while the file is being sent (the page reloads on success)
  window.saturne.loader.display(zone);

  sendButton.click();
};

/**
 * Order a column of the documents box is first sorted in : the names from A to Z, the biggest and newest files first.
 *
 * @memberof Saturne_Framework_Document
 *
 * @since   23.8.0
 * @version 23.8.0
 */
window.saturne.document.fileListDefaultOrders = { name: 'asc', size: 'desc', date: 'desc' };

/**
 * Set up every documents box of the page not set up yet : sort buttons on the column titles, search and type filters.
 *
 * @memberof Saturne_Framework_Document
 *
 * @since   23.8.0
 * @version 23.8.0
 *
 * @return {void}
 */
window.saturne.document.initFileLists = function() {
  $('.saturne-documents:not(.saturne-documents--enhanced)').each(function() {
    let $box = $(this);

    $box.addClass('saturne-documents--enhanced');
    $box.find('th[data-sort-key]').each(function() {
      $(this).wrapInner('<button type="button" class="saturne-documents__sort"></button>');
      $(this).children('.saturne-documents__sort').append('<i class="saturne-documents__sort-icon fas fa-sort" aria-hidden="true"></i>');
    });
    $box.find('.saturne-documents__filters').prop('hidden', false);

    let sort = window.saturne.document.readFileListSort($box);
    $box.attr('data-sort-current', sort.key + ':' + sort.order);
    window.saturne.document.sortFileList($box);
    window.saturne.document.refreshFileList($box);
  });
};

/**
 * Return the sort the user last chose for this kind of documents box, the newest files first otherwise.
 *
 * @memberof Saturne_Framework_Document
 *
 * @since   23.8.0
 * @version 23.8.0
 *
 * @param  {Object} $box Documents box.
 * @return {Object}      Sort as {key, order}.
 */
window.saturne.document.readFileListSort = function($box) {
  let stored = '';
  try {
    stored = window.localStorage.getItem('saturne.documents.sort.' + $box.attr('data-sort-storage')) || '';
  } catch (error) {
    // Storage blocked (private window, previews) : the default sort is used
  }

  let parts = stored.split(':');
  if (window.saturne.document.fileListDefaultOrders[parts[0]] && (parts[1] === 'asc' || parts[1] === 'desc')) {
    return { key: parts[0], order: parts[1] };
  }

  return { key: 'date', order: 'desc' };
};

/**
 * Return the current sort of a documents box.
 *
 * @memberof Saturne_Framework_Document
 *
 * @since   23.8.0
 * @version 23.8.0
 *
 * @param  {Object} $box Documents box.
 * @return {Object}      Sort as {key, order}.
 */
window.saturne.document.getFileListSort = function($box) {
  let parts = ($box.attr('data-sort-current') || 'date:desc').split(':');

  return { key: parts[0], order: parts[1] };
};

/**
 * Sort the documents box on the column title clicked : a second click on the same column reverses the order.
 *
 * @memberof Saturne_Framework_Document
 *
 * @since   23.8.0
 * @version 23.8.0
 *
 * @return {void}
 */
window.saturne.document.onFileListSort = function() {
  let $box    = $(this).closest('.saturne-documents');
  let key     = $(this).closest('th').attr('data-sort-key');
  let current = window.saturne.document.getFileListSort($box);
  let order   = window.saturne.document.fileListDefaultOrders[key];

  if (current.key === key) {
    order = (current.order === 'asc' ? 'desc' : 'asc');
  }

  $box.attr('data-sort-current', key + ':' + order);
  try {
    window.localStorage.setItem('saturne.documents.sort.' + $box.attr('data-sort-storage'), key + ':' + order);
  } catch (error) {
    // Storage blocked : the sort only lasts until the page is left
  }

  window.saturne.document.sortFileList($box);
  window.saturne.document.refreshFileList($box);
};

/**
 * Compare two rows of a documents box on a column.
 *
 * @memberof Saturne_Framework_Document
 *
 * @since   23.8.0
 * @version 23.8.0
 *
 * @param  {Object} first  First row.
 * @param  {Object} second Second row.
 * @param  {string} key    Column : name, size or date.
 * @return {number}        Negative, zero or positive as for Array.sort().
 */
window.saturne.document.compareFileListRows = function(first, second, key) {
  if (key === 'name') {
    // numeric : DU9 comes before DU10
    return String($(first).attr('data-name')).localeCompare(String($(second).attr('data-name')), undefined, { numeric: true, sensitivity: 'base' });
  }

  return (parseInt($(first).attr('data-' + key), 10) || 0) - (parseInt($(second).attr('data-' + key), 10) || 0);
};

/**
 * Put the rows of a documents box in the order of its current sort, and show this sort on the column titles.
 *
 * @memberof Saturne_Framework_Document
 *
 * @since   23.8.0
 * @version 23.8.0
 *
 * @param  {Object} $box Documents box.
 * @return {void}
 */
window.saturne.document.sortFileList = function($box) {
  let sort    = window.saturne.document.getFileListSort($box);
  let factor  = (sort.order === 'asc' ? 1 : -1);
  let $anchor = $box.find('.saturne-documents__no-match');

  if ($anchor.length) {
    let rows = $box.find('.saturne-documents__row').get().sort(function(first, second) {
      return factor * window.saturne.document.compareFileListRows(first, second, sort.key);
    });
    $anchor.before(rows);
  }

  $box.find('th[data-sort-key]').each(function() {
    let $title   = $(this);
    let isSorted = $title.attr('data-sort-key') === sort.key;
    let icon     = 'fa-sort';
    let ariaSort = 'none';
    if (isSorted) {
      icon     = (sort.order === 'asc' ? 'fa-sort-up' : 'fa-sort-down');
      ariaSort = (sort.order === 'asc' ? 'ascending' : 'descending');
    }

    $title.toggleClass('saturne-documents__sorted', isSorted);
    $title.attr('aria-sort', ariaSort);
    $title.find('.saturne-documents__sort-icon').removeClass('fa-sort fa-sort-up fa-sort-down').addClass(icon);
  });
};

/**
 * Lower case a text and remove its accents, so that the search ignores both.
 *
 * @memberof Saturne_Framework_Document
 *
 * @since   23.8.0
 * @version 23.8.0
 *
 * @param  {string} text Text.
 * @return {string}      Normalized text.
 */
window.saturne.document.normalizeFileName = function(text) {
  return String(text || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim();
};

/**
 * Tell whether a row of a documents box matches the search and the type filter.
 *
 * @memberof Saturne_Framework_Document
 *
 * @since   23.8.0
 * @version 23.8.0
 *
 * @param  {Object}  $row Row.
 * @param  {string}  term Normalized searched text, '' for none.
 * @param  {string}  type File type filtered, '' for all.
 * @return {boolean}      True when the row matches.
 */
window.saturne.document.fileListRowMatches = function($row, term, type) {
  if (type !== '' && $row.attr('data-type') !== type) {
    return false;
  }

  return term === '' || window.saturne.document.normalizeFileName($row.attr('data-name')).indexOf(term) !== -1;
};

/**
 * Show the rows of a documents box that match its search and type filter, the first ones only until the list is expanded.
 *
 * @memberof Saturne_Framework_Document
 *
 * @since   23.8.0
 * @version 23.8.0
 *
 * @param  {Object} $box Documents box.
 * @return {void}
 */
window.saturne.document.refreshFileList = function($box) {
  let term     = window.saturne.document.normalizeFileName($box.find('.saturne-documents__search-input').val());
  let type     = $box.find('.saturne-documents__type--active').attr('data-type') || '';
  let limit    = parseInt($box.attr('data-page-size'), 10) || 10;
  let expanded = $box.hasClass('saturne-documents--expanded');
  let matched  = 0;
  let $lastRow = $();

  $box.find('.saturne-documents__row').each(function() {
    let $row    = $(this);
    let isShown = false;
    if (window.saturne.document.fileListRowMatches($row, term, type)) {
      matched++;
      isShown = expanded || matched <= limit;
    }

    $row.prop('hidden', !isShown);
    if (isShown) {
      $lastRow = $row;
    }
  });

  let $noMatch = $box.find('.saturne-documents__no-match').prop('hidden', matched > 0);
  let $more    = $box.find('.saturne-documents__more').prop('hidden', matched <= limit);
  let $button  = $more.find('.saturne-documents__more-button');
  let label    = expanded ? $button.attr('data-label-less') : String($button.attr('data-label-more')).replace('%s', matched - limit);

  $button.text(label).append(' <i class="fas fa-chevron-' + (expanded ? 'up' : 'down') + '" aria-hidden="true"></i>');
  $button.attr('aria-expanded', expanded ? 'true' : 'false');

  // The last row shown carries no bottom border : the border of the box closes the list
  if (matched === 0) {
    $lastRow = $noMatch;
  } else if (matched > limit) {
    $lastRow = $more;
  }
  $box.find('.saturne-documents__last').removeClass('saturne-documents__last');
  $lastRow.addClass('saturne-documents__last');
};

/**
 * Filter the documents box on the searched text.
 *
 * @memberof Saturne_Framework_Document
 *
 * @since   23.8.0
 * @version 23.8.0
 *
 * @return {void}
 */
window.saturne.document.onFileListSearch = function() {
  window.saturne.document.refreshFileList($(this).closest('.saturne-documents'));
};

/**
 * Keep Enter in the search field from submitting the generation form around it, and clear the search on Escape.
 *
 * @memberof Saturne_Framework_Document
 *
 * @since   23.8.0
 * @version 23.8.0
 *
 * @param  {Object} event Keydown event.
 * @return {void}
 */
window.saturne.document.onFileListSearchKeydown = function(event) {
  if (event.key === 'Enter') {
    event.preventDefault();
  } else if (event.key === 'Escape' && $(this).val() !== '') {
    event.preventDefault();
    $(this).val('');
    window.saturne.document.refreshFileList($(this).closest('.saturne-documents'));
  }
};

/**
 * Filter the documents box on the file type clicked.
 *
 * @memberof Saturne_Framework_Document
 *
 * @since   23.8.0
 * @version 23.8.0
 *
 * @return {void}
 */
window.saturne.document.onFileListType = function() {
  let $type = $(this);
  let $box  = $type.closest('.saturne-documents');

  $box.find('.saturne-documents__type').removeClass('saturne-documents__type--active').attr('aria-pressed', 'false');
  $type.addClass('saturne-documents__type--active').attr('aria-pressed', 'true');
  window.saturne.document.refreshFileList($box);
};

/**
 * Show every file of the documents box, or only the first ones again.
 *
 * @memberof Saturne_Framework_Document
 *
 * @since   23.8.0
 * @version 23.8.0
 *
 * @return {void}
 */
window.saturne.document.onFileListMore = function() {
  let $box = $(this).closest('.saturne-documents');

  $box.toggleClass('saturne-documents--expanded');
  window.saturne.document.refreshFileList($box);
};
