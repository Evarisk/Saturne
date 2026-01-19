/* Copyright (C) 2022-2025 EVARISK <technique@evarisk.com>
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
 * \file    js/modules/menu.js
 * \ingroup saturne
 * \brief   JavaScript menu file
 */

'use strict';

/**
 * Init menu JS
 *
 * @since   1.0.0
 * @version 22.0.0
 */
window.saturne.menu = {};

/**
 * Menu init
 *
 * @since   1.0.0
 * @version 22.0.0
 *
 * @return {void}
 */
window.saturne.menu.init = function init() {
  window.saturne.menu.event();
  window.saturne.menu.setMenu();
};

/**
 * Menu event initialization. Binds all necessary event listeners
 *
 * @since   1.0.0
 * @version 22.0.0
 *
 * @return {void}
 */
window.saturne.menu.event = function initializeEvents() {};

/**
 * Action set menu
 *
 * @since   8.5.0
 * @version 22.0.0
 *
 * @return {void}
 */
window.saturne.menu.setMenu = function setMenu() {
  const menuLeft        = $('#id-left .vmenu');
  const menuElement     = menuLeft.find('a.vmenu, span.vmenudisabled, span.vmenu, a.vsmenu, a.help');
  const minimizeElement = menuElement.find('.saturne-toggle-menu');
  const searchBox       = menuLeft.find('.blockvmenusearch');

  // If there is no minimize element, exit
  if (minimizeElement.length === 0) {
    return;
  }

  minimizeElement.closest('.blockvmenulast').css('cursor', 'pointer');

  const minimizeMenu = () => {
    menuElement.each(function () {
      $(this).contents().filter(function() {
        return this.nodeType === 3;
      }).wrap('<span class="hidden-text" style="display:none"></span>');
    });

    searchBox.slideUp(200);
    menuLeft.animate({ width: '30px' }, 200);

    minimizeElement.attr('title', 'Agrandir le menu');
    minimizeElement.removeClass('fa-chevron-circle-left').addClass('fa-chevron-circle-right');

    localStorage.setItem('maximized', 'false');
  };

  const maximizeMenu = () => {
    menuElement.each(function () {
      $(this).find('span.hidden-text').contents().unwrap();
    });

    searchBox.slideDown(200);
    menuLeft.animate({ width: '240px' }, 200);

    minimizeElement.attr('title', 'Réduire le menu');
    minimizeElement.removeClass('fa-chevron-circle-right').addClass('fa-chevron-circle-left');

    localStorage.setItem('maximized', 'true');
  };

  if (localStorage.maximized === 'false' && menuLeft.width() > 50) {
    minimizeMenu();
  }

  // Toggle au clic
  minimizeElement.closest('.blockvmenulast').off('click').on('click', function () {
    if (menuLeft.width() > 50) {
      minimizeMenu();
    } else {
      maximizeMenu();
    }
  });
};
