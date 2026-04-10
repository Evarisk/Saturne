/* Copyright (C) 2024-2026 EVARISK <technique@evarisk.com>
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
 */

/**
 * \file    js/modules/mediaBlock.js
 * \ingroup saturne
 * \brief   JavaScript handler for saturne_render_media_block() upload blocks
 */

/**
 * Media block namespace
 *
 * @memberof Saturne_MediaBlock
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @type {Object}
 */
window.saturne.mediaBlock = {};

/**
 * Media block init
 *
 * @memberof Saturne_MediaBlock
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void}
 */
window.saturne.mediaBlock.init = function() {
  window.saturne.mediaBlock.event();
};

/**
 * Media block event bindings
 *
 * @memberof Saturne_MediaBlock
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void}
 */
window.saturne.mediaBlock.event = function() {
  $(document).on('change', '.saturne-photo-upload', window.saturne.mediaBlock.onPhotoSelected);
  $(document).on('click', '.saturne-media-gallery .open-media-editor-as-gallery', window.saturne.mediaBlock.onGalleryClick);
};

/**
 * Triggered when a new photo file is selected via the camera button.
 * Opens the photo editor so the user can annotate before uploading.
 *
 * @memberof Saturne_MediaBlock
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void}
 */
window.saturne.mediaBlock.onPhotoSelected = function() {
  var input  = $(this);
  var block  = input.closest('.linked-medias');
  var module = block.find('.fast-upload-options').data('from-type');
  var subdir = block.find('.fast-upload-options').data('from-subdir');
  var files  = input.prop('files');

  if (!files || !files.length) {
    return;
  }

  window.saturne.photoEditor.openFile(files[0], function(blob) {
    window.saturne.mediaBlock.uploadBlob(blob, module, subdir, block);
  });

  // Reset input so the same file can be re-selected if needed
  input.val('');
};

/**
 * Triggered when the user clicks the gallery thumbnail.
 * Opens the photo editor with the first image from the gallery.
 *
 * @memberof Saturne_MediaBlock
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void}
 */
window.saturne.mediaBlock.onGalleryClick = function() {
  var urls   = $(this).data('json');
  var block  = $(this).closest('.linked-medias');
  var module = block.find('.fast-upload-options').data('from-type');
  var subdir = block.find('.fast-upload-options').data('from-subdir');

  if (!urls || !urls.length) {
    return;
  }

  window.saturne.photoEditor.open(urls, function(blob) {
    var currentIndex     = window.saturne.photoEditor._currentIndex;
    var originalUrl      = urls[currentIndex] || '';
    // Extract the filename from the Dolibarr document.php URL (?file=subdir%2Fname.jpg)
    var urlParams        = new URLSearchParams(originalUrl.split('?')[1] || '');
    var filePath         = decodeURIComponent(urlParams.get('file') || '');
    var originalFilename = filePath.split('/').pop() || null;
    window.saturne.mediaBlock.uploadBlob(blob, module, subdir, block, originalFilename);
  }, 0);
};

/**
 * Upload a Blob to the server via AJAX and refresh the gallery section.
 * When `originalFilename` is provided the server will overwrite that file
 * instead of creating a new one.
 *
 * @memberof Saturne_MediaBlock
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param   {Blob}        blob             Image blob to upload
 * @param   {string}      module           Module name
 * @param   {string}      subdir           Sub-directory
 * @param   {jQuery}      block            The .linked-medias block element
 * @param   {string|null} originalFilename Filename to overwrite (null = new file)
 * @returns {void}
 */
window.saturne.mediaBlock.uploadBlob = function(blob, module, subdir, block, originalFilename) {
  var token          = window.saturne.toolbox.getToken();
  var querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);
  var filename       = originalFilename || ('photo_' + new Date().getTime() + '.jpg');
  var overwrite      = originalFilename ? '1' : '0';
  var file           = new File([blob], filename, { type: 'image/jpeg', lastModified: Date.now() });
  var formData       = new FormData();

  formData.append('userfile[]', file, filename);
  formData.append('module_name', module);
  formData.append('sub_dir', subdir);
  formData.append('overwrite', overwrite);

  $.ajax({
    url         : document.URL + querySeparator + 'action=uploadPhoto&token=' + token,
    type        : 'POST',
    data        : formData,
    processData : false,
    contentType : false,
    complete    : function(resp) {
      var updatedGallery = $(resp.responseText).find('.saturne-media-gallery');
      if (updatedGallery.length && block && block.length) {
        block.find('.saturne-media-gallery').replaceWith(updatedGallery);
      }
    }
  });
};
