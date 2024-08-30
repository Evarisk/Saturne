/* Copyright (C) 2022-2024 EVARISK <technique@evarisk.com>
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
 * \brief   JavaScript keyEvents file for module Saturne
 */


/**
 * Init keyEvents JS
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.saturne.keyEvent = {};

/**
 * keyEvents init
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
 * keyEvents event
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.keyEvent.event = function() {
  $(document).on( 'keydown', window.saturne.keyEvent.keyActions);
  $(document).on( 'keyup', '.url-container' , window.saturne.keyEvent.checkUrlFormat);
};

/**
 * Key events action
 *
 * @since   1.0.0
 * @version 1.4.0
 *
 * @return {void}
 */
window.saturne.keyEvent.keyActions = function(event) {
  if ($(this).find('.modal-active').length > 0) {
    // Modal key events
    if ('Escape' === event.key) {
      $(this).find('.modal-active .modal-close .fas.fa-times').first().click();
    }

    if ('Enter' === event.key) {
      if (!$('input, textarea').is(':focus')) {
        $(this).find('.modal-active .modal-footer .wpeo-button').not('.button-disable').first().click();
      }
    }
  } else {
    // List key events
    if (!$(event.target).is('input, textarea')) {
      if ('Enter' === event.key) {
        $(this).find('.button_search').click();
      }
      if (event.shiftKey && 'Enter' === event.key) {
        $(this).find('.button_removefilter').click();
      }
    }
  }

  if ($(this).find('.card__confirmation').length > 0) {
    if ('Escape' === event.key) {
      $(this).find('.confirmation-close-button.confirmation-close .fas.fa-times').click();
    }
  }
};

/**
 * Check url format of url containers
 *
 * @since   1.0.0
 * @version 1.4.0
 *
 * @return {void}
 */
window.saturne.keyEvent.checkUrlFormat = function() {
  const urlRegex = /[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&//=]*)?/gi;
  if ($(this).val().match(urlRegex)) {
    $(this).attr('style', 'border: solid; border-color: green');
  } else if ($('input:focus').val().length > 0) {
    $(this).attr('style', 'border: solid; border-color: red');
  }
};
