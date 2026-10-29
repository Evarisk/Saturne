/* jshint ignore:start */
/* Copyright (C) 2022-2024 EVARISK <technique@evarisk.com>
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
 * \file    js/modules/mediaGallery.js
 * \ingroup mediaGallery
 * \brief   JavaScript file mediaGallery for module Saturne.
 */

/**
 * Initialise l'objet "mediaGallery" ainsi que la méthode "init" obligatoire pour la bibliothèque Saturne
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.saturne.mediaGallery = {};

/**
 * La méthode appelée automatiquement par la bibliothèque Saturne
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.mediaGallery.init = function() {
	window.saturne.mediaGallery.event();
};

/**
 * La méthode contenant tous les événements pour la bibliothèque de médias
 *
 * @since   1.0.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.saturne.mediaGallery.event = function() {
	// Photos
  $( document ).on( 'click', '.clickable-photo', window.saturne.mediaGallery.selectPhoto );
  $( document ).on( 'click', '.open-media-editor', window.saturne.mediaGallery.openPhotoEditor );
  $( document ).on( 'click', '.save-photo', window.saturne.mediaGallery.savePhoto );
  $(document).on( 'click', '.delete-photo', window.saturne.mediaGallery.deletePhoto);
  // The gallery add button carries its own id, the class selector only matches the older markup
  $( document ).on( 'change', '.flat.minwidth400.maxwidth200onsmartphone, #add_media_to_gallery', window.saturne.mediaGallery.sendPhoto );
  $( document ).on( 'click', '.clicked-photo-preview', window.saturne.mediaGallery.previewPhoto );
  $( document ).on( 'input', '#search_in_gallery', window.saturne.mediaGallery.handleSearch );
  $( document ).on( 'click', '.media-gallery-unlink', window.saturne.mediaGallery.unlinkFile );
  $( document ).on( 'click', '.media-gallery-favorite', window.saturne.mediaGallery.addToFavorite );
  $( document ).on( 'change', '.fast-upload', window.saturne.mediaGallery.fastUpload );
  $( document ).on( 'click', '.select-page', window.saturne.mediaGallery.selectPage );
  $( document ).on( 'click', '.toggle-today-medias', window.saturne.mediaGallery.toggleTodayMedias );
  $( document ).on( 'click', '.toggle-unlinked-medias', window.saturne.mediaGallery.toggleUnlinkedMedias );
  $(document).on('click', '.regenerate-thumbs', window.saturne.mediaGallery.regenerateThumbs);
  $(document).on('click', '.sync-media-library', window.saturne.mediaGallery.syncLibrary);
  $(document).on( 'click', '.media-gallery-ai', window.saturne.mediaGallery.readFileAI);
  // Files dropped on the open gallery, or on the media library page, join the library. Bound twice:
  // delegated to the open gallery, they run before the page wide handlers of the documents upload
  // zone and keep them out of a drop meant for the gallery; bound to the document, they serve the page
  let dropEvents = {
    dragenter: window.saturne.mediaGallery.onDragEnter,
    dragover : window.saturne.mediaGallery.onDragOver,
    dragleave: window.saturne.mediaGallery.onDragLeave,
    drop     : window.saturne.mediaGallery.onDrop
  };
  $(document).on(dropEvents, '#media_gallery.modal-active');
  $(document).on(dropEvents);
}

/**
 * Number of elements the dragged files are over
 *
 * Each element the pointer crosses fires its own enter and leave: the cover stays until the last
 * one is left.
 *
 * @type {number}
 */
window.saturne.mediaGallery.dragDepth = 0;

/**
 * Cover of the place dropped files go to: the open gallery, else the media library page
 *
 * Empty on any other page, whose drops are left to their own handlers.
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @return {jQuery} Cover shown while files are dragged
 */
window.saturne.mediaGallery.getDropCover = function() {
  let mediaGallery = $('#media_gallery.modal-active');

  return mediaGallery.length ? mediaGallery.find('.media-gallery-dropzone') : $('.media-library-page-dropzone');
};

/**
 * Show the cover when dragged files come over a place taking them
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @param  {Object} event Drag event
 * @return {void}
 */
window.saturne.mediaGallery.onDragEnter = function( event ) {
  let cover = window.saturne.mediaGallery.getDropCover();
  if (!cover.length || !window.saturne.document.isFileDrag(event)) {
    return;
  }
  event.preventDefault();
  event.stopPropagation();

  window.saturne.mediaGallery.dragDepth++;
  cover.addClass('dropzone-visible');
};

/**
 * Let the files be dropped anywhere on that place, so the browser never opens them instead
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @param  {Object} event Drag event
 * @return {void}
 */
window.saturne.mediaGallery.onDragOver = function( event ) {
  if (!window.saturne.mediaGallery.getDropCover().length || !window.saturne.document.isFileDrag(event)) {
    return;
  }
  event.preventDefault();
  event.stopPropagation();

  event.originalEvent.dataTransfer.dropEffect = 'copy';
};

/**
 * Hide the cover once the dragged files left
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @param  {Object} event Drag event
 * @return {void}
 */
window.saturne.mediaGallery.onDragLeave = function( event ) {
  let cover = window.saturne.mediaGallery.getDropCover();
  if (!cover.length || !window.saturne.document.isFileDrag(event)) {
    return;
  }
  event.stopPropagation();

  window.saturne.mediaGallery.dragDepth--;
  if (window.saturne.mediaGallery.dragDepth <= 0) {
    window.saturne.mediaGallery.dragDepth = 0;
    cover.removeClass('dropzone-visible');
  }
};

/**
 * Add the dropped images to the library
 *
 * The library only holds images, as the add button only picks them: the other files are left out
 * and named to the user. On the gallery the images are handed to that button, so the upload runs
 * exactly as for files picked by hand; the media library page sends them itself.
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @param  {Object} event Drop event
 * @return {void}
 */
