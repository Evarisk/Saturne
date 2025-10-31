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
 * \file    js/modules/contentEditable.js
 * \ingroup saturne
 * \brief   JavaScript contentEditable file
 */

'use strict';

/**
 * Init contentEditable JS
 *
 * @since   22.0.0
 * @version 22.0.0
 */
window.saturne.contentEditable = {};

/**
 * contentEditable init
 *
 * @since   22.0.0
 * @version 22.0.0
 *
 * @return {void}
 */
window.saturne.contentEditable.init = function init() {
  window.saturne.contentEditable.event();
};

/**
 * contentEditable event initialization. Binds all necessary event listeners
 *
 * @since   22.0.0
 * @version 22.0.0
 *
 * @return {void}
 */
window.saturne.contentEditable.event = function initializeEvents() {
  // Bind tous les events via délégation
  $(document)
    .on('blur', '.contenteditable', window.saturne.contentEditable.onBlur)
    .on('focus', '.contenteditable', window.saturne.contentEditable.onFocus)
    .on('keydown', '.contenteditable', window.saturne.contentEditable.onKeyDown)
    .on('mouseenter', '.contenteditable', window.saturne.contentEditable.onMouseEnter);
};

/**
 * Gestion du blur (validation + AJAX)
 *
 * @since   22.0.0
 * @version 22.0.0
 */
window.saturne.contentEditable.onBlur = function() {
  const $el = $(this);
  const value = $.trim($el.text());
  const parsed = window.saturne.utils.parseDateTime(value);

  if (parsed) {
    $el.text(window.saturne.utils.formatDateTime(parsed))
      .removeClass('invalid');

    $.ajax({
      url: '/dolibarr/htdocs/core/ajax/saveinplace.php',
      method: 'POST',
      data: {
        field: $el.data('field'),
        element: 'trainingsession',
        table_element: 'dolimeet_session',
        fk_element: $el.data('id'),
        type: 'datepicker',
        timestamp: Math.floor(parsed.getTime())
      }
    });
  } else {
    $el.addClass('invalid');
  }
};

/**
 * Gestion du focus (effet visuel)
 *
 * @since   22.0.0
 * @version 22.0.0
 */
window.saturne.contentEditable.onFocus = function() {
  $(this).removeClass('invalid').addClass('active');
};

/**
 * Gestion des flèches haut/bas pour changer la date
 *
 * @since   22.0.0
 * @version 22.0.0
 */
window.saturne.contentEditable.onKeyDown = function(e) {
  if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
    e.preventDefault();
    const $el = $(this);
    const current = window.saturne.utils.parseDateTime($.trim($el.text())) || new Date();
    const delta = e.key === 'ArrowUp' ? 1 : -1;
    current.setDate(current.getDate() + delta);
    $el.text(window.saturne.utils.formatDateTime(current));
  }
};

/**
 * Exemple d’action sur survol
 *
 * @since   22.0.0
 * @version 22.0.0
 */
window.saturne.contentEditable.onMouseEnter = function() {
  $(this).css('cursor', 'text');
};
