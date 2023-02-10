
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
	}

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

	window.saturne.tooltip.onEnter = function( event ) {
		window.saturne.tooltip.display( $( this ) );
	};

	window.saturne.tooltip.onOut = function( event ) {
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
		var el = $( '<span class="wpeo-tooltip tooltip-' + direction + '">' + $( element ).attr( 'aria-label' ) + '</span>' );
		var pos = $( element ).position();
		var offset = $( element ).offset();
		$( element )[0].tooltipElement = el;
		$( 'body' ).append( $( element )[0].tooltipElement );

		if ( $( element ).data( 'color' ) ) {
			el.addClass( 'tooltip-' + $( element ).data( 'color' ) );
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