window.saturne.mediaGallery.onDrop = function( event ) {
  let cover = window.saturne.mediaGallery.getDropCover();
  if (!cover.length || !window.saturne.document.isFileDrag(event)) {
    return;
  }
  event.preventDefault();
  event.stopPropagation();

  let images  = new window.DataTransfer();
  let ignored = [];

  window.saturne.mediaGallery.dragDepth = 0;
  cover.removeClass('dropzone-visible');

  $.each(event.originalEvent.dataTransfer.files, function( index, file ) {
    if (file.type.indexOf('image/') === 0) {
      images.items.add(file);
    } else {
      ignored.push($('<span>').text(file.name).html());
    }
  });

  if (ignored.length && typeof $.jnotify === 'function') {
    $.jnotify(cover.data('label-ignored').replace('%s', ignored.join(', ')), 'warning', true);
  }

  if (!images.files.length) {
    return;
  }

  if (cover.closest('#media_gallery').length) {
    let fileInput   = $('#add_media_to_gallery')[0];
    fileInput.files = images.files;
    $(fileInput).trigger('change');
  } else {
    window.saturne.mediaGallery.sendToLibraryPage(images.files, cover);
  }
};

/**
 * Send images dropped on the media library page, then list the pending medias again
 *
 * The page lists the library itself: the gallery is not opened for the upload, the list shows the
 * new medias among the pending ones.
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @param  {FileList} files Images to add
 * @param  {jQuery}   cover Cover of the page, carrying the messages
 * @return {void}
 */
window.saturne.mediaGallery.sendToLibraryPage = function( files, cover ) {
  let pagePane         = $('.saturne-media-page-pane');
  let token            = window.saturne.toolbox.getToken();
  let baseUrl          = document.URL.split('#')[0];
  let querySeparator   = window.saturne.toolbox.getQuerySeparator(baseUrl);
  let requestCompleted = 0;
  let added            = 0;
  let errors           = [];

  window.saturne.loader.display(pagePane);

  $.each(files, function( index, file ) {
    let formdata = new FormData();
    formdata.append('userfile[]', file);

    $.ajax({
      url        : baseUrl + querySeparator + 'subaction=uploadPhoto&token=' + token,
      type       : 'POST',
      data       : formdata,
      processData: false,
      contentType: false,
      success    : function( resp ) {
        let error = $(resp).find('.error-medias');
        if (error.length) {
          errors.push(JSON.parse(error.val()).message);
        } else {
          added++;
        }
      },
      error      : function() {
        errors.push(cover.data('label-error'));
      },
      complete   : function() {
        requestCompleted++;
        if (requestCompleted < files.length) {
          return;
        }

        if (typeof $.jnotify === 'function') {
          if (added) {
            $.jnotify(cover.data('label-done').replace('%s', added));
          }
          if (errors.length) {
            $.jnotify(errors.join('<br>'), 'error', true);
          }
        }

        // The gallery was written with the page: it reads the library again when next opened
        window.saturne.mediaGallery.libraryChanged = true;
        window.saturne.mediaPending.load(pagePane);
      }
    });
  });
};

/**
 * Select photo
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.mediaGallery.selectPhoto = function( event ) {
    if (!$(event.target).closest('.photo-selector').length) {
        return;
    }
    let photoID = $(this).attr('value');
	let parent = $(this).closest('.modal-content')

	if ($(this).hasClass('clicked-photo')) {
		$(this).attr('style', 'none !important')
		$(this).removeClass('clicked-photo')
		if ($('.clicked-photo').length === 0) {
      $(this).closest('.modal-container').find('.save-photo').addClass('button-disable');
      $(this).closest('.modal-container').find('.delete-photo').addClass('button-disable');

    }
	} else {
		parent.closest('.modal-container').find('.save-photo').removeClass('button-disable');
    parent.closest('.modal-container').find('.delete-photo').removeClass('button-disable');
    parent.find('.clickable-photo'+photoID).addClass('clicked-photo');
	}
};

/**
 * Open the photo editor on the media that was clicked in the gallery
 *
 * The whole gallery is handed over so the editor arrows walk through it, and the editor
 * starts on the media that was clicked.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {Object} event Click event
 * @return {void}
 */
window.saturne.mediaGallery.openPhotoEditor = function( event ) {
  event.preventDefault();
  event.stopPropagation();

  var image      = $(this);
  var images     = image.closest('.modal-container').find('.open-media-editor[data-fullsrc]');
  var urls       = [];
  var startIndex = 0;

  images.each(function( index ) {
    urls.push($(this).data('fullsrc'));
    if (this === image[0]) {
      startIndex = index;
    }
  });

  if (!urls.length) {
    return;
  }

  window.saturne.photoEditor.open(urls, function( blob ) {
    var edited = images.eq(window.saturne.photoEditor._currentIndex);
    window.saturne.mediaGallery.replacePhoto(blob, edited.data('filename'));
  }, startIndex, null);
};

/**
 * Send an edited media back under its own name so it replaces the original
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {Blob}   blob     Edited image
 * @param  {string} filename Name of the media being replaced
 * @return {void}
 */
window.saturne.mediaGallery.replacePhoto = function( blob, filename ) {
  if (!filename) {
    return;
  }

  var mediaGallery   = $('#media_gallery');
  var token          = window.saturne.toolbox.getToken();
  var querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);
  var formData       = new FormData();

  formData.append('userfile[]', new File([blob], filename, { type: blob.type || 'image/jpeg', lastModified: Date.now() }), filename);
  formData.append('overwritemedia', '1');

  window.saturne.loader.display(mediaGallery.find('.ecm-photo-list-content'));

  $.ajax({
    url        : document.URL + querySeparator + 'subaction=uploadPhoto&token=' + token,
    type       : 'POST',
    data       : formData,
    processData: false,
    contentType: false,
    success    : function( resp ) {
      mediaGallery.html($(resp).find('#media_gallery').children()).promise().done(function() {
        // The media keeps its URL, only its content changed: the cache would serve the old one
        mediaGallery.find('.open-media-editor').each(function() {
          var img = $(this);
          img.attr('data-src', img.attr('data-src') + '&edited=' + Date.now());
        });
        window.saturne.modal.loadLazyImages();
      });
    },
    error      : function() {
      $('.wpeo-loader').removeClass('wpeo-loader');
    }
  });
};

