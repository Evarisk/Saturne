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
 * \file    js/modules/keyEvents.js
 * \ingroup saturne
 * \brief   JavaScript file keyEvents for module Saturne.
 */


/**
 * Initialise l'objet "keyEvent" ainsi que la méthode "init" obligatoire pour la bibliothèque Saturne.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.saturne.keyEvent = {};

/**
 * La méthode appelée automatiquement par la bibliothèque Saturne.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.keyEvent.init = function() {
	window.saturne.keyEvent.event();
};

/**
 * La méthode contenant tous les événements pour le migration.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.keyEvent.event = function() {
	$( document ).on( 'keydown', window.saturne.keyEvent.modalActions );
	$( document ).on( 'keyup', '.url-container' , window.saturne.keyEvent.checkUrlFormat );
	$( document ).on( 'keydown', window.saturne.keyEvent.buttonActions );
}

/**
 * Action modal close & validation with key events
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.keyEvent.modalActions = function( event ) {
	if ( 'Escape' === event.key  ) {
		$(this).find('.modal-active .modal-close .fas.fa-times').first().click();
	}

	if ( 'Enter' === event.key )  {
    if (!$('input, textarea').is(':focus')) {
			$(this).find('.modal-active .modal-footer .wpeo-button').not('.button-disable').first().click();
		}
	}
};

/**
 * Check url format of url containers
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.keyEvent.checkUrlFormat = function( event ) {
	var urlRegex = /[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&//=]*)?/gi;
	if ($(this).val().match(urlRegex)) {
		$(this).attr('style', 'border: solid; border-color: green')
	} else if ($('input:focus').val().length > 0) {
		$(this).attr('style', 'border: solid; border-color: red')
	}
};

/**
 * Action save & cancel with key events
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.keyEvent.buttonActions = function( event ) {
	if ( 'Escape' === event.key  ) {
		$(this).find('.button-cancel').click();
	}

	if ( 'Enter' === event.key )  {
		if (!$('input, textarea').is(':focus')) {
			$(this).find('.button-add').click();
		}
	}

	if (!$(event.target).is('input, textarea')) {
		if ('Enter' === event.key)  {
			$(this).find('.button_search').click();
		}
		if (event.shiftKey && 'Enter' === event.key)  {
			$(this).find('.button_removefilter').click();
		}
	}
};
