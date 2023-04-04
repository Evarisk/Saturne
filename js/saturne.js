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
 * \file    js/saturne.js
 * \ingroup toolbox
 * \brief   JavaScript file for module Saturne.
 */

/* Javascript library of module Saturne */

/**
 * @namespace Saturne_Framework_Init
 *
 * @author Evarisk <technique@evarisk.com>
 * @copyright 2015-2023 Evarisk
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