/**
 * Action save photo to an object
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.mediaGallery.savePhoto = function( event ) {

	let mediaGallery      = $('#media_gallery')
	let mediaGalleryModal = $(this).closest('.modal-container')
	let filesLinked       = mediaGalleryModal.find('.clicked-photo')

	// Opened from a media block: the medias are linked to its folder, the block refreshes itself
	let fromBlock = mediaGallery.attr('data-from-block')
	if (fromBlock) {
		let blockFilenames = []
		filesLinked.each(function() {
			blockFilenames.push($(this).find('.filename').val())
		})
		if (!blockFilenames.length) {
			return
		}
		window.saturne.mediaBlock.linkLibraryMedias(blockFilenames, $('#' + fromBlock))
		mediaGallery.removeClass('modal-active')
		filesLinked.removeClass('clicked-photo').attr('style', '')
		mediaGalleryModal.find('.save-photo, .delete-photo').addClass('button-disable')
		return
	}

	let filenames = []
	filesLinked.each(function(  ) {
		filenames.push($( this ).find('.filename').val())
	});
	window.saturne.mediaGallery.linkMediasToRecord(filenames, $(this))
};

/**
 * Link library medias to the record that opened the gallery, then refresh what the page shows of it
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @param  {string[]} filenames Names of the medias in the library
 * @param  {jQuery}   button    Button clicked, showing the loader meanwhile
 * @return {void}
 */
window.saturne.mediaGallery.linkMediasToRecord = function( filenames, button ) {
	let mediaGallery = $('#media_gallery')
	let token        = window.saturne.toolbox.getToken();

	let objectId         = mediaGallery.attr('data-from-id');
	let objectType       = mediaGallery.attr('data-from-type')
	let objectSubtype    = mediaGallery.attr('data-from-subtype')
  let objectSubdir     = mediaGallery.attr('data-from-subdir')
  let objectPhotoClass = mediaGallery.attr('data-photo-class')

	let filenamesList = ''
	filenames.forEach(function( filename ) {
		filenamesList += filename + 'vVv'
	});
	window.saturne.loader.display(button);
  if (typeof objectPhotoClass != 'undefined' && objectPhotoClass.length > 0) {
    if ($('.linked-medias.'+objectPhotoClass).length > 0) {
      window.saturne.loader.display($('.linked-medias.'+objectPhotoClass));
    }
  } else {
    window.saturne.loader.display($('.linked-medias.'+objectSubtype));
  }
	let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL)

	$.ajax({
		url: document.URL + querySeparator + "subaction=addFiles&token=" + token,
		type: "POST",
		data: JSON.stringify({
			filenames: filenamesList,
			objectId: objectId,
			objectType: objectType,
			objectSubdir: objectSubdir,
			objectSubtype: objectSubtype
		}),
		processData: false,
		contentType: 'application/json',
		success: function ( resp ) {
			$('.wpeo-loader').removeClass('wpeo-loader')
			mediaGallery.removeClass('modal-active')
      if (typeof objectPhotoClass != 'undefined' && objectPhotoClass.length > 0) {
        $('.photo.'+objectPhotoClass).replaceWith($(resp).find('.photo.'+objectPhotoClass).first())
        $('.linked-medias.'+objectPhotoClass).replaceWith($(resp).find('.linked-medias.'+objectPhotoClass))
        // The photo opening the gallery shows the favorite as well (a Digirisk tree, a list): it holds
        // the class itself, without the photo one, so the line above leaves it as it was
        let photoContainer = $(resp).find('.photo-container.'+objectPhotoClass).first()
        if (photoContainer.length > 0) {
          $('.photo-container.'+objectPhotoClass).replaceWith(photoContainer)
        }
      } else {

        $('.linked-medias.'+objectSubtype).html($(resp).find('.linked-medias.'+objectSubtype).children())
      }
      if ($('.floatleft.inline-block.valignmiddle.divphotoref').length > 0) {
        $('.floatleft.inline-block.valignmiddle.divphotoref').replaceWith($(resp).find('.floatleft.inline-block.valignmiddle.divphotoref'))
      }
      if ($('.question__list-medias').length > 0) {
        $('.question__list-medias').replaceWith($(resp).find('.question__list-medias'));
      }
			//refresh media gallery & unselect selected medias
			mediaGallery.html($(resp).find('#media_gallery').children())
      window.saturne.modal.loadLazyImages();
		},
	});
};

/**
 * Action delete photo from media gallery
 *
 * @since   1.3.0
 * @version 1.5.0
 *
 * @return {void}
 */
window.saturne.mediaGallery.deletePhoto = function() {
  let mediaGalleryModal = $(this).closest('.modal-container');
  let filesLinked       = mediaGalleryModal.find('.clicked-photo');
  let fileNames = '';
  if (filesLinked.length > 0) {
    filesLinked.each(function() {
      fileNames += $(this).find('.filename').val() + 'vVv'
    });
  }
  window.saturne.loader.display($(this));

  $('.card__confirmation').removeAttr('style');
  $('.card__confirmation .confirmation-title .filesLinked').text(filesLinked.length);
  $(document).on('click', '.confirmation-close', function() {
    window.saturne.mediaGallery.closeConfirmation(filesLinked, mediaGalleryModal);
  });
  $(document).on('click', '.confirmation-delete', function() {
    window.saturne.mediaGallery.deleteFilesRequest(fileNames);
  });
};

