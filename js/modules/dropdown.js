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
 * \file    js/modules/dropdown.js
 * \ingroup saturne
 * \brief   JavaScript file dropdown for module Saturne.
 */


/*
 * Gestion du dropdown.
 *
 * @since   1.0.0
 * @version 1.0.0
 */

/**
 * Initialise l'objet "dropdown" ainsi que la méthode "init" obligatoire pour la bibliothèque Saturne.
 *
 * @memberof Saturne_Framework_Dropdown
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void} [description]
 */
window.saturne.dropdown = {};

/**
 * La méthode appelée automatiquement par la bibliothèque Saturne.
 *
 * @memberof Saturne_Framework_Dropdown
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void} [description]
 */
window.saturne.dropdown.init = function() {
    window.saturne.dropdown.event();
};

/**
 * La méthode contenant tous les événements pour la dropdown.
 *
 * @memberof Saturne_Framework_Dropdown
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void} [description]
 */
window.saturne.dropdown.event = function() {
    $(document).on('keyup', window.saturne.dropdown.keyup);
    $(document).on('keypress', window.saturne.dropdown.keypress);
    $(document).on('click', '.wpeo-dropdown:not(.dropdown-active) .dropdown-toggle:not(.disabled)', window.saturne.dropdown.open);
    $(document).on('click', '.wpeo-dropdown.dropdown-active .saturne-dropdown-content', function(e) {e.stopPropagation()});
    $(document).on('click', '.wpeo-dropdown.dropdown-active:not(.dropdown-force-display) .saturne-dropdown-content .dropdown-item', window.saturne.dropdown.close );
    $(document).on('click', '.wpeo-dropdown.dropdown-active', function (e) {window.saturne.dropdown.close(e); e.stopPropagation();});
    $(document).on('click', 'body', window.saturne.dropdown.close);
};

/**
 * [description]
 *
 * @memberof Saturne_Framework_Dropdown
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {void} event [description]
 * @returns {void}       [description]
 */
window.saturne.dropdown.keyup = function(event) {
    if ( 27 === event.keyCode ) {
        window.saturne.dropdown.close();
    }
};

/**
 * Do a barrel roll!
 *
 * @memberof Saturne_Framework_Dropdown
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {void} event [description]
 * @returns {void}       [description]
 */
window.saturne.dropdown.keypress = function( event ) {

    let currentString  = localStorage.currentString ? localStorage.currentString : ''
    let keypressNumber = localStorage.keypressNumber ? +localStorage.keypressNumber : 0

    currentString += event.keyCode
    keypressNumber += +1

    localStorage.setItem('currentString', currentString)
    localStorage.setItem('keypressNumber', keypressNumber)

    if (keypressNumber > 9) {
        localStorage.setItem('currentString', '')
        localStorage.setItem('keypressNumber', 0)
    }

    if (currentString === '9897114114101108114111108108') {
        var a="-webkit-",
            b='transform:rotate(1turn);',
            c='transition:4s;';

        document.head.innerHTML += '<style>body{' + a + b + a + c + b + c
    }
};

/**
 * [description]
 *
 * @memberof Saturne_Framework_Dropdown
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {void} event [description]
 * @returns {void}       [description]
 */
window.saturne.dropdown.open = function( event ) {
    var triggeredElement = $( this );
    var angleElement = triggeredElement.find('[data-fa-i2svg]');
    var callbackData = {};
    var key = undefined;

    window.saturne.dropdown.close( event, $( this ) );

    if ( triggeredElement.attr( 'data-action' ) ) {
        window.saturne.loader.display( triggeredElement );

        triggeredElement.get_data( function( data ) {
            for ( key in callbackData ) {
                if ( ! data[key] ) {
                    data[key] = callbackData[key];
                }
            }

            window.saturne.request.send( triggeredElement, data, function( element, response ) {
                triggeredElement.closest( '.wpeo-dropdown' ).find( '.saturne-dropdown-content' ).html( response.data.view );

                triggeredElement.closest( '.wpeo-dropdown' ).addClass( 'dropdown-active' );

                /* Toggle Button Icon */
                if ( angleElement ) {
                    window.saturne.dropdown.toggleAngleClass( angleElement );
                }
            } );
        } );
    } else {
        triggeredElement.closest( '.wpeo-dropdown' ).addClass( 'dropdown-active' );

        /* Toggle Button Icon */
        if ( angleElement ) {
            window.saturne.dropdown.toggleAngleClass( angleElement );
        }
    }

    event.stopPropagation();
};

/**
 * [description]
 *
 * @memberof Saturne_Framework_Dropdown
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {void} event [description]
 * @returns {void}       [description]
 */
window.saturne.dropdown.close = function( event ) {
    var _element = $( this );
    $( '.wpeo-dropdown.dropdown-active:not(.no-close)' ).each( function() {
        var toggle = $( this );
        var triggerObj = {
            close: true
        };

        _element.trigger( 'dropdown-before-close', [ toggle, _element, triggerObj ] );

        if ( triggerObj.close ) {
            toggle.removeClass( 'dropdown-active' );

            /* Toggle Button Icon */
            var angleElement = $( this ).find('.dropdown-toggle').find('[data-fa-i2svg]');
            if ( angleElement ) {
                window.saturne.dropdown.toggleAngleClass( angleElement );
            }
        } else {
            return;
        }
    });
};

/**
 * [description]
 *
 * @memberof Saturne_Framework_Dropdown
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {jQuery} button [description]
 * @returns {void}        [description]
 */
window.saturne.dropdown.toggleAngleClass = function( button ) {
    if ( button.hasClass('fa-caret-down') || button.hasClass('fa-caret-up') ) {
        button.toggleClass('fa-caret-down').toggleClass('fa-caret-up');
    }
    else if ( button.hasClass('fa-caret-circle-down') || button.hasClass('fa-caret-circle-up') ) {
        button.toggleClass('fa-caret-circle-down').toggleClass('fa-caret-circle-up');
    }
    else if ( button.hasClass('fa-angle-down') || button.hasClass('fa-angle-up') ) {
        button.toggleClass('fa-angle-down').toggleClass('fa-angle-up');
    }
    else if ( button.hasClass('fa-chevron-circle-down') || button.hasClass('fa-chevron-circle-up') ) {
        button.toggleClass('fa-chevron-circle-down').toggleClass('fa-chevron-circle-up');
    }
};
