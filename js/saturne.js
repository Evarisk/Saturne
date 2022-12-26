/* Copyright (C) 2022 EVARISK <dev@evarisk.com>
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
 * \file    js/saturne.js
 * \ingroup saturne
 * \brief   JavaScript file for module Saturne.
 */

/* Javascript library of module Saturne */

'use strict';
/**
 * @namespace Saturne_Framework_Init
 *
 * @author Evarisk <dev@evarisk.com>
 * @copyright 2015-2022 Evarisk
 */

if ( ! window.saturne ) {
	/**
	 * [saturne description]
	 *
	 * @memberof Saturne_Framework_Init
	 *
	 * @type {Object}
	 */
	window.saturne = {};

	/**
	 * [scriptsLoaded description]
	 *
	 * @memberof Saturne_Framework_Init
	 *
	 * @type {Boolean}
	 */
	window.saturne.scriptsLoaded = false;
}

if ( ! window.saturne.scriptsLoaded ) {
	/**
	 * [description]
	 *
	 * @memberof Saturne_Framework_Init
	 *
	 * @returns {void} [description]
	 */
	window.saturne.init = function() {
		window.saturne.load_list_script();
	};

	/**
	 * [description]
	 *
	 * @memberof Saturne_Framework_Init
	 *
	 * @returns {void} [description]
	 */
	window.saturne.load_list_script = function() {
		if ( ! window.saturne.scriptsLoaded) {
			var key = undefined, slug = undefined;
			for ( key in window.saturne ) {

				if ( window.saturne[key].init ) {
					window.saturne[key].init();
				}

				for ( slug in window.saturne[key] ) {

					if ( window.saturne[key] && window.saturne[key][slug] && window.saturne[key][slug].init ) {
						window.saturne[key][slug].init();
					}

				}
			}

			window.saturne.scriptsLoaded = true;
		}
	};

	/**
	 * [description]
	 *
	 * @memberof Saturne_Framework_Init
	 *
	 * @returns {void} [description]
	 */
	window.saturne.refresh = function() {
		var key = undefined;
		var slug = undefined;
		for ( key in window.saturne ) {
			if ( window.saturne[key].refresh ) {
				window.saturne[key].refresh();
			}

			for ( slug in window.saturne[key] ) {

				if ( window.saturne[key] && window.saturne[key][slug] && window.saturne[key][slug].refresh ) {
					window.saturne[key][slug].refresh();
				}
			}
		}
	};

	$( document ).ready( window.saturne.init );
}

/**
 * @namespace EO_Framework_Loader
 *
 * @author Evarisk <dev@evarisk.com>
 * @copyright 2015-2018 Evarisk
 */

/*
 * Gestion du loader.
 *
 * @since 1.0.0
 * @version 1.0.0
 */
if ( ! window.saturne.loader ) {

	/**
	 * [loader description]
	 *
	 * @memberof EO_Framework_Loader
	 *
	 * @type {Object}
	 */
	window.saturne.loader = {};

	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Loader
	 *
	 * @returns {void} [description]
	 */
	window.saturne.loader.init = function() {
		window.saturne.loader.event();
	};

	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Loader
	 *
	 * @returns {void} [description]
	 */
	window.saturne.loader.event = function() {
	};

	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Loader
	 *
	 * @param  {void} element [description]
	 * @returns {void}         [description]
	 */
	window.saturne.loader.display = function( element ) {
		// Loader spécial pour les "button-progress".
		if ( element.hasClass( 'button-progress' ) ) {
			element.addClass( 'button-load' )
		} else {
			element.addClass( 'wpeo-loader' );
			var el = $( '<span class="loader-spin"></span>' );
			element[0].loaderElement = el;
			element.append( element[0].loaderElement );
		}
	};

	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Loader
	 *
	 * @param  {jQuery} element [description]
	 * @returns {void}         [description]
	 */
	window.saturne.loader.remove = function( element ) {
		if ( 0 < element.length && ! element.hasClass( 'button-progress' ) ) {
			element.removeClass( 'wpeo-loader' );

			$( element[0].loaderElement ).remove();
		}
	};
}