/**
 * Action to remove the view of the confirmation box
 *
 * @since   1.3.0
 * @version 1.5.0
 *
 * @param {string} filesLinked       Selected Name of linked files
 * @param {string} mediaGalleryModal Media gallery modal element
 *
 * @return {void}
 */

window.saturne.mediaGallery.closeConfirmation = function(filesLinked, mediaGalleryModal) {
  $('.wpeo-loader').removeClass('wpeo-loader')
  $('.card__confirmation').attr('style', 'display:none;')
  if (filesLinked.length > 0) {
    filesLinked.each(function() {
      filesLinked.removeClass('clicked-photo');
    });
  }
  mediaGalleryModal.find('.save-photo').addClass('button-disable');
  mediaGalleryModal.find('.delete-photo').addClass('button-disable');
}

/**
 * Action to execute delete files request
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @param {string} fileNames Name of linked files
 *
 * @return {void}
 */
window.saturne.mediaGallery.deleteFilesRequest = function(fileNames) {
  let token          = window.saturne.toolbox.getToken();
  let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);

  $.ajax({
    url: document.URL + querySeparator + 'subaction=delete_files&token=' + token,
    type: 'POST',
    processData: false,
    contentType: 'application/json',
    data: JSON.stringify({
      filenames: fileNames
    }),
    success: function(resp) {
      $('#media_gallery .modal-container').replaceWith($(resp).find('#media_gallery .modal-container'));
      window.saturne.modal.loadLazyImages();
    },
    error: function() {}
  });
}

/**
 * Action handle search in medias
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.mediaGallery.handleSearch = function( event ) {
	let searchQuery = $('#search_in_gallery').val()
	let photos = $('.center.clickable-photo')

	photos.each(function(  ) {
		$( this ).text().trim().match(searchQuery) ? $(this).show() : $(this).hide()
	});
};

/**
 * Action send photo
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.mediaGallery.sendPhoto = function( event ) {

	event.preventDefault()

	let mediaGallery  = $('#media_gallery')
	let files         = $(this).prop("files")

	let totalCount = files.length
	let requestCompleted = 0
	let progress   = 0

	let objectId         = mediaGallery.attr('data-from-id')
	let objectType       = mediaGallery.attr('data-from-type')
	let objectSubtype    = mediaGallery.attr('data-from-subtype')
	let objectSubdir     = mediaGallery.attr('data-from-subdir')

	let token = window.saturne.toolbox.getToken();

	$('#progressBar').width(0)
	$('#progressBarContainer').attr('style', 'display:block')

	window.saturne.loader.display($('#progressBarContainer'));

	let baseUrl = document.URL.split('#')[0];
	let querySeparator = window.saturne.toolbox.getQuerySeparator(baseUrl);
	let textToShow = '';

	$.each(files, function(index, file) {
		let formdata = new FormData();
		formdata.append("userfile[]", file);
		$.ajax({
			url: baseUrl + querySeparator + "subaction=uploadPhoto&token=" + token,
			type: "POST",
			data: formdata,
			processData: false,
			contentType: false,
			success: function (resp) {
				requestCompleted++
				progress += (1 / totalCount) * 100
				$('#progressBar').animate({
					width: progress + '%'
				}, 1);
				if ($(resp).find('.error-medias').length) {
					let errorMessage = $(resp).find('.error-medias').val()
					let decodedErrorMessage = JSON.parse(errorMessage)
					textToShow += decodedErrorMessage.message + '<br>'
				}

				if (requestCompleted === totalCount) {
					$('.wpeo-loader').removeClass('wpeo-loader');
					$('#progressBarContainer').fadeOut(800)
					$('#progressBarContainer').find('.loader-spin').remove();
					window.saturne.loader.display(mediaGallery.find('.ecm-photo-list-content'));
					setTimeout(() => {
						let newChildren = $(resp).find('#media_gallery').children();
						if (newChildren.length) {
							mediaGallery.html(newChildren).promise().done( () => {
								if (totalCount == 1) {
									$('#media_gallery').find('.save-photo').removeClass('button-disable');
									$('#media_gallery').find('.delete-photo').removeClass('button-disable');
									$('#media_gallery').find('.clickable-photo0').addClass('clicked-photo');
								}
								if ($(resp).find('.error-medias').length) {
									$('.messageErrorSendPhoto').find('.notice-subtitle').html(textToShow);
									$('.messageErrorSendPhoto').removeClass('hidden');
								} else {
									let successMessage = totalCount > 1 ? totalCount + " médias ajoutés avec succès" : "1 média ajouté avec succès";
									$('.messageSuccessSendPhoto').find('.notice-title').html(successMessage);
									$('.messageSuccessSendPhoto').removeClass('hidden');
								}
								window.saturne.mediaGallery.currentOffset = 1;
								window.saturne.mediaGallery.hasMore = true;
								window.saturne.mediaGallery.isLoading = false;
								setTimeout(window.saturne.mediaGallery.checkFill, 200);
								mediaGallery.attr('data-from-id', objectId);
								mediaGallery.attr('data-from-type', objectType);
								mediaGallery.attr('data-from-subtype', objectSubtype);
								mediaGallery.attr('data-from-subdir', objectSubdir);
								mediaGallery.find('.wpeo-button').attr('value', objectId);
								window.saturne.modal.loadLazyImages();
								$('.wpeo-loader').removeClass('wpeo-loader');
								$('.loader-spin').remove();

								// The media library page lists the pending medias outside the gallery: the new ones are among them
								let pagePane = $('.saturne-media-page-pane');
								if (pagePane.length && window.saturne.mediaPending) {
									window.saturne.mediaPending.load(pagePane);
								}
							});
						} else {
							$('.wpeo-loader').removeClass('wpeo-loader');
							$('.loader-spin').remove();
						}
					}, 800);
				}
			},
			error: function() {
				requestCompleted++;
				textToShow += "Erreur lors de l'envoi d'un fichier.<br>";
				if (requestCompleted === totalCount) {
					$('.wpeo-loader').removeClass('wpeo-loader');
					$('#progressBarContainer').fadeOut(800);
					$('#progressBarContainer').find('.loader-spin').remove();
					$('.messageErrorSendPhoto').find('.notice-subtitle').html(textToShow);
					$('.messageErrorSendPhoto').removeClass('hidden');
					$('.ecm-photo-list-content').removeClass('wpeo-loader').find('.loader-spin').remove();
				}
			}
		});
	})
};

/**
 * Action preview photo
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.mediaGallery.previewPhoto = function( event ) {
	var checkExist = setInterval(function() {
		if ($('.ui-dialog').length) {
			clearInterval(checkExist);
			$( document ).find('.ui-dialog').addClass('preview-photo');
		}
	}, 100);
};

/**
 * Action unlink photo
 *
 * @since   1.0.0
 * @version 1.5.0
 *
 * @return {void}
 */
