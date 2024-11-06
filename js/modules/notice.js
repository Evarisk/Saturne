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

"use strict"

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
 * Show notice
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @returns {void}
 */
window.saturne.notice.showNotice = function (id, title, message, type) {
  const notice = $('#' + id);
  const inputs = notice.find('input');

  // Remove all default classes notice provided by Saturne CSS
  notice.removeClass('notice-error');
  notice.removeClass('notice-info');
  notice.removeClass('notice-success');
  notice.removeClass('notice-warning');

  notice.addClass('notice-' + type);

  // Use hidden input to translate title and message
  inputs.each(function (index, element) {
    let name = $(element).attr('name');
    let val  = $(element).val();
    title = title.replace(new RegExp(name, 'g'), val);
    message = message.replace(new RegExp(name, 'g'), val);
  });

  notice.find('.notice-title').html(title);
  notice.find('.notice-message').html(message);

  notice.removeClass('hidden');
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
    $(this).addClass('hidden');
    $(this).css('display', '');
  });

  if ($(this).hasClass('notice-close-forever')) {
    window.saturne.utils.reloadPage('close_notice', '.fiche');
  }
};
