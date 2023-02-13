/* Copyright (C) 2021-2023 EVARISK <dev@evarisk.com>
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
 * \brief   JavaScript file for module Saturne.
 */

/**
 * Initialise l'objet "mediaGallery" ainsi que la méthode "init" obligatoire pour la bibliothèque Saturne.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.saturne.mediaGallery = {};

/**
 * La méthode appelée automatiquement par la bibliothèque Saturne.
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
 * La méthode contenant tous les événements pour la bibliothèque de médias.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.mediaGallery.event = function() {
	// Photos
	$( document ).on( 'click', '.clickable-photo', window.saturne.mediaGallery.selectPhoto );
	$( document ).on( 'click', '.save-photo', window.saturne.mediaGallery.savePhoto );
	$( document ).on( 'change', '.flat.minwidth400.maxwidth200onsmartphone', window.saturne.mediaGallery.sendPhoto );
	$( document ).on( 'click', '.clicked-photo-preview', window.saturne.mediaGallery.previewPhoto );
	$( document ).on( 'input', '.form-element #search_in_gallery', window.saturne.mediaGallery.handleSearch );
	$( document ).on( 'click', '.media-gallery-unlink', window.saturne.mediaGallery.unlinkFile );
	$( document ).on( 'click', '.media-gallery-favorite', window.saturne.mediaGallery.addToFavorite );
	$( document ).on( 'change', '.fast-upload', window.saturne.mediaGallery.fastUpload );
	$( document ).on( 'click', '.select-page', window.saturne.mediaGallery.selectPage );
}

/**
 * Select photo.
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
		}

	} else {
		parent.closest('.modal-container').find('.save-photo').removeClass('button-disable');
		parent.find('.clickable-photo'+photoID).addClass('clicked-photo');
	}
};

/**
 * Action save photo to an object.
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
	let token             = $('.id-container').find('input[name="token"]').val();

	let objectId         = $(this).find('.from-id').val()
	let objectType       = $(this).find('.from-type').val()
	let objectSubtype    = $(this).find('.from-subtype').length ? $(this).find('.from-subtype').val() : ''
	let objectSubdir     = $(this).find('.from-subdir').length ? $(this).find('.from-subdir').val() : ''

	let filenames = ''
	if (filesLinked.length > 0) {
		filesLinked.each(function(  ) {
			filenames += $( this ).find('.filename').val() + 'vVv'
		});
	}

	window.saturne.loader.display($(this));
	window.saturne.loader.display($('.linked-medias.'+objectSubtype));

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

			//refresh medias container after adding
			$('.linked-medias.'+objectSubtype).html($(resp).find('.linked-medias.'+objectSubtype).children())

			//refresh media gallery & unselect selected medias
			mediaGallery.html($(resp).find('#media_gallery').children())
		},
	});
};

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
 * Action send photo.
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

	let objectId         = mediaGallery.find('.from-id').val()
	let objectType       = mediaGallery.find('.from-type').val()
	let objectSubtype    = mediaGallery.find('.from-subtype').length ? mediaGallery.find('.from-subtype').val() : ''
	let objectSubdir     = mediaGallery.find('.from-subdir').length ? mediaGallery.find('.from-subdir').val() : ''

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
								$('#media_gallery').find('.clickable-photo0').addClass('clicked-photo');
							}
							if ($(resp).find('.error-medias').length) {
								$('.messageErrorSendPhoto').find('.notice-subtitle').html(textToShow)
								$('.messageErrorSendPhoto').removeClass('hidden');
							} else {
								$('.messageSuccessSendPhoto').removeClass('hidden');
							}
							$('#media_gallery').find('.from-id').attr('value', objectId);
							$('#media_gallery').find('.from-type').attr('value', objectType);
							$('#media_gallery').find('.from-subtype').attr('value', objectSubtype);
							$('#media_gallery').find('.from-subdir').attr('value', objectSubdir);
							$('#media_gallery').find('.wpeo-button').attr('value', objectId);
						})
					}, 800)
				}
			},
		});
	})
};

/**
 * Action preview photo.
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
 * Action unlink photo.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.mediaGallery.unlinkFile = function( event ) {

	event.preventDefault()

	let token = window.saturne.toolbox.getToken();

	let objectSubtype = $(this).closest('.linked-medias').find('.from-subtype').length ? $(this).closest('.linked-medias').find('.from-subtype').val() : ''

	let mediaContainer = $(this).closest('.media-container')
	let filepath       = mediaContainer.find('.file-path').val()

	window.saturne.loader.display(mediaContainer);

	let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL)

	$.ajax({
		url: document.URL + querySeparator + "subaction=unlinkFile&token=" + token,
		type: "POST",
		data: JSON.stringify({
			filepath: filepath,
		}),
		processData: false,
		success: function ( resp ) {
			$('.wpeo-loader').removeClass('wpeo-loader')
			$('.linked-medias.'+objectSubtype).html($(resp).find('.linked-medias.'+objectSubtype).children())
		}
	});
};

/**
 * Action add photo to favorite.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.mediaGallery.addToFavorite = function( event ) {
		event.preventDefault()
		let filename = $(this).closest('.media-gallery-favorite').find('.filename').attr('value')

		//change star button style
		let previousFavorite = $(this).closest('.linked-medias').find('.fas.fa-star')
		let newFavorite = $(this).find('.far.fa-star')

		previousFavorite.removeClass('fas')
		previousFavorite.addClass('far')
		previousFavorite.closest('.media-gallery-favorite').removeClass('favorite')
		newFavorite.addClass('fas')
		newFavorite.removeClass('far')
		newFavorite.closest('.media-gallery-favorite').addClass('favorite')

		if (filename.length > 0) {
			$(this).closest('.linked-medias').find('.favorite-photo').val(filename)
		}
};

/**
 * Action fast upload.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.mediaGallery.fastUpload = function( typeFrom ) {
	let files         = $(this).prop("files");
	let token         = $('input[name="token"]').val();
	let mediaGallery  = $('#media_gallery')

	let formdata = new FormData();

	let objectId         = $(this).closest('.linked-medias').find('.from-id').val()
	let objectType       = $(this).closest('.linked-medias').find('.from-type').val()
	let objectSubtype    = $(this).closest('.linked-medias').find('.from-subtype').length ? $(this).closest('.linked-medias').find('.from-subtype').val() : ''
	let objectSubdir     = $(this).closest('.linked-medias').find('.from-subdir').length ? $(this).closest('.linked-medias').find('.from-subdir').val() : ''

	window.saturne.loader.display($('.linked-medias.'+objectSubtype));

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

							//refresh medias container after adding
							$('.linked-medias.'+objectSubtype).html($(resp).find('.linked-medias.'+objectSubtype).children())

							//refresh media gallery & unselect selected medias
							mediaGallery.html($(resp).find('#media_gallery').children())
						},
					});
				}
			}
		});
	})
};

/**
 * Action select page.
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
		},
		error: function ( ) {
		}
	})
};