window.saturne.mediaGallery.unlinkFile = function( event ) {

	event.preventDefault()

	let token = window.saturne.toolbox.getToken();


  let modal = $(this).closest('.modal-active')
  let inModal = $(this).closest('.modal-active').length > 0

  let mediaInfos = null;
  if (inModal) {
    mediaInfos = modal.find('.modal-options')
  } else {
    mediaInfos = $(this).closest('.linked-medias').find('.modal-options')
  }

  let objectSubtype    = mediaInfos.attr('data-from-subtype')
	let objectType       = mediaInfos.attr('data-from-type')
	let objectSubdir     = mediaInfos.attr('data-from-subdir')
  let objectId         = mediaInfos.attr('data-from-id')
  let objectPhotoClass = mediaInfos.attr('data-photo-class')

	let mediaContainer   = $(this).closest('.media-container')
	let filepath         = mediaContainer.find('.file-path').val()
	let filename         = mediaContainer.find('.file-name').val()
  let previousFavorite = $(this).closest('.linked-medias').find('.media-gallery-favorite.favorite').closest('.media-container').find('.file-name').val()

	window.saturne.loader.display(mediaContainer);

	let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL)

  $('.card__confirmation').css('display', 'flex');
  $(document).on('click', '.confirmation-close', function() {
    $(document).off('click', '.confirmation-delete');
    $(document).off('click', '.confirmation-close');
    $('.wpeo-loader').removeClass('wpeo-loader');
    $('.card__confirmation').css('display', 'none');
  });
  $(document).on('click', '.confirmation-delete', function() {
    $(document).off('click', '.confirmation-delete');
    $(document).off('click', '.confirmation-close');

    window.saturne.loader.display($(this));

    $.ajax({
      url: document.URL + querySeparator + "subaction=unlinkFile&token=" + token,
      type: "POST",
      data: JSON.stringify({
        filepath: filepath,
        filename: filename,
        objectSubtype: objectSubtype,
        objectType: objectType,
        objectSubdir: objectSubdir,
        objectId: objectId
      }),
      processData: false,
      contentType: 'application/json',
      success: function ( resp ) {
        $('.card__confirmation').css('display', 'none');
        $('#media_gallery .modal-container').replaceWith($(resp).find('#media_gallery .modal-container'));
        if (previousFavorite == filename) {
          if (typeof objectPhotoClass != 'undefined' && objectPhotoClass.length > 0) {
            $('.photo.'+objectPhotoClass).replaceWith($(resp).find('.photo.'+objectPhotoClass).first())
          }
          if ($('.floatleft.inline-block.valignmiddle.divphotoref').length > 0) {
            $('.floatleft.inline-block.valignmiddle.divphotoref').replaceWith($(resp).find('.floatleft.inline-block.valignmiddle.divphotoref'))
          }
        }
        if (typeof objectPhotoClass != 'undefined' && objectPhotoClass.length > 0) {
          $('.linked-medias.' + objectPhotoClass).replaceWith($(resp).find('.linked-medias.' + objectPhotoClass))
        } else if ($('.floatleft.inline-block.valignmiddle.divphotoref').length > 0) {
          $('.linked-medias.'+objectSubtype).html($(resp).find('.linked-medias.'+objectSubtype).children())
        } else if ($('.linked-medias.' + objectSubtype + ' .linked-medias-list').length > 0) {
          var $linkedMediasList = $('.linked-medias.' + objectSubtype + ' .linked-medias-list');
          $linkedMediasList.fadeOut(300, function() {
            var $newContent = $(resp).find('.linked-medias.' + objectSubtype + ' .linked-medias-list');
            $(this).replaceWith($newContent.hide());
            $newContent.fadeIn(300);
          });
        } else {
          var $linkedMediasList = $('.linked-medias.linked-medias-list.' + objectSubtype);
          $linkedMediasList.fadeOut(300, function() {
            var $newContent = $(resp).find('.linked-medias.linked-medias-list.' + objectSubtype);
            $(this).replaceWith($newContent.hide());
            $newContent.fadeIn(300);
          });
        }

        $('.wpeo-loader').removeClass('wpeo-loader')
        window.saturne.modal.loadLazyImages();
      }
    });
  });
};

