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
  $(document).on('click', '.open-media-editor-linked', window.saturne.mediaBlock.onLinkedPhotoClick);
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

  // Send all files to the editor in batch mode. The editor will return an array of
  // modified File objects once the user clicks Validate All.
  window.saturne.photoEditor.openBatch(filesArray, function(modifiedFiles) {
    window.saturne.mediaBlock.uploadBatchFiles(filesArray, modifiedFiles, module, subdir, block);
  });
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
  var urls = $(this).data('json');

  if (!urls || !urls.length) {
    return;
  }

  window.saturne.mediaBlock.openEditor(urls, 0, $(this).closest('.linked-medias'));
};

/**
 * Resolve the filename carried by a Dolibarr file URL (document.php or viewimage.php)
 *
 * @memberof Saturne_MediaBlock
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param   {string}      url File URL holding a `file` query parameter
 * @returns {string|null}     Filename, or null when the URL carries none
 */
window.saturne.mediaBlock.filenameFromUrl = function(url) {
  return window.saturne.photoEditor._fileNameOf(url);
};

/**
 * Find the media block holding the medias of a record
 *
 * The banner photo of a record is rendered outside its media block, so a click on it has to
 * reach that block to know where the media can be written back.
 *
 * @memberof Saturne_MediaBlock
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param   {number|string} recordId Record owning the medias
 * @returns {jQuery}                 The block, empty when the page renders none
 */
window.saturne.mediaBlock.blockForRecord = function(recordId) {
  if (!recordId) {
    return $();
  }

  return $('.linked-medias').filter(function() {
    var block = $(this);

    return block.find('.fast-upload-options[data-object-id="' + recordId + '"]').length > 0 ||
        block.find('.modal-options[data-from-id="' + recordId + '"]').length > 0;
  }).first();
};

/**
 * Resolve where the medias of a block live and how they can be written back
 *
 * Two markups coexist: the media block carries a module and a folder, while the older media row
 * of a record carries the record itself. Saving takes a different route for each, and only the
 * second one knows which media the record displays.
 *
 * @memberof Saturne_MediaBlock
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param   {jQuery}      block The .linked-medias block holding the medias
 * @returns {Object|null}       Context of the block, null when nothing can be written to
 */
window.saturne.mediaBlock.mediaContext = function(block) {
  var fastUpload = block.find('.fast-upload-options');

  if (fastUpload.length) {
    var context = {
      mode  : 'module',
      module: fastUpload.data('from-type'),
      subdir: fastUpload.data('from-subdir')
    };

    // A block rendered for a record also says which of its medias that record displays
    if (fastUpload.data('object-id')) {
      context.objectId   = fastUpload.data('object-id');
      context.objectType = fastUpload.data('object-type');
      context.subtype    = fastUpload.data('object-subtype');
      context.favorite   = fastUpload.data('favorite') || '';
      context.photoClass = fastUpload.data('from-subtype') || '';
    }

    return context;
  }

  var modalOptions = block.find('.modal-options');

  if (!modalOptions.length || !modalOptions.data('from-id')) {
    return null;
  }

  return {
    mode      : 'object',
    objectId  : modalOptions.data('from-id'),
    objectType: modalOptions.data('from-type'),
    subtype   : modalOptions.data('from-subtype'),
    subdir    : modalOptions.data('from-subdir') || '',
    photoClass: modalOptions.data('photo-class'),
    // Read from the media marked as favorite: the hidden input of the older markup is not
    // always filled in by the host page
    favorite  : block.find('.media-gallery-favorite.favorite').find('.filename').val() || ''
  };
};

/**
 * Open the photo editor on a set of medias, starting on one of them
 *
 * Saving needs the host page to handle the media actions: without a block able to take a write
 * the editor opens read-only rather than offering buttons that would fail silently.
 *
 * @memberof Saturne_MediaBlock
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param   {Array}  urls       Media URLs of the set
 * @param   {number} startIndex Index the editor opens on
 * @param   {jQuery} block      The .linked-medias block owning the medias
 * @returns {void}
 */
