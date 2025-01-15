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
  $( document ).on( 'click', '.save-photo', window.saturne.mediaGallery.savePhoto );
  $(document).on( 'click', '.delete-photo', window.saturne.mediaGallery.deletePhoto);
  $( document ).on( 'change', '.flat.minwidth400.maxwidth200onsmartphone', window.saturne.mediaGallery.sendPhoto );
  $( document ).on( 'click', '.clicked-photo-preview', window.saturne.mediaGallery.previewPhoto );
  $( document ).on( 'input', '.form-element #search_in_gallery', window.saturne.mediaGallery.handleSearch );
  $( document ).on( 'click', '.media-gallery-unlink', window.saturne.mediaGallery.unlinkFile );
  $( document ).on( 'click', '.media-gallery-favorite', window.saturne.mediaGallery.addToFavorite );
  $( document ).on( 'change', '.fast-upload', window.saturne.mediaGallery.fastUpload );
  $( document ).on( 'click', '.select-page', window.saturne.mediaGallery.selectPage );
  $( document ).on( 'click', '.toggle-today-medias', window.saturne.mediaGallery.toggleTodayMedias );
  $( document ).on( 'click', '.toggle-unlinked-medias', window.saturne.mediaGallery.toggleUnlinkedMedias );
  $(document).on('click', '.regenerate-thumbs', window.saturne.mediaGallery.regenerateThumbs);
}