/**
 * Action add photo to favorite
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.mediaGallery.addToFavorite = function( event ) {
	event.preventDefault()
	let filename = $(this).closest('.media-gallery-favorite').find('.filename').attr('value')

	let token = window.saturne.toolbox.getToken();

  let modal = $(this).closest('.modal-active')
  let inModal = $(this).closest('.modal-active').length > 0

	//change star button style
	let previousFavorite = $(this).closest('.linked-medias').find('.fas.fa-star')
	let newFavorite      = $(this).find('.far.fa-star')

  let mediaInfos = null;
  if (inModal) {
    mediaInfos = modal.find('.modal-options')
  } else {
    mediaInfos = $(this).closest('.linked-medias').find('.modal-options')
  }

	let objectSubtype    = mediaInfos.attr('data-from-subtype')
	let objectType       = mediaInfos.attr('data-from-type')
	let objectSubdir     = mediaInfos.attr('data-from-subdir')
  let objectId         = mediaInfos.attr('data-from-id')
  let objectPhotoClass = mediaInfos.attr('data-photo-class')

	let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL)

	previousFavorite.removeClass('fas')
	previousFavorite.addClass('far')
	previousFavorite.closest('.media-gallery-favorite').removeClass('favorite')
	newFavorite.addClass('fas')
	newFavorite.removeClass('far')
	newFavorite.closest('.media-gallery-favorite').addClass('favorite')

	if (filename.length > 0) {
		$(this).closest('.linked-medias').find('.favorite-photo').val(filename)
	}

  if (typeof objectPhotoClass != 'undefined' && objectPhotoClass.length > 0) {
    window.saturne.loader.display($('.photo.'+objectPhotoClass));
  }

    $.ajax({
		url: document.URL + querySeparator + "subaction=addToFavorite&token=" + token,
		type: "POST",
		data: JSON.stringify({
			filename: filename,
			objectSubtype: objectSubtype,
			objectType: objectType,
			objectSubdir: objectSubdir,
			objectId: objectId,
		}),
		processData: false,
    contentType: 'application/json',
		success: function ( resp ) {
      if (previousFavorite != filename) {
        if (typeof objectPhotoClass != 'undefined' && objectPhotoClass.length > 0) {
          $('.photo.'+objectPhotoClass).replaceWith($(resp).find('.photo.'+objectPhotoClass).first())
        }
      }
      if ($('.floatleft.inline-block.valignmiddle.divphotoref').length > 0) {
        $('.floatleft.inline-block.valignmiddle.divphotoref').replaceWith($(resp).find('.floatleft.inline-block.valignmiddle.divphotoref'))
      }
      window.saturne.modal.loadLazyImages();
		}
	});
};

/**
 * Action fast upload
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.mediaGallery.fastUpload = function( typeFrom ) {
	let files         = $(this).prop("files");
	let token         = window.saturne.toolbox.getToken();
	let mediaGallery  = $('#media_gallery')

	let formdata = new FormData();

	let objectId         = $(this).closest('.linked-medias').find('.modal-options').attr('data-from-id')
	let objectType       = $(this).closest('.linked-medias').find('.modal-options').attr('data-from-type')
	let objectSubtype    = $(this).closest('.linked-medias').find('.modal-options').attr('data-from-subtype')
	let objectSubdir     = $(this).closest('.linked-medias').find('.modal-options').attr('data-from-subdir')
  let objectPhotoClass = $(this).closest('.linked-medias').find('.modal-options').attr('data-photo-class')

  if (typeof objectPhotoClass != 'undefined' && objectPhotoClass.length > 0) {
    window.saturne.loader.display($('.linked-medias.'+objectPhotoClass));
  } else {
    window.saturne.loader.display($('.linked-medias.'+objectSubtype));
  }

	let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL)

	let filenames = ''

	let totalCount = files.length
	let requestCompleted = 0

	$.each(files, function(index, file) {

		formdata.append("userfile[]", file);
		filenames += file.name + 'vVv'

		$.ajax({
			url: document.URL + querySeparator + "subaction=uploadPhoto&token=" + token,
			type: "POST",
			data: formdata,
			processData: false,
			contentType: false,
			complete: function () {
				requestCompleted++
				if (requestCompleted == totalCount) {
					$.ajax({
						url: document.URL + querySeparator + "subaction=addFiles&token=" + token,
						type: "POST",
						data: JSON.stringify({
							filenames: filenames,
							objectId: objectId,
							objectType: objectType,
							objectSubtype: objectSubtype,
							objectSubdir: objectSubdir
						}),
						processData: false,
						contentType: 'application/json',
						success: function ( resp ) {
              $('.wpeo-loader').removeClass('wpeo-loader')
              mediaGallery.removeClass('modal-active')
              if ($('.floatleft.inline-block.valignmiddle.divphotoref').length > 0) {
                $('.floatleft.inline-block.valignmiddle.divphotoref').replaceWith($(resp).find('.floatleft.inline-block.valignmiddle.divphotoref'))
              }
              if (typeof objectPhotoClass != 'undefined' && objectPhotoClass.length > 0) {
                $('.photo.'+objectPhotoClass).replaceWith($(resp).find('.photo.'+objectPhotoClass).first())
                $('.linked-medias.'+objectPhotoClass).replaceWith($(resp).find('.linked-medias.'+objectPhotoClass))
              } else {
                $('.linked-medias.'+objectSubtype).replaceWith($(resp).find('.linked-medias.'+objectSubtype))
              }

              //refresh media gallery & unselect selected medias
              mediaGallery.html($(resp).find('#media_gallery').children())
              window.saturne.modal.loadLazyImages();
						},
					});
				}
			}
		});

		formdata = new FormData();
	})
};

/**
 * Action select page
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.mediaGallery.selectPage = function( event ) {

	let token = window.saturne.toolbox.getToken();

	let pagesCounter       = $(this).closest('.wpeo-pagination').find('#pagesCounter').val()
	let containerToRefresh = $(this).closest('.wpeo-pagination').find('#containerToRefresh').val()

	let offset = $(this).attr('value');

	let mediaGallery = $('#' + containerToRefresh);

	let objectId         = mediaGallery.find('.modal-options').attr('data-from-id')
	let objectType       = mediaGallery.find('.modal-options').attr('data-from-type')
	let objectSubtype    = mediaGallery.find('.modal-options').attr('data-from-subtype')
	let objectSubdir     = mediaGallery.find('.modal-options').attr('data-from-subdir')

	let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL)

	if (!$(this).hasClass('arrow')) {
		$(this).closest('.wpeo-pagination').find('.pagination-element').removeClass('pagination-current');
		$(this).closest('.pagination-element').addClass('pagination-current');
	}

	window.saturne.loader.display($('#media_gallery').find('.modal-content'));

	$.ajax({
		url: document.URL + querySeparator + "subaction=pagination" + "&token=" + token,
		type: "POST",
		data: JSON.stringify({
			offset: offset,
			pagesCounter: pagesCounter
		}),
		processData: false,
		contentType: 'application/json',
		success: function ( resp ) {
			$('.wpeo-loader').removeClass('wpeo-loader')
			mediaGallery.html($(resp).find('#' + containerToRefresh).children());

			mediaGallery.find('.modal-options').attr('data-from-id', objectId)
			mediaGallery.find('.modal-options').attr('data-from-type', objectType)
			mediaGallery.find('.modal-options').attr('data-from-subtype', objectSubtype)
			mediaGallery.find('.modal-options').attr('data-from-subdir', objectSubdir)
      window.saturne.modal.loadLazyImages();
		},
		error: function ( ) {
		}
	})
};

/**
 * Action toggle today medias
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.mediaGallery.toggleTodayMedias = function( event ) {
  if ( event ) {
    event.preventDefault();
  }

  let token          = window.saturne.toolbox.getToken();
  let baseUrl        = document.URL.split('#')[0];
  let querySeparator = window.saturne.toolbox.getQuerySeparator(baseUrl);

  let toggleValue = $(this).attr('value');

  if ($('.ecm-photo-list-content').length) {
    window.saturne.loader.display($('.ecm-photo-list-content'));
  }
  if ($('.wpeo-pagination').length) {
    window.saturne.loader.display($('.wpeo-pagination'));
  }

  $.ajax({
    url: baseUrl + querySeparator + "subaction=toggleTodayMedias&toggle_today_medias=" + toggleValue + "&token=" + token,
    type: "POST",
    processData: false,
    contentType: false,
    success: function ( resp ) {
      let newToggle = $(resp).find('.toggle-today-medias');
      let newContent = $(resp).find('.ecm-photo-list-content');
      let newPagination = $(resp).find('.wpeo-pagination');

      if (newToggle.length) {
        $('.toggle-today-medias').replaceWith(newToggle);
      }
      if (newContent.length) {
        $('.ecm-photo-list-content').replaceWith(newContent);
      }
      if (newPagination.length) {
        $('.wpeo-pagination').replaceWith(newPagination);
      }
      window.saturne.modal.loadLazyImages();
    },
    error: function ( ) {
    },
    complete: function ( ) {
      $('.wpeo-loader').removeClass('wpeo-loader');
      $('.loader-spin').remove();
    }
  });
};

/**
 * Action toggle unlinked medias
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.mediaGallery.toggleUnlinkedMedias = function( event ) {
  if ( event ) {
    event.preventDefault();
  }

  let token          = window.saturne.toolbox.getToken();
  let baseUrl        = document.URL.split('#')[0];
  let querySeparator = window.saturne.toolbox.getQuerySeparator(baseUrl);

  let toggleValue = $(this).attr('value');

  if ($('.ecm-photo-list-content').length) {
    window.saturne.loader.display($('.ecm-photo-list-content'));
  }
  if ($('.wpeo-pagination').length) {
    window.saturne.loader.display($('.wpeo-pagination'));
  }

  $.ajax({
    url: baseUrl + querySeparator + "subaction=toggleUnlinkedMedias&toggle_unlinked_medias=" + toggleValue + "&token=" + token,
    type: "POST",
    processData: false,
    contentType: false,
    success: function ( resp ) {
      let newToggle = $(resp).find('.toggle-unlinked-medias');
      let newContent = $(resp).find('.ecm-photo-list-content');
      let newPagination = $(resp).find('.wpeo-pagination');

      if (newToggle.length) {
        $('.toggle-unlinked-medias').replaceWith(newToggle);
      }
      if (newContent.length) {
        $('.ecm-photo-list-content').replaceWith(newContent);
      }
      if (newPagination.length) {
        $('.wpeo-pagination').replaceWith(newPagination);
      }
      window.saturne.modal.loadLazyImages();
    },
    error: function ( ) {
    },
    complete: function ( ) {
      $('.wpeo-loader').removeClass('wpeo-loader');
      $('.loader-spin').remove();
    }
  });
};

/**
 * Read the gallery again, on its first page, then run the callback
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @param  {Function} callback Run once the gallery is read again
 * @return {void}
 */
