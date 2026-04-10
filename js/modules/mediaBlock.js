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
  $(document).on('change', '.saturne-media-block-upload', window.saturne.mediaBlock.uploadPhoto);
};

/**
 * Upload selected photo files via AJAX and refresh the gallery
 *
 * @memberof Saturne_MediaBlock
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void}
 */
window.saturne.mediaBlock.uploadPhoto = function() {
  var input          = $(this);
  var block          = input.closest('.saturne-media-upload-block');
  var files          = input.prop('files');
  var token          = window.saturne.toolbox.getToken();
  var querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);
  var formData       = new FormData();

  $.each(files, function(index, file) {
    formData.append('userfile[]', file);
  });

  formData.append('module_name', block.data('module'));
  formData.append('sub_dir', block.data('subdir'));

  window.saturne.loader.display(block);

  $.ajax({
    url         : document.URL + querySeparator + 'action=uploadPhoto&token=' + token,
    type        : 'POST',
    data        : formData,
    processData : false,
    contentType : false,
    complete    : window.saturne.mediaBlock.onUploadComplete,
  });
};

/**
 * On upload complete: remove loader and refresh the gallery section from response
 *
 * @memberof Saturne_MediaBlock
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param   {jqXHR} resp jQuery XHR response
 * @returns {void}
 */
window.saturne.mediaBlock.onUploadComplete = function(resp) {
  var updatedGallery = $(resp.responseText).find('.saturne-media-gallery');
  if (updatedGallery.length) {
    $('.saturne-media-gallery').replaceWith(updatedGallery);
  }
  $('.saturne-media-upload-block').each(function() {
    window.saturne.loader.remove($(this));
  });
};
