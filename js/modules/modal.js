/* Copyright (C) 2023 EVARISK <dev@evarisk.com>
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
 * \file    js/modules/modal.js
 * \ingroup modal
 * \brief   JavaScript file for module Saturne.
 */

/**
 * Initialise l'objet "modal" ainsi que la méthode "init" obligatoire pour la bibliothèque Saturne.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.saturne.modal = {};

/**
 * La méthode appelée automatiquement par la bibliothèque Saturne.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.modal.init = function() {
	window.saturne.modal.event();
};

/**
 * La méthode contenant tous les événements pour la modal.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.modal.event = function() {
	$( document ).on( 'click', '.modal-close', window.saturne.modal.closeModal );
	$( document ).on( 'click', '.modal-open', window.saturne.modal.openModal );
	$( document ).on( 'click', '.modal-refresh', window.saturne.modal.refreshModal );
};

/**
 * Open Modal.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.saturne.modal.openModal = function ( event ) {
	let idSelected = $(this).attr('value');
	if (document.URL.match(/#/)) {
		var urlWithoutTag = document.URL.split(/#/)[0]
	} else {
		var urlWithoutTag = document.URL
	}
	history.pushState({ path:  document.URL}, '', urlWithoutTag);

	// Open modal media gallery.
	if ($(this).hasClass('open-media-gallery')) {
		$('#media_gallery').addClass('modal-active');
		$('#media_gallery').attr('value', idSelected);
		$('#media_gallery').find('.from-id').attr('value', $(this).find('.from-id').val());
		$('#media_gallery').find('.from-type').attr('value', $(this).find('.from-type').val());
		$('#media_gallery').find('.from-subtype').attr('value', $(this).find('.from-subtype').val());
		$('#media_gallery').find('.from-subdir').attr('value', $(this).find('.from-subdir').val());
		$('#media_gallery').find('.wpeo-button').attr('value', idSelected);
	}

	// Open modal patch note.
	if ($(this).hasClass('show-patchnote')) {
		$('.fiche .wpeo-modal-patchnote').addClass('modal-active');
	}

	$('.notice').addClass('hidden');
};

/**
 * Close Modal.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.saturne.modal.closeModal = function ( event ) {
	if ($(event.target).hasClass('modal-active') || $(event.target).hasClass('modal-close') || $(event.target).parent().hasClass('modal-close')) {
		$(this).closest('.modal-active').removeClass('modal-active')
		$('.clicked-photo').attr('style', '');
		$('.clicked-photo').removeClass('clicked-photo');
		$('.notice').addClass('hidden');
	}
};

/**
 * Refresh Modal.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.saturne.modal.refreshModal = function ( event ) {
	window.location.reload();
};
