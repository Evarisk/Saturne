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
 * @version 1.3.0
 *
 * @return {void}
 */
window.saturne.document.autoDownloadTemplate = function() {
  let token          = window.saturne.toolbox.getToken();
  let url            = document.URL.replace(/#.*$/, '');
  let querySeparator = window.saturne.toolbox.getQuerySeparator(url);
  let element        = $(this).closest('.file-generation');
  let type           = element.find('.template-type').attr('value');
  let filename       = element.find('.template-name').attr('value');

  $.ajax({
    url: url + querySeparator + 'action=download_template&filename=' + filename + '&type=' + type + '&token=' + token,
    type: 'POST',
    success: function() {
      let path = element.find('.template-path').attr('value');
      window.saturne.signature.download(path + filename, filename);
      $.ajax({
        url: document.URL + querySeparator + 'action=remove_file&filename=' + filename + '&token=' + token,
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
