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
  $(document).on('change', '.saturne-file-upload', window.saturne.mediaBlock.onFileSelected);
  $(document).on('click', '.saturne-open-files-library', window.saturne.mediaBlock.onFilesLibraryOpen);
  $(document).on('click', '.saturne-file-delete', window.saturne.mediaBlock.onFileDelete);
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

  for (var i = 0; i < files.length; i++) {
    if (!files[i].type || files[i].type.indexOf('image/') !== 0) {
      var errorMsg = input.data('error-not-image') || files[i].name;
      $.jnotify(errorMsg, 'error', true);
      input.val('');
      return;
    }
  }

  // Convert FileList to Array before clearing the input — browsers invalidate
  // the FileList object when input.val('') is called, so async callbacks lose access
  var filesArray = Array.prototype.slice.call(files);

  // Reset input so the same file can be re-selected if needed
  input.val('');

  // Every photo (single or series) goes through the editor sequentially so each one is
  // resized/annotated like a single photo: validate one, the editor re-opens for the next.
  window.saturne.mediaBlock.openFilesSequentially(filesArray, 0, module, subdir, block);
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
  }, 0, function(deletedUrl) {
    // Delete callback: resolve the filename from the document.php URL and ask the server to remove it
    var delParams   = new URLSearchParams((deletedUrl || '').split('?')[1] || '');
    var delFilePath = decodeURIComponent(delParams.get('file') || '');
    var delFilename = delFilePath.split('/').pop() || null;
    if (delFilename) {
      window.saturne.mediaBlock.deletePhoto(delFilename, module, subdir, block);
    }
  });
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
      window.saturne.mediaBlock.refreshGallery(block, resp.responseText);
    }
  });
};

/**
 * Delete a photo from the server via AJAX and refresh the gallery section in place.
 *
 * @memberof Saturne_MediaBlock
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param   {string} filename Name of the photo file to delete
 * @param   {string} module   Module name
 * @param   {string} subdir   Sub-directory
 * @param   {jQuery} block    The .linked-medias block element
 * @returns {void}
 */
window.saturne.mediaBlock.deletePhoto = function(filename, module, subdir, block) {
  var token          = window.saturne.toolbox.getToken();
  var querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);
  var formData       = new FormData();

  formData.append('filename', filename);
  formData.append('module_name', module);
  formData.append('sub_dir', subdir);

  $.ajax({
    url         : document.URL + querySeparator + 'action=deletePhoto&token=' + token,
    type        : 'POST',
    data        : formData,
    processData : false,
    contentType : false,
    complete    : function(resp) {
      window.saturne.mediaBlock.refreshGallery(block, resp.responseText);
    }
  });
};

/**
 * Open each file in the photo editor sequentially.
 * After the user validates one photo the editor re-opens automatically for the next.
 *
 * @memberof Saturne_MediaBlock
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param   {FileList} files  Files to process
 * @param   {number}   index  Current index
 * @param   {string}   module Module name
 * @param   {string}   subdir Sub-directory
 * @param   {jQuery}   block  The .linked-medias block element
 * @returns {void}
 */
window.saturne.mediaBlock.openFilesSequentially = function(files, index, module, subdir, block) {
  if (index >= files.length) {
    return;
  }

  // Validate one: upload the edited photo then re-open the editor for the next file.
  var onValidate = function(blob) {
    window.saturne.mediaBlock.uploadBlob(blob, module, subdir, block);
    window.saturne.mediaBlock.openFilesSequentially(files, index + 1, module, subdir, block);
  };

  // Validate all (only when more than one photo remains): upload the current edited photo,
  // then resize + upload every remaining file without opening the editor again.
  var onValidateAll = null;
  if (files.length - index > 1) {
    onValidateAll = function(blob) {
      window.saturne.mediaBlock.uploadBlob(blob, module, subdir, block);
      window.saturne.mediaBlock.uploadRemainingResized(files, index + 1, module, subdir, block);
    };
  }

  window.saturne.photoEditor.openFile(files[index], onValidate, onValidateAll);
};

/**
 * Resize and upload the remaining files one after another, without the editor.
 * Used by the "validate all" action of the photo editor.
 *
 * @memberof Saturne_MediaBlock
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param   {Array}  files  Files to upload
 * @param   {number} index  Current index
 * @param   {string} module Module name
 * @param   {string} subdir Sub-directory
 * @param   {jQuery} block  The .linked-medias block element
 * @returns {void}
 */
window.saturne.mediaBlock.uploadRemainingResized = function(files, index, module, subdir, block) {
  if (index >= files.length) {
    return;
  }

  window.saturne.photoEditor.resizeFileToBlob(files[index], function(blob) {
    window.saturne.mediaBlock.uploadBlob(blob, module, subdir, block);
    window.saturne.mediaBlock.uploadRemainingResized(files, index + 1, module, subdir, block);
  });
};

/**
 * Upload a series of photo files directly, bypassing the editor.
 * All selected files are sent in a single request, then the gallery is refreshed.
 *
 * @memberof Saturne_MediaBlock
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param   {Array}  files  Files to upload
 * @param   {string} module Module name
 * @param   {string} subdir Sub-directory
 * @param   {jQuery} block  The .linked-medias block element
 * @returns {void}
 */
window.saturne.mediaBlock.uploadPhotosDirectly = function(files, module, subdir, block) {
  var token          = window.saturne.toolbox.getToken();
  var querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);
  var formData       = new FormData();

  for (var i = 0; i < files.length; i++) {
    formData.append('userfile[]', files[i], files[i].name);
  }
  formData.append('module_name', module);
  formData.append('sub_dir', subdir || '');
  formData.append('overwrite', '0');

  $.ajax({
    url         : document.URL + querySeparator + 'action=uploadPhoto&token=' + token,
    type        : 'POST',
    data        : formData,
    processData : false,
    contentType : false,
    complete    : function(resp) {
      window.saturne.mediaBlock.refreshGallery(block, resp.responseText);
    }
  });
};

