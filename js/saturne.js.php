/* Javascript library of module Saturne */

/**
 * @namespace Saturne_Framework_Init
 *
 * @author Evarisk <dev@evarisk.com>
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

<?php

require_once './modules/modal.js';
require_once './modules/loader.js';
require_once './modules/mediaGallery.js';
require_once './modules/keyEvents.js';
