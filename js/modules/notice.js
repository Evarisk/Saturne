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
 * \file    js/modules/media.js
 * \ingroup saturne
 * \brief   JavaScript notice file for module Saturne
 */

/**
 * Init notice JS
 *
 * @since   1.2.0
 * @version 1.2.0
 */
window.saturne.notice = {};

/**
 * Notice init
 *
 * @since   1.2.0
 * @version 1.2.0
 *
 * @return {void}
 */
window.saturne.notice.init = function() {
  window.saturne.notice.event();
};

/**
 * Notice event
 *
 * @since   1.2.0
 * @version 1.2.0
 *
 * @return {void}
 */
window.saturne.notice.event = function() {
  $(document).on('click', '.notice-close', window.saturne.notice.closeNotice);
};

/**
 * Close notice on click
 *
 * @since   1.2.0
 * @version 1.2.0
 *
 * @return {void}
 */
window.saturne.notice.closeNotice = function() {
  $(this).closest('.wpeo-notice').fadeOut(function () {
    $(this).closest('.wpeo-notice').addClass('hidden');
  });

  if ($(this).hasClass('notice-close-forever')) {
    window.saturne.utils.reloadPage('close_notice', '.fiche');
  }
};