window.saturne.mediaGallery.reload = function(callback) {
  let mediaGallery = $('#media_gallery');

  window.saturne.loader.display(mediaGallery.find('.modal-content'));

  // A plain reading of the page: the pagination action needs the count of pages, which a gallery
  // on a single page does not show
  $.ajax({
    url: document.URL.split('#')[0],
    type: 'GET',
    success: function(resp) {
      let freshGallery = $(resp).find('#media_gallery').first();

      // The children only: the opener of the gallery is written on the modal itself
      if (freshGallery.length) {
        mediaGallery.html(freshGallery.children());
      }
      window.saturne.modal.loadLazyImages();

      if (typeof callback === 'function') {
        callback();
      }
    },
    complete: function() {
      $('.wpeo-loader').removeClass('wpeo-loader');
    }
  });
};

/**
 * Read the gallery again when the library changed since the page was written
 *
 * The gallery tab comes with the page and the pending one with its first opening: a photo a media
 * block sent since then joined the library but showed in neither tab until the page was reloaded.
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @return {void}
 */
window.saturne.mediaGallery.reloadIfChanged = function() {
  if (!window.saturne.mediaGallery.libraryChanged) {
    return;
  }

  window.saturne.mediaGallery.libraryChanged = false;
  window.saturne.mediaGallery.reload(function() {
    // The pending list is built again: the button it offers follows the opener of the gallery
    if (window.saturne.mediaPending) {
      window.saturne.mediaPending.syncCurrentTarget($('#media_gallery'));
    }
  });
};