/**
 * Select photo
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.mediaGallery.selectPhoto = function( event ) {
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
	let token             = window.saturne.toolbox.getToken();

	let objectId         = mediaGallery.attr('data-from-id');
	let objectType       = mediaGallery.attr('data-from-type')
	let objectSubtype    = mediaGallery.attr('data-from-subtype')
  let objectSubdir     = mediaGallery.attr('data-from-subdir')
  let objectPhotoClass = mediaGallery.attr('data-photo-class')

	let filenames = ''
	if (filesLinked.length > 0) {
		filesLinked.each(function(  ) {
			filenames += $( this ).find('.filename').val() + 'vVv'
		});
	}
	window.saturne.loader.display($(this));
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
			filenames: filenames,
			objectId: objectId,
			objectType: objectType,
			objectSubdir: objectSubdir,
			objectSubtype: objectSubtype
		}),
		processData: false,
		contentType: false,
		success: function ( resp ) {
			$('.wpeo-loader').removeClass('wpeo-loader')
			mediaGallery.removeClass('modal-active')
      if (typeof objectPhotoClass != 'undefined' && objectPhotoClass.length > 0) {
        $('.photo.'+objectPhotoClass).replaceWith($(resp).find('.photo.'+objectPhotoClass).first())
        $('.linked-medias.'+objectPhotoClass).replaceWith($(resp).find('.linked-medias.'+objectPhotoClass))
      } else {

        $('.linked-medias.'+objectSubtype).html($(resp).find('.linked-medias.'+objectSubtype).children())
      }
      if ($('.floatleft.inline-block.valignmiddle.divphotoref').length > 0) {
        $('.floatleft.inline-block.valignmiddle.divphotoref').replaceWith($(resp).find('.floatleft.inline-block.valignmiddle.divphotoref'))
      }
			//refresh media gallery & unselect selected medias
			mediaGallery.html($(resp).find('#media_gallery').children())
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
    contentType: false,
    data: JSON.stringify({
      filenames: fileNames
    }),
    success: function(resp) {
      $('#media_gallery .modal-container').replaceWith($(resp).find('#media_gallery .modal-container'));
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

	let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL)
	let textToShow = '';

	$.each(files, function(index, file) {
		let formdata = new FormData();
		formdata.append("userfile[]", file);
		$.ajax({
			url: document.URL + querySeparator + "subaction=uploadPhoto&token=" + token,
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
						mediaGallery.html($(resp).find('#media_gallery').children()).promise().done( () => {
							if (totalCount == 1) {
								$('#media_gallery').find('.save-photo').removeClass('button-disable');
								$('#media_gallery').find('.delete-photo').removeClass('button-disable');
								$('#media_gallery').find('.clickable-photo0').addClass('clicked-photo');
							}
							if ($(resp).find('.error-medias').length) {
								$('.messageErrorSendPhoto').find('.notice-subtitle').html(textToShow)
								$('.messageErrorSendPhoto').removeClass('hidden');
							} else {
								$('.messageSuccessSendPhoto').removeClass('hidden');
							}
							mediaGallery.attr('data-from-id', objectId);
							mediaGallery.attr('data-from-type', objectType);
							mediaGallery.attr('data-from-subtype', objectSubtype);
							mediaGallery.attr('data-from-subdir', objectSubdir);
							mediaGallery.find('.wpeo-button').attr('value', objectId);
						})
					}, 800)
				}
			},
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
			objectId: objectId
		}),
		processData: false,
		success: function ( resp ) {
      if (previousFavorite != filename) {
        if (typeof objectPhotoClass != 'undefined' && objectPhotoClass.length > 0) {
          $('.photo.'+objectPhotoClass).replaceWith($(resp).find('.photo.'+objectPhotoClass).first())
        }
      }
      if ($('.floatleft.inline-block.valignmiddle.divphotoref').length > 0) {
        $('.floatleft.inline-block.valignmiddle.divphotoref').replaceWith($(resp).find('.floatleft.inline-block.valignmiddle.divphotoref'))
      }
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
						contentType: false,
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
		contentType: false,
		success: function ( resp ) {
			$('.wpeo-loader').removeClass('wpeo-loader')
			mediaGallery.html($(resp).find('#' + containerToRefresh).children());

			mediaGallery.find('.modal-options').attr('data-from-id', objectId)
			mediaGallery.find('.modal-options').attr('data-from-type', objectType)
			mediaGallery.find('.modal-options').attr('data-from-subtype', objectSubtype)
			mediaGallery.find('.modal-options').attr('data-from-subdir', objectSubdir)
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

  let token          = window.saturne.toolbox.getToken();
  let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL)

  let toggleValue = $(this).attr('value')

  window.saturne.loader.display($('.ecm-photo-list-content'))
  window.saturne.loader.display($('.wpeo-pagination'))

  $.ajax({
    url: document.URL + querySeparator + "subaction=toggleTodayMedias&toggle_today_medias=" + toggleValue + "&token=" + token,
    type: "POST",
    processData: false,
    contentType: false,
    success: function ( resp ) {
      $('.toggle-today-medias').replaceWith($(resp).find('.toggle-today-medias'))
      $('.ecm-photo-list-content').replaceWith($(resp).find('.ecm-photo-list-content'))
      $('.wpeo-pagination').replaceWith($(resp).find('.wpeo-pagination'))
    },
    error: function ( ) {
    }
  })
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

  let token          = window.saturne.toolbox.getToken();
  let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL)

  let toggleValue = $(this).attr('value')

  window.saturne.loader.display($('.ecm-photo-list-content'))
  window.saturne.loader.display($('.wpeo-pagination'))

  $.ajax({
    url: document.URL + querySeparator + "subaction=toggleUnlinkedMedias&toggle_unlinked_medias=" + toggleValue + "&token=" + token,
    type: "POST",
    processData: false,
    contentType: false,
    success: function ( resp ) {
      $('.toggle-unlinked-medias').replaceWith($(resp).find('.toggle-unlinked-medias'))
      $('.ecm-photo-list-content').replaceWith($(resp).find('.ecm-photo-list-content'))
      $('.wpeo-pagination').replaceWith($(resp).find('.wpeo-pagination'))
    },
    error: function ( ) {
    }
  })
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
  let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);
  let fullname       = $(this).closest('.photo-image').find('.fullname').attr('data-fullname');

  window.saturne.loader.display($(this).closest('.photo-image'));

  $.ajax({
    url: document.URL + querySeparator + 'subaction=regenerate_thumbs&token=' + token,
    type: 'POST',
    data: JSON.stringify({
      fullname: fullname
    }),
    processData: false,
    contentType: false,
    success: function(resp) {
      $('.ecm-photo-list-content').replaceWith($(resp).find('.ecm-photo-list-content'));
    },
    error: function() {}
  });
};