window.saturne.mediaBlock.openEditor = function(urls, startIndex, block) {
  var context = window.saturne.mediaBlock.mediaContext(block);

  if (!context) {
    window.saturne.photoEditor.open(urls, null, startIndex, null);
    return;
  }

  var currentFileName = function() {
    return window.saturne.mediaBlock.filenameFromUrl(urls[window.saturne.photoEditor._currentIndex] || '');
  };

  var onSave   = null;
  var onDelete = null;
  var favorite = null;

  if (context.mode === 'module') {
    onSave = function(blob) {
      window.saturne.mediaBlock.uploadBlob(blob, context.module, context.subdir, block, currentFileName());
    };

    onDelete = function(deletedUrl) {
      var deletedFilename = window.saturne.mediaBlock.filenameFromUrl(deletedUrl);

      if (deletedFilename) {
        window.saturne.mediaBlock.deletePhoto(deletedFilename, context.module, context.subdir, block);
      }
    };
  } else {
    onSave = function(blob) {
      window.saturne.mediaBlock.saveObjectMedia(blob, currentFileName(), context);
    };
  }

  if (context.objectId) {
    favorite = {
      current : context.favorite,
      onSelect: function(url, filename) {
        window.saturne.mediaBlock.setObjectFavorite(filename, context, block);
      }
    };
  }

  window.saturne.photoEditor.open(urls, onSave, startIndex, onDelete, null, favorite);
};

/**
 * Write an edited media back over the file it came from, in the folder of its record
 *
 * @memberof Saturne_MediaBlock
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param   {Blob}   blob     Edited image
 * @param   {string} filename Name of the media being replaced
 * @param   {Object} context  Context returned by mediaContext()
 * @returns {void}
 */
window.saturne.mediaBlock.saveObjectMedia = function(blob, filename, context) {
  if (!filename) {
    return;
  }

  var token          = window.saturne.toolbox.getToken();
  var querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);
  var formData       = new FormData();

  formData.append('userfile[]', new File([blob], filename, { type: blob.type || 'image/jpeg', lastModified: Date.now() }), filename);
  formData.append('object_type', context.objectType);
  formData.append('object_id', context.objectId);
  formData.append('object_subdir', context.subdir);
  formData.append('file_name', filename);

  $.ajax({
    url        : document.URL + querySeparator + 'subaction=editObjectMedia&token=' + token,
    type       : 'POST',
    data       : formData,
    processData: false,
    contentType: false,
    success    : function(resp) {
      window.saturne.mediaBlock.refreshObjectMedias(resp, context);
    },
    error      : function() {
      $('.wpeo-loader').removeClass('wpeo-loader');
    }
  });
};

/**
 * Record which media the record displays
 *
 * @memberof Saturne_MediaBlock
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param   {string} filename Media to display
 * @param   {Object} context  Context returned by mediaContext()
 * @param   {jQuery} block    The .linked-medias block owning the medias
 * @returns {void}
 */
window.saturne.mediaBlock.setObjectFavorite = function(filename, context, block) {
  if (!filename) {
    return;
  }

  var token          = window.saturne.toolbox.getToken();
  var querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);

  block.find('.favorite-photo').val(filename);

  $.ajax({
    url        : document.URL + querySeparator + 'subaction=addToFavorite&token=' + token,
    type       : 'POST',
    data       : JSON.stringify({
      filename     : filename,
      objectSubtype: context.subtype,
      objectType   : context.objectType,
      objectSubdir : context.subdir,
      objectId     : context.objectId
    }),
    processData: false,
    contentType: 'application/json',
    success    : function(resp) {
      window.saturne.mediaBlock.refreshObjectMedias(resp, context);
    },
    error      : function() {
      $('.wpeo-loader').removeClass('wpeo-loader');
    }
  });
};

/**
 * Put back on the page the medias the server just rendered
 *
 * An edited media keeps its URL while its content changes, so the browser would serve the
 * previous one from its cache: the thumbnails are asked for again.
 *
 * @memberof Saturne_MediaBlock
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param   {string} resp    Page rendered by the server
 * @param   {Object} context Context returned by mediaContext()
 * @returns {void}
 */