/**
 * Refresh the gallery section of a media block from an AJAX response.
 *
 * @memberof Saturne_MediaBlock
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param   {jQuery} block        The .linked-medias block element
 * @param   {string} responseText HTML response containing the refreshed block
 * @returns {void}
 */
window.saturne.mediaBlock.refreshGallery = function(block, responseText) {
  var $doc      = $('<div>').html(responseText);
  var blockId   = block && block.attr('id');
  var $srcBlock = blockId ? $doc.find('#' + blockId) : $();
  if (!$srcBlock.length) {
    $srcBlock = $doc.find('.linked-medias').first();
  }
  var $gallery = $srcBlock.find('.saturne-media-gallery');
  if ($gallery.length && block && block.length) {
    block.find('.saturne-media-gallery').replaceWith($gallery);
  }
};

/**
 * Triggered when one or more documents are selected via the file button.
 * Uploads any file type directly (no editor) then refreshes the file list.
 * Extra POST data declared by the host (data-from-extra) is forwarded so the
 * server can resolve/create the target object before storing the file.
 *
 * @memberof Saturne_MediaBlock
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void}
 */
window.saturne.mediaBlock.onFileSelected = function() {
  var input   = $(this);
  var block   = input.closest('.linked-medias');
  var options = block.find('.fast-upload-options');
  var module  = options.data('from-type');
  var subdir  = options.data('from-subdir');
  var files   = input.prop('files');

  if (!files || !files.length) {
    return;
  }

  var formData = new FormData();
  for (var i = 0; i < files.length; i++) {
    formData.append('userfile[]', files[i], files[i].name);
  }
  formData.append('module_name', module);
  formData.append('sub_dir', subdir || '');

  // Forward extra data declared by the host (e.g. parent ids to resolve the target)
  window.saturne.mediaBlock.appendExtraData(formData, options.data('from-extra'));

  // Reset input so the same file can be re-selected if needed
  input.val('');

  window.saturne.mediaBlock.submitFileForm(formData, 'uploadFile', block);
};

/**
 * Append host-declared extra data (data-from-extra) to a FormData object.
 * Accepts both a jQuery-parsed object and a raw JSON string.
 *
 * @memberof Saturne_MediaBlock
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param   {FormData}      formData FormData to enrich
 * @param   {Object|string} extra    Extra data declared by the host
 * @returns {void}
 */
window.saturne.mediaBlock.appendExtraData = function(formData, extra) {
  if (typeof extra === 'string' && extra.length) {
    try {
      extra = JSON.parse(extra);
    } catch (e) {
      extra = null;
    }
  }

  if (!extra || typeof extra !== 'object') {
    return;
  }

  for (var key in extra) {
    if (Object.prototype.hasOwnProperty.call(extra, key)) {
      formData.append(key, extra[key]);
    }
  }
};

/**
 * Triggered when the user clicks the files count badge.
 * Opens the files modal associated with the block.
 *
 * @memberof Saturne_MediaBlock
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void}
 */
window.saturne.mediaBlock.onFilesLibraryOpen = function() {
  var modalId = $(this).data('modal-id');
  if (modalId) {
    $('#' + modalId).addClass('modal-active');
  }
};

/**
 * Triggered when the user deletes an uploaded document from the files modal.
 *
 * @memberof Saturne_MediaBlock
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void}
 */
window.saturne.mediaBlock.onFileDelete = function() {
  var btn      = $(this);
  var modal    = btn.closest('.saturne-files-modal');
  var module   = modal.data('module');
  var subdir   = modal.data('subdir');
  var block    = $('#' + modal.data('block-id'));
  var filename = btn.data('filename');

  var formData = new FormData();
  formData.append('filename', filename);
  formData.append('module_name', module);
  formData.append('sub_dir', subdir || '');

  window.saturne.mediaBlock.submitFileForm(formData, 'deleteFile', block);
};

/**
 * Submit a file upload/delete FormData via AJAX and refresh the file list section.
 *
 * @memberof Saturne_MediaBlock
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param   {FormData} formData FormData to send (files or filename + module/subdir)
 * @param   {string}   action   Server action name ('uploadFile' or 'deleteFile')
 * @param   {jQuery}   block    The .linked-medias file block element
 * @returns {void}
 */
window.saturne.mediaBlock.submitFileForm = function(formData, action, block) {
  var token          = window.saturne.toolbox.getToken();
  var querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);
  var blockId        = block && block.attr('id');
  var modalId        = blockId ? blockId.replace('master-media-row-container-file', 'files-modal') : '';

  $.ajax({
    url         : document.URL + querySeparator + 'action=' + action + '&token=' + token,
    type        : 'POST',
    data        : formData,
    processData : false,
    contentType : false,
    complete    : function(resp) {
      var doc = new DOMParser().parseFromString(resp.responseText, 'text/html');

      // Refresh the inline block (upload button + count badge)
      if (blockId) {
        var updatedBlock = doc.getElementById(blockId);
        if (updatedBlock && $('#' + blockId).length) {
          $('#' + blockId).replaceWith($(updatedBlock));
        }
      }

      // Refresh the modal content, keeping it open if it currently is
      if (modalId) {
        var updatedModal = doc.getElementById(modalId);
        var $modal       = $('#' + modalId);
        if (updatedModal && $modal.length) {
          var wasActive = $modal.hasClass('modal-active');
          $modal.replaceWith($(updatedModal));
          if (wasActive) {
            $('#' + modalId).addClass('modal-active');
          }
        }
      }
    }
  });
};
