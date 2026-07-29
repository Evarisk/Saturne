/* Copyright (C) 2022-2026 EVARISK <technique@evarisk.com>
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
 */

/**
 * \file    js/modules/linkedObject.js
 * \ingroup saturne
 * \brief   JavaScript linkedObject file for module Saturne
 */

/**
 * Init linkedObject JS
 *
 * @memberof Saturne_Framework_Linkedobject
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @type {Object}
 */
window.saturne.linkedObject = {};

/**
 * LinkedObject init
 *
 * @memberof Saturne_Framework_Linkedobject
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void}
 */
window.saturne.linkedObject.init = function() {
  window.saturne.linkedObject.event();
};

/**
 * LinkedObject event
 *
 * @memberof Saturne_Framework_Linkedobject
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void}
 */
window.saturne.linkedObject.event = function() {
  $(document).on('click', '.linked-object-toggle', window.saturne.linkedObject.confirmToggle);
};

/**
 * Ask for confirmation before a destructive link change
 *
 * The message is rendered server side : an empty attribute means the action destroys nothing.
 *
 * @memberof Saturne_Framework_Linkedobject
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {Object}  event Triggered event
 * @returns {boolean}      False when the user cancels, true otherwise
 */
window.saturne.linkedObject.confirmToggle = function(event) {
  var confirmMessage = $(this).attr('data-confirm-message');

  if (!confirmMessage) {
    return true;
  }

  if (!window.confirm(confirmMessage)) {
    event.preventDefault();
    return false;
  }

  return true;
};
