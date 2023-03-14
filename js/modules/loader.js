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
 * \file    js/modules/loader.js
 * \ingroup loader
 * \brief   JavaScript file loader for module Saturne.
 */

/*
 * Gestion du loader.
 *
 * @since 1.0.0
 * @version 1.0.0
 */

/**
 * Initialise l'objet "loader" ainsi que la méthode "init" obligatoire pour la bibliothèque Saturne.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.saturne.loader = {};

/**
 * La méthode appelée automatiquement par la bibliothèque Saturne.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.loader.init = function() {
	window.saturne.loader.event();
};

/**
 * La méthode contenant tous les événements pour le loader.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.loader.event = function() {
};

/**
 * Shows loader on selected element
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.loader.display = function( element ) {
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
 * Removes loader on selected element
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.loader.remove = function( element ) {
	if ( 0 < element.length && ! element.hasClass( 'button-progress' ) ) {
		element.removeClass( 'wpeo-loader' );

		$( element[0].loaderElement ).remove();
	}
};