/**
 * Catch up of the library on the medias the records hold
 *
 * The server works a slice at a time: the browser calls again until it is done, then shows what was
 * copied and lists the library again. Each answer comes from a page that renewed the CSRF token, the
 * next call carries the new one.
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @return {void}
 */
window.saturne.mediaGallery.syncLibrary = function() {
  let button = $(this);

  if (button.hasClass('sync-running')) {
    return;
  }

  let baseUrl        = document.URL.split('#')[0];
  let querySeparator = window.saturne.toolbox.getQuerySeparator(baseUrl);
  let token          = window.saturne.toolbox.getToken();
  let copied         = { added: 0, renamed: 0 };

  button.addClass('sync-running').find('i').addClass('fa-spin');

  let finish = function(message, color) {
    button.removeClass('sync-running').find('i').removeClass('fa-spin');
    if (typeof $.jnotify === 'function') {
      $.jnotify(message, { color: color });
    } else {
      alert(message);
    }
  };

  let step = function() {
    $.ajax({
      url: baseUrl + querySeparator + 'subaction=syncMediaLibrary&token=' + token,
      type: 'POST',
      success: function(resp) {
        let page  = $(resp);
        let stats = null;

        try {
          stats = JSON.parse(page.find('#saturne-sync-media-library-response').text());
        } catch (e) {
          finish(button.data('label-error'), 'red');
          return;
        }

        token = page.find('input[name="token"]').first().val() || token;

        // Every call goes over the whole module again: a media copied by an earlier one is now
        // counted as present
        if (!stats.done) {
          copied.added   += stats.added;
          copied.renamed += stats.renamed;
          step();
          return;
        }

        let added   = copied.added + stats.added;
        let renamed = copied.renamed + stats.renamed;
        let present = stats.present - copied.added - copied.renamed;
        let message = button.data('label-done').replace('%s', added + renamed).replace('%s', renamed).replace('%s', present).replace('%s', stats.excluded);

        finish(message, 'green');

        // The library holds new medias: list it again
        window.saturne.mediaGallery.reload();
      },
      error: function() {
        finish(button.data('label-error'), 'red');
      }
    });
  };

  step();
};

/**
 * Regenerate thumbs media action
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.saturne.mediaGallery.regenerateThumbs = function() {
  let token          = window.saturne.toolbox.getToken();
  let baseUrl        = document.URL.split('#')[0];
  let querySeparator = window.saturne.toolbox.getQuerySeparator(baseUrl);
  let fullname       = $(this).closest('.photo-image').find('.fullname').attr('data-fullname');

  window.saturne.loader.display($(this).closest('.photo-image'));

  $.ajax({
    url: baseUrl + querySeparator + 'subaction=regenerate_thumbs&token=' + token,
    type: 'POST',
    data: JSON.stringify({
      fullname: fullname
    }),
    processData: false,
    contentType: 'application/json',
    success: function(resp) {
      let newContent = $(resp).find('.ecm-photo-list-content');
      if (newContent.length) {
        $('.ecm-photo-list-content').replaceWith(newContent);
      }
      window.saturne.modal.loadLazyImages();
    },
    error: function() {},
    complete: function() {
      $('.wpeo-loader').removeClass('wpeo-loader');
      $('.loader-spin').remove();
    }
  });
};

/**
 * Read file with AI
 *
 * @since   22.0.0
 * @version 22.0.0
 *
 * @return {void}
 */
window.saturne.mediaGallery.readFileAI = function(e) {
  e.preventDefault();

  let token = window.saturne.toolbox.getToken();
  let mediaContainer   = $(this).closest('.media-container');
  let filepath         = mediaContainer.find('.file-path').val();
  let filename         = mediaContainer.find('.file-name').val();

  let fullButton = $(this);
  let icon = fullButton.find('.button-icon');
  icon.removeClass('fa-magic').addClass('fa-circle-notch fa-spin');

  let descriptionTextarea = $('textarea#description');

  let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);

  $.ajax({
    url: document.URL + querySeparator + "subaction=readFileAI&token=" + token,
    type: 'POST',
    contentType: "application/json; charset=utf-8",
    dataType: "json",
    data: JSON.stringify({
      token: token,
      filepath: filepath,
      filename: filename
    }),
    success: function(response) {

      if (response.success) {

        fullButton.addClass('colorsuccess');
        icon.removeClass('fa-circle-notch fa-spin').addClass('fa-check');

        // Contact
        if (response.type == 'contact') {
          $('#reedcrm_firstname').val(response.contact.prenom);
          $('#reedcrm_lastname').val(response.contact.nom);
          $('#projectphone').val(response.contact.telephone.trim());
          $('#reedcrm_email').val(response.contact.email);
        }

        // Description
        let descValue = descriptionTextarea.val();
        if (descValue !== "") {
          descValue += "\n";
        }
        descValue += response.text;
        descriptionTextarea.val(descValue);

      } else {
        fullButton.addClass('colorerror');
        icon.removeClass('fa-circle-notch fa-spin').addClass('fa-times');
        console.error(response.error);
      }
    },
    error: function(xhr, status, error) {
      fullButton.addClass('colorerror');
      icon.removeClass('fa-circle-notch fa-spin').addClass('fa-times');
    }
  });
};

/* jshint ignore:end */
