/* Copyright (C) 2024 EVARISK <technique@evarisk.com>
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
 * \file    js/modules/object.js
 * \ingroup saturne
 * \brief   JavaScript object file for module Saturne
 */

/**
 * Init object JS
 *
 * @memberof Saturne_Object
 *
 * @since   1.2.1
 * @version 1.2.1
 *
 * @type {Object}
 */
window.saturne.object = {};

/**
 * Object init
 *
 * @memberof Saturne_Object
 *
 * @since   1.2.1
 * @version 1.2.1
 *
 * @returns {void}
 */
window.saturne.object.init = function() {
  window.saturne.object.event();
};

/**
 * Object event
 *
 * @memberof Saturne_Object
 *
 * @since   1.2.1
 * @version 1.2.1
 *
 * @returns {void}
 */
window.saturne.object.event = function() {
  $(document).on('click', '.toggle-object-infos', window.saturne.object.toggleObjectInfos);
};

/**
 * Show object infos if toggle is on
 *
 * @since   1.2.1
 * @version 1.2.1
 *
 * @return {void}
 */
window.saturne.object.toggleObjectInfos = function() {
  if ($(this).hasClass('fa-minus-square')) {
    $(this).removeClass('fa-minus-square').addClass('fa-caret-square-down');
    $(this).closest('.fiche').find('.fichecenter.object-infos').addClass('hidden');
  } else {
    $(this).removeClass('fa-caret-square-down').addClass('fa-minus-square');
    $(this).closest('.fiche').find('.fichecenter.object-infos').removeClass('hidden');
  }
};
