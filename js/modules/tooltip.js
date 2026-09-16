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
 * \file    js/modules/tooltip.js
 * \ingroup saturne
 * \brief   JavaScript file tooltip for module Saturne.
 */

/**
 * @namespace EO_Framework_Tooltip
 *
 * @author Eoxia <dev@eoxia.com>
 * @copyright 2015-2018 Eoxia
 */

if ( ! window.saturne.tooltip ) {

	/**
	 * [tooltip description]
	 *
	 * @memberof EO_Framework_Tooltip
	 *
	 * @type {Object}
	 */
	window.saturne.tooltip = {};

	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Tooltip
	 *
	 * @returns {void} [description]
	 */
	window.saturne.tooltip.init = function() {
		window.saturne.tooltip.event();
	};

	window.saturne.tooltip.tabChanged = function() {
		$( '.wpeo-tooltip' ).remove();
	};

	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Tooltip
	 *
	 * @returns {void} [description]
	 */
	window.saturne.tooltip.event = function() {
		$( document ).on( 'mouseenter touchstart', '.wpeo-tooltip-event:not([data-tooltip-persist="true"])', window.saturne.tooltip.onEnter );
		$( document ).on( 'mouseleave touchend', '.wpeo-tooltip-event:not([data-tooltip-persist="true"])', window.saturne.tooltip.onOut );
	};

	window.saturne.tooltip.onEnter = function() {
		window.saturne.tooltip.display( $( this ) );
	};

	window.saturne.tooltip.onOut = function() {
		window.saturne.tooltip.remove( $( this ) );
	};

	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Tooltip
	 *
	 * @param  {void} event [description]
	 * @returns {void}       [description]
	 */
	window.saturne.tooltip.display = function( element ) {
		var direction = ( $( element ).data( 'direction' ) ) ? $( element ).data( 'direction' ) : 'top';
		var label = $( element ).attr( 'aria-label' );
		var el = $( '<span class="wpeo-tooltip tooltip-' + direction + '">' + label + '</span>' );
		var offset = $( element ).offset();
		$( element )[0].tooltipElement = el;
		$( 'body' ).append( $( element )[0].tooltipElement );

		if ( $( element ).data( 'color' ) ) {
			el.addClass( 'tooltip-' + $( element ).data( 'color' ) );
		}

		// Un label qui porte déjà des sauts de ligne, ou qui demande explicitement la variante,
		// s'affiche sur plusieurs lignes : la règle par défaut le sortirait de la fenêtre et
		// n'en montrerait que la première ligne. La classe est posée avant le calcul de la
		// position, qui mesure la hauteur et la largeur réelles de l'infobulle.
		if ( $( element ).data( 'multiline' ) || /[\r\n]/.test( label ) ) {
			el.addClass( 'tooltip-multiline' );
		}

		var top = 0;
		var left = 0;

		switch( $( element ).data( 'direction' ) ) {
			case 'left':
				top = ( offset.top - ( el.outerHeight() / 2 ) + ( $( element ).outerHeight() / 2 ) ) + 'px';
				left = ( offset.left - el.outerWidth() - 10 ) + 3 + 'px';
				break;
			case 'right':
				top = ( offset.top - ( el.outerHeight() / 2 ) + ( $( element ).outerHeight() / 2 ) ) + 'px';
				left = offset.left + $( element ).outerWidth() + 8 + 'px';
				break;
			case 'bottom':
				top = ( offset.top + $( element ).height() + 10 ) + 10 + 'px';
				left = ( offset.left - ( el.outerWidth() / 2 ) + ( $( element ).outerWidth() / 2 ) ) + 'px';
				break;
			case 'top':
				top = offset.top - el.outerHeight() - 4  + 'px';
				left = ( offset.left - ( el.outerWidth() / 2 ) + ( $( element ).outerWidth() / 2 ) ) + 'px';
				break;
			default:
				top = offset.top - el.outerHeight() - 4  + 'px';
				left = ( offset.left - ( el.outerWidth() / 2 ) + ( $( element ).outerWidth() / 2 ) ) + 'px';
				break;
		}

		// La position est calculée à partir du seul élément survolé, sans tenir compte des bords
		// de la fenêtre : une infobulle large, ou multiligne donc plus haute, en sortait et
		// devenait illisible. On la ramène dedans, celles qui y tenaient déjà ne bougent pas.
		var margin  = 4;
		var minLeft = $( window ).scrollLeft() + margin;
		var maxLeft = minLeft + $( window ).width() - el.outerWidth() - ( margin * 2 );
		var minTop  = $( window ).scrollTop() + margin;

		top  = Math.max( parseFloat( top ), minTop ) + 'px';
		left = Math.max( Math.min( parseFloat( left ), maxLeft ), minLeft ) + 'px';

		el.css( {
			'top': top,
			'left': left,
			'opacity': 1
		} );

		$( element ).on("remove", function() {
			$( $( element )[0].tooltipElement ).remove();

		} );
	};

	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Tooltip
	 *
	 * @param  {void} event [description]
	 * @returns {void}       [description]
	 */
	window.saturne.tooltip.remove = function( element ) {
		if ( $( element )[0] && $( element )[0].tooltipElement ) {
			$( $( element )[0].tooltipElement ).remove();
		}
	};
}
