/* Copyright (C) 2022-2023 EVARISK <dev@evarisk.com>
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
 * \file    js/modules/toolbox.js
 * \ingroup toolbox
 * \brief   JavaScript file toolbox for module Saturne.
 */

/**
 * Initialise l'objet "toolbox" ainsi que la méthode "init" obligatoire pour la bibliothèque Saturne.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.saturne.toolbox = {};

/**
 * La méthode appelée automatiquement par la bibliothèque Saturne.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.toolbox.init = function() {
};

/**
 * Returns suitable query separator
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {string}
 */
window.saturne.toolbox.getQuerySeparator = function( url ) {
	return url.match(/\?/) ? '&' : "?"
};


/**
 * Returns action security token
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {string}
 */
window.saturne.toolbox.getToken = function() {
	let token = $('input[name="token"]').val();

	return token
};


