/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
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
 * \file    js/modules/saturneElement.js
 * \ingroup saturne
 * \brief   JavaScript saturneElement file
 */

'use strict';

/**
 * Init saturneElement JS
 *
 * @since   21.1.0
 * @version 21.1.0
 */
window.saturne.saturneElement = {};

/**
 * SaturneElement init
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @return {void}
 */
window.saturne.saturneElement.init = function init() {
  window.saturne.saturneElement.event();
};

/**
 * SaturneElement event initialization. Binds all necessary event listeners
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @return {void}
 */
window.saturne.saturneElement.event = function initializeEvents() {
  $(document).on( 'click', '.toggle-unit', window.saturne.saturneElement.switchToggle);
};

/**
 * Navigation toggle switch handler
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @return {void}
 */
window.saturne.saturneElement.switchToggle = function switchToggle() {
  const $this       = $(this);
  const toggledIcon = $this.find('.toggle-icon');
  const $unit       = $this.closest('.unit');
  const objectId    = $unit.data('object-id');

  let menu = new Set(JSON.parse(localStorage.getItem('menu') || '[]'));

  if (toggledIcon.hasClass('fa-chevron-down')) {
    toggledIcon.toggleClass('fa-chevron-down fa-chevron-right');
    $unit.removeClass('toggled');
    menu.delete(objectId);
  } else if (toggledIcon.hasClass( 'fa-chevron-right')) {
    toggledIcon.toggleClass('fa-chevron-right fa-chevron-down');
    $unit.addClass('toggled');
    menu.add(objectId);
  }

  localStorage.setItem('menu', JSON.stringify(Array.from(menu)));
};