window.saturne.mediaBlock.refreshObjectMedias = function(resp, context) {
  var rendered = $(resp);

  if (context.photoClass) {
    var list = $('.linked-medias.' + context.photoClass);

    if (list.length) {
      list.html(rendered.find('.linked-medias.' + context.photoClass).children());
    }
  }

  var banner = $('.floatleft.inline-block.valignmiddle.divphotoref');

  if (banner.length) {
    banner.replaceWith(rendered.find('.floatleft.inline-block.valignmiddle.divphotoref'));
  }

  $('.linked-medias .photo, .divphotoref .photo').each(function() {
    var image     = $(this);
    var attribute = image.attr('data-src') ? 'data-src' : 'src';
    var source    = image.attr(attribute);

    if (source) {
      image.attr(attribute, source.split('&edited=')[0] + '&edited=' + Date.now());
    }
  });

  $('.wpeo-loader').removeClass('wpeo-loader');
  window.saturne.modal.loadLazyImages();
};


/**
 * Triggered when a linked media thumbnail is clicked.
 * Opens the Saturne editor on the whole set rather than the native Dolibarr preview dialog.
 *
 * @memberof Saturne_MediaBlock
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param   {Object} event Click event
 * @returns {void}
 */
window.saturne.mediaBlock.onLinkedPhotoClick = function(event) {
  event.preventDefault();

  var link       = $(this);
  var galleryId  = link.data('gallery');
  var siblings   = galleryId ? $('.open-media-editor-linked[data-gallery="' + galleryId + '"]') : link;
  var urls       = [];
  var startIndex = 0;

  siblings.each(function() {
    var url      = $(this).data('url');
    var position = urls.indexOf(url);

    // A page can render the same media twice: list it once so the editor arrows do not
    // walk through duplicates
    if (position === -1) {
      position = urls.push(url) - 1;
    }

    if (this === link[0]) {
      startIndex = position;
    }
  });

  if (!urls.length) {
    return;
  }

  var block = link.closest('.linked-medias');

  // The banner photo is rendered outside the media row: the row of the same set is the one
  // holding the record this media can be written back to
  if (!block.length) {
    block = siblings.closest('.linked-medias').first();
  }

  // The media block of a record renders no anchor of its own: reach it through the record
  if (!block.length) {
    block = window.saturne.mediaBlock.blockForRecord(link.data('object-id'));
  }

  // A block showing its medias as a single gallery holds the whole list: a click on the banner
  // photo of that record then opens every one of them rather than that photo alone
  var gallery     = block.find('.open-media-editor-as-gallery');
  var galleryUrls = gallery.length ? gallery.data('json') : null;

  if (galleryUrls && galleryUrls.length) {
    var clicked  = window.saturne.mediaBlock.filenameFromUrl(link.data('url'));
    var position = 0;

    galleryUrls.forEach(function(url, index) {
      if (window.saturne.mediaBlock.filenameFromUrl(url) === clicked) {
        position = index;
      }
    });

    window.saturne.mediaBlock.openEditor(galleryUrls, position, block);
    return;
  }

  window.saturne.mediaBlock.openEditor(urls, startIndex, block);
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
  var file           = new File([blob], filename, { type: blob.type || 'image/jpeg', lastModified: Date.now() });
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
 * Upload an array of files in batch mode.
 * Untouched files (which were never loaded into the canvas) are resized before uploading.
 * Modified files (which were loaded and potentially edited) are uploaded directly.
 *
 * @memberof Saturne_MediaBlock
 *
 * @since   1.1.0
 * @version 1.0.0
 *
 * @param   {Array}  originalFiles Original File objects from the input
 * @param   {Array}  modifiedFiles File objects returned by the editor
 * @param   {string} module        Module name
 * @param   {string} subdir        Sub-directory
 * @param   {jQuery} block         The .linked-medias block element
 * @returns {void}
 */
window.saturne.mediaBlock.uploadBatchFiles = function(originalFiles, modifiedFiles, module, subdir, block) {
  var index = 0;
  
  var processNext = function() {
    if (index >= modifiedFiles.length) {
      return;
    }
    
    var uploadAndNext = function(blob) {
      window.saturne.mediaBlock.uploadBlob(blob, module, subdir, block);
      index++;
      processNext();
    };
    
    if (modifiedFiles[index] === originalFiles[index]) {
      // Untouched file, needs resizing
      window.saturne.photoEditor.resizeFileToBlob(originalFiles[index], function(blob) {
        uploadAndNext(blob);
      });
    } else {
      // Already modified/resized by the canvas
      uploadAndNext(modifiedFiles[index]);
    }
  };
  
  processNext();
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