/**
 * Initialise l'objet "mediaGallery" ainsi que la méthode "init" obligatoire pour la bibliothèque EvariskJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.saturne.mediaGallery = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EvariskJS.
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
 * La méthode contenant tous les événements pour le mediaGallery.
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
	$( document ).on( 'submit', '.fast-upload', window.saturne.mediaGallery.fastUpload );
	$( document ).on( 'click', '.selected-page', window.saturne.mediaGallery.selectPage );
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

	let currentElement    = $(this)
	let mediaGallery      = $('#media_gallery')
	let mediaGalleryModal = $(this).closest('.modal-container')
	let filesLinked       = mediaGalleryModal.find('.clicked-photo')
	let token             = $('.id-container').find('input[name="token"]').val();

	let objectId         = $(this).find('.from-id').val()
	let objectType       = $(this).find('.from-type').val()
	let objectSubtype    = $(this).find('.from-subtype').length ? $(this).find('.from-subtype').val() : ''
	let favoriteInput    = $(this).closest('.linked-medias')

	let filenames = ''
	if (filesLinked.length > 0) {
		filesLinked.each(function(  ) {
			filenames += $( this ).find('.filename').val() + 'vVv'
		});
	}

	let favorite = filenames.split('vVv')[0]

	window.saturne.loader.display($(this));

	let querySeparator = '?'
	document.URL.match(/\?/) ? querySeparator = '&' : 1

	$.ajax({
		url: document.URL + querySeparator + "subaction=addFiles&token=" + token,
		type: "POST",
		data: JSON.stringify({
			filenames: filenames,
			objectId: objectId,
			objectType: objectType,
			objectSubtype: objectSubtype
		}),
		processData: false,
		contentType: false,
		success: function ( resp ) {
			$('.wpeo-loader').removeClass('wpeo-loader')
			mediaGallery.removeClass('modal-active')

			//sanitize media name
			favorite = favorite.replace(/\ /g, '%20')
			favorite = favorite.replace(/\(/g, '%28')
			favorite = favorite.replace(/\)/g, '%29')
			favorite = favorite.replace(/\+/g, '%2B')

			//refresh medias container after adding
			$('.linked-medias.'+objectSubtype).html($(resp).find('.linked-medias.'+objectSubtype).children())

			//fill favorite hidden input
			let favoriteMedia = $('.linked-medias.'+objectSubtype).find('.media-container').find('.media-gallery-favorite .filename').attr('value')
			favoriteInput.val(favoriteMedia)

			//add media to favorite in frontend
			$('.linked-medias.'+objectSubtype).find('.media-container').find('.media-gallery-favorite .fa-star').first().removeClass('far').addClass('fas')
			$('.linked-medias.'+objectSubtype).find('.media-container').find('.media-gallery-favorite').first().addClass('favorite')

			//refresh media gallery & unselect selected medias
			$('.wpeo-modal.modal-photo').html($(resp).find('.wpeo-modal.modal-photo .modal-container'))
		},
	});

	// if (type === 'riskassessment') {
	// 	mediaLinked = modalFrom.find('.element-linked-medias')
	// 	window.saturne.loader.display(mediaLinked);
	//
	// 	let riskAssessmentPhoto = ''
	// 	riskAssessmentPhoto = $('.risk-evaluation-photo-'+idToSave+'.risk-'+riskId)
	//
	// 	let filepath = modalFrom.find('.risk-evaluation-photo-single .filepath-to-riskassessment').val()
	// 	let thumbName = window.saturne.file.getThumbName(favorite)
	// 	let newPhoto = filepath + thumbName
	//
	// 	$.ajax({
	// 		url: document.URL + "&action=addFiles&token=" + token +'&favorite='+favorite,
	// 		type: "POST",
	// 		data: JSON.stringify({
	// 			risk_id: riskId,
	// 			riskassessment_id: idToSave,
	// 			filenames: filenames,
	// 		}),
	// 		processData: false,
	// 		contentType: false,
	// 		success: function ( resp ) {
	// 			$('.wpeo-loader').removeClass('wpeo-loader')
	// 			parent.removeClass('modal-active')
	//
	// 			newPhoto = newPhoto.replace(/\ /g, '%20')
	// 			newPhoto = newPhoto.replace(/\(/g, '%28')
	// 			newPhoto = newPhoto.replace(/\)/g, '%29')
	// 			newPhoto = newPhoto.replace(/\+/g, '%2B')
	//
	// 			//Update risk assessment main img in "photo" of risk assessment modal
	// 			riskAssessmentPhoto.each( function() {
	// 				$(this).find('.clicked-photo-preview').attr('src',newPhoto)
	// 				$(this).find('.filename').attr('value', favorite)
	// 				$(this).find('.clicked-photo-preview').hasClass('photosaturne') ? $(this).find('.clicked-photo-preview').removeClass('photosaturne').addClass('photo') : 0
	// 			});
	//
	// 			//Remove special chars from img
	// 			favorite = favorite.replace(/\ /g, '%20')
	// 			favorite = favorite.replace(/\(/g, '%28')
	// 			favorite = favorite.replace(/\)/g, '%29')
	// 			favorite = favorite.replace(/\+/g, '%2B')
	//
	// 			mediaLinked.html($(resp).find('.element-linked-medias-'+idToSave+'.risk-'+riskId).first())
	//
	// 			modalFrom.find('.messageSuccessSavePhoto').removeClass('hidden')
	// 		},
	// 		error: function ( ) {
	// 			modalFrom.find('.messageErrorSavePhoto').removeClass('hidden')
	// 		}
	// 	});
	//
	// } else if (type === 'digiriskelement') {
	// 	mediaLinked = $('#digirisk_element_medias_modal_'+idToSave).find('.element-linked-medias')
	// 	window.saturne.loader.display(mediaLinked);
	//
	// 	let digiriskElementPhoto = ''
	// 	digiriskElementPhoto = $('.digirisk-element-'+idToSave).find('.clicked-photo-preview')
	//
	// 	let filepath = $('.digirisk-element-'+idToSave).find('.filepath-to-digiriskelement').val()
	// 	let thumbName = window.saturne.file.getThumbName(favorite)
	// 	let newPhoto = filepath + thumbName
	//
	// 	$.ajax({
	// 		url: document.URL + "&action=addDigiriskElementFiles&token=" + token,
	// 		type: "POST",
	// 		data: JSON.stringify({
	// 			digiriskelement_id: idToSave,
	// 			filenames: filenames,
	// 		}),
	// 		processData: false,
	// 		contentType: false,
	// 		success: function ( resp ) {
	// 			$('.wpeo-loader').removeClass('wpeo-loader')
	// 			parent.removeClass('modal-active')
	//
	// 			newPhoto = newPhoto.replace(/\ /g, '%20')
	// 			newPhoto = newPhoto.replace(/\(/g, '%28')
	// 			newPhoto = newPhoto.replace(/\)/g, '%29')
	// 			newPhoto = newPhoto.replace(/\+/g, '%2B')
	//
	// 			digiriskElementPhoto.attr('src',newPhoto )
	//
	// 			let photoContainer = digiriskElementPhoto.closest('.open-media-gallery')
	// 			photoContainer.removeClass('open-media-gallery')
	// 			photoContainer.addClass('open-medias-linked')
	// 			photoContainer.addClass('digirisk-element')
	// 			photoContainer.closest('.unit-container').find('.digirisk-element-medias-modal').load(document.URL+ ' #digirisk_element_medias_modal_'+idToSave)
	//
	// 			favorite = favorite.replace(/\ /g, '%20')
	// 			favorite = favorite.replace(/\(/g, '%28')
	// 			favorite = favorite.replace(/\)/g, '%29')
	// 			favorite = favorite.replace(/\+/g, '%2B')
	//
	// 			if (idToSave === currentElementID) {
	// 				let digiriskBanner = $('.arearef.heightref')
	// 				digiriskBanner.load(document.URL+'&favorite='+favorite + ' .arearef.heightref')
	// 			}
	// 			mediaLinked.load(document.URL+'&favorite='+favorite + ' .element-linked-medias-'+idToSave+'.digirisk-element')
	// 			modalFrom.find('.messageSuccessSavePhoto').removeClass('hidden')
	// 		},
	// 		error: function ( ) {
	// 			modalFrom.find('.messageErrorSavePhoto').removeClass('hidden')
	// 		}
	// 	});
	// }
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

	let formdata = new FormData();

	let files         = $(this).prop("files");
	let elementParent = $(this).closest('.modal-container').find('.ecm-photo-list-content');
	let modalFooter   = $(this).closest('.modal-container').find('.modal-footer');

	let totalCount = files.length
	let progress   = 0

	let actionContainerSuccess = $('.messageSuccessSendPhoto');
	let actionContainerError   = $('.messageErrorSendPhoto');

	let token = $('input[name="token"]').val();

	$('#progressBar').width(0)
	$('#progressBarContainer').attr('style', 'display:block')

	window.saturne.loader.display($('#progressBarContainer'));

	let querySeparator = '?'
	document.URL.match(/\?/) ? querySeparator = '&' : 1

	$.each(files, function(index, file) {
		formdata.append("userfile[]", file);
		$.ajax({
			url: document.URL + querySeparator + "subaction=uploadPhoto&uploadMediasSuccess=1&token=" + token,
			type: "POST",
			data: formdata,
			processData: false,
			contentType: false,
			success: function (resp) {
				if ($(resp).find('.error-medias').length) {
					let response = $(resp).find('.error-medias').val()
					let decoded_response = JSON.parse(response)
					$('#progressBar').width('100%')
					$('#progressBar').css('background-color','#e05353')
					$('.wpeo-loader').removeClass('wpeo-loader');

					let textToShow = '';
					textToShow += decoded_response.message

					actionContainerError.find('.notice-subtitle').text(textToShow)

					actionContainerError.removeClass('hidden');
				} else {
					progress += (1 / totalCount) * 100
					$('#progressBar').animate({
						width: progress + '%'
					}, 300);
					if (index + 1 === totalCount) {
						elementParent.html($(resp).find('#media_gallery .ecm-photo-list')).promise().done( () => {
							setTimeout(() => {
								$('#progressBarContainer').fadeOut(800)
								$('.wpeo-loader').removeClass('wpeo-loader');
								$('#progressBarContainer').find('.loader-spin').remove();
							}, 800)

							//refresh pages navigation
							modalFooter.html($(resp).find('#media_gallery .modal-footer'))

							$('#add_media_to_gallery').parent().html($(resp).find('#add_media_to_gallery'))
							if (totalCount == 1) {
								elementParent.closest('.modal-container').find('.save-photo').removeClass('button-disable');
								elementParent.find('.clickable-photo0').addClass('clicked-photo');
							}
						})
						actionContainerSuccess.removeClass('hidden');
					}
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
			$( document ).find('.ui-dialog').css('z-index', '1500');
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
	let element_linked_id = $(this).find('.element-linked-id').val()
	let filename = $(this).find('.filename').val()
	let querySeparator = '?'
	let riskId = $(this).closest('.modal-risk').attr('value')
	let type = $(this).closest('.modal-container').find('.type-from').val()
	let noPhotoPath = $(this).closest('.modal-container').find('.no-photo-path').val()
	var params = new window.URLSearchParams(window.location.search);
	var currentElementID = params.get('id')

	let mediaContainer = $(this).closest('.media-container')
	let previousPhoto = null
	let previousName = ''
	let newPhoto = ''

	let token = $('.id-container.page-ut-gp-list').find('input[name="token"]').val();

	window.saturne.loader.display($(this).closest('.media-container'));

	document.URL.match(/\?/) ? querySeparator = '&' : 1

	if (type === 'riskassessment') {
		let riskAssessmentPhoto = $('.risk-evaluation-photo-'+element_linked_id)
		previousPhoto = $(this).closest('.modal-container').find('.risk-evaluation-photo .clicked-photo-preview')
		newPhoto = previousPhoto[0].src
		$.ajax({
			url: document.URL + querySeparator + "action=unlinkFile&token=" + token,
			type: "POST",
			data: JSON.stringify({
				risk_id: riskId,
				riskassessment_id: element_linked_id,
				filename: filename,
			}),
			processData: false,
			success: function ( ) {
				$('.wpeo-loader').removeClass('wpeo-loader')
				riskAssessmentPhoto.each( function() {
					$(this).find('.clicked-photo-preview').attr('src',noPhotoPath )
				});
				mediaContainer.hide()
			}
		});
	} else if (type === 'digiriskelement') {
		previousPhoto = $('.digirisk-element-'+element_linked_id).find('.photo.clicked-photo-preview')
		newPhoto = previousPhoto[0].src

		$.ajax({
			url: document.URL + querySeparator + "action=unlinkDigiriskElementFile&token=" + token,
			type: "POST",
			data: JSON.stringify({
				digiriskelement_id: element_linked_id,
				filename: filename,
			}),
			processData: false,
			success: function ( ) {
				$('.wpeo-loader').removeClass('wpeo-loader')
				previousPhoto.attr('src',newPhoto)
				mediaContainer.hide()
				if (element_linked_id === currentElementID) {
					let digiriskBanner = $('.arearef.heightref')
					digiriskBanner.find('input[value="'+filename+'"]').siblings('').hide()
				}
			}
		});
	}

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
	var params = new window.URLSearchParams(window.location.search);
	var id = window.location.search.split(/id=/)[1]
	let element_linked_id = $(this).find('.element-linked-id').val()
	let filename = $(this).find('.filename').val()
	let querySeparator = '?'
	let mediaContainer = $(this).closest('.media-container')
	let modalFrom = $('.modal-risk.modal-active')
	let riskId = modalFrom.attr('value')
	let type = $(this).closest('.modal-container').find('.type-from').val()
	let previousPhoto = null
	let elementPhotos = ''

	//change star button style
	let previousFavorite = $(this).closest('.element-linked-medias').find('.fas.fa-star')
	let newFavorite = $(this).find('.far.fa-star')

	previousFavorite.removeClass('fas')
	previousFavorite.addClass('far')
	newFavorite.addClass('fas')
	newFavorite.removeClass('far')

	document.URL.match(/\?/) ? querySeparator = '&' : 1

	window.saturne.loader.display(mediaContainer);

	let token = $('.id-container.page-ut-gp-list').find('input[name="token"]').val();

	if (type === 'riskassessment') {
		previousPhoto = $(this).closest('.modal-container').find('.risk-evaluation-photo .clicked-photo-preview')
		elementPhotos = $('.risk-evaluation-photo-'+element_linked_id+'.risk-'+riskId)

		$(this).closest('.modal-content').find('.risk-evaluation-photo-single .filename').attr('value', filename)

		let filepath = modalFrom.find('.risk-evaluation-photo-single .filepath-to-riskassessment').val()
		let thumbName = window.saturne.file.getThumbName(filename)
		let newPhoto = filepath + thumbName

		let saveButton = $(this).closest('.modal-container').find('.risk-evaluation-save')
		saveButton.addClass('button-disable')
		$.ajax({
			url: document.URL + querySeparator + "action=addToFavorite&token=" + token,
			data: JSON.stringify({
				riskassessment_id: element_linked_id,
				filename: filename,
			}),
			type: "POST",
			processData: false,
			success: function ( ) {
				newPhoto = newPhoto.replace(/\ /g, '%20')
				newPhoto = newPhoto.replace(/\(/g, '%28')
				newPhoto = newPhoto.replace(/\)/g, '%29')
				newPhoto = newPhoto.replace(/\+/g, '%2B')

				elementPhotos.each( function() {
					$(this).find('.clicked-photo-preview').attr('src',newPhoto )
					$(this).find('.clicked-photo-preview').hasClass('photosaturne') ? $(this).find('.clicked-photo-preview').removeClass('photosaturne').addClass('photo') : 0
				});
				saveButton.removeClass('button-disable')
				$('.wpeo-loader').removeClass('wpeo-loader')
			}
		});
	} else if (type === 'digiriskelement') {
		previousPhoto = $('.digirisk-element-'+element_linked_id).find('.photo.clicked-photo-preview')

		let filepath =$('.digirisk-element-'+element_linked_id).find('.filepath-to-digiriskelement').val()
		let thumbName = window.saturne.file.getThumbName(filename)
		let newPhoto = filepath + thumbName

		jQuery.ajax({
			url: document.URL + querySeparator + "action=addDigiriskElementPhotoToFavorite&token=" + token,
			type: "POST",
			data: JSON.stringify({
				digiriskelement_id: element_linked_id,
				filename: filename,
			}),
			processData: false,
			success: function ( resp ) {

				if (id === element_linked_id) {
					$('.arearef.heightref.valignmiddle.centpercent').load(' .arearef.heightref.valignmiddle.centpercent')
				}
				newPhoto = newPhoto.replace(/\ /g, '%20')
				newPhoto = newPhoto.replace(/\(/g, '%28')
				newPhoto = newPhoto.replace(/\)/g, '%29')
				newPhoto = newPhoto.replace(/\+/g, '%2B')

				previousPhoto.attr('src',newPhoto )
				$('.wpeo-loader').removeClass('wpeo-loader')
			}
		});
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
	let id = 0

	if (typeFrom == 'photo_ok') {
		var files = $('#fast-upload-photo-ok').prop('files');
	} else if (typeFrom == 'photo_ko') {
		var files = $('#fast-upload-photo-ko').prop('files');
	} else if (typeFrom.match(/answer_photo/)) {
		id = typeFrom.split(/_photo/)[1]
		typeFrom = 'answer_photo'
		var files = $('#fast-upload-answer-photo'+id).prop('files');
	}
	window.saturne.mediaGallery.sendPhoto('', files, typeFrom, id)
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
	let offset = $(this).attr('value');
	$(this).closest('.wpeo-pagination').find('.pagination-element').removeClass('pagination-current');
	$(this).closest('.pagination-element').addClass('pagination-current');

	let elementParent = $('.modal-container').find('.ecm-photo-list-content');
	let querySeparator = '?';
	document.URL.match(/\?/) ? querySeparator = '&' : 1
	let token = $('.fiche').find('input[name="token"]').val();
	window.saturne.loader.display($('#media_gallery').find('.modal-content'));

	$.ajax({
		url: document.URL + querySeparator + "token=" + token + "&offset=" + offset,
		type: "POST",
		processData: false,
		contentType: false,
		success: function ( resp ) {
			$('.wpeo-loader').removeClass('wpeo-loader')
			elementParent.html($(resp).find('.ecm-photo-list-content'));
		},
		error: function ( ) {
		}
	})
};

