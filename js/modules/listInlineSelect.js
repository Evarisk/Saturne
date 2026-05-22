/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    js/modules/listInlineSelect.js
 * \ingroup saturne
 * \brief   JavaScript inline <select> editing inside list cells
 */

'use strict';

window.saturne.listInlineSelect = {};

/**
 * listInlineSelect init
 *
 * @since   23.0.0
 * @version 23.0.0
 * @return  {void}
 */
window.saturne.listInlineSelect.init = function init() {
  window.saturne.listInlineSelect.event();
};

/**
 * Bind the change handler on inline selects (delegated, survives AJAX list reloads).
 *
 * @since   23.0.0
 * @version 23.0.0
 * @return  {void}
 */
window.saturne.listInlineSelect.event = function event() {
  $(document).on('change', '.saturne-inline-select', window.saturne.listInlineSelect.onChange);
};

/**
 * On change: persist the new value through the generic saturne_update_field endpoint.
 *
 * @since   23.0.0
 * @version 23.0.0
 * @return  {void}
 */
window.saturne.listInlineSelect.onChange = function onChange() {
  const $el = $(this);
  const $td = $el.closest('td');

  $el.prop('disabled', true).addClass('ce-saving');

  $.ajax({
    url: (window.saturne.config && window.saturne.config.urlRoot ? window.saturne.config.urlRoot : '') + '/custom/saturne/core/ajax/saturne_update_field.php',
    method: 'POST',
    data: {
      action:     'update_field',
      token:      window.saturne.toolbox.getToken(),
      field:      $el.data('field'),
      element:    $el.data('element'),
      fk_element: $el.data('id'),
      type:       'select',
      fieldValue: $el.val()
    }
  })
    .done(function() {
      window.saturne.listInlineSelect.flash($td, true);
    })
    .fail(function() {
      window.saturne.listInlineSelect.flash($td, false);
    })
    .always(function() {
      $el.prop('disabled', false).removeClass('ce-saving');
    });
};

/**
 * Flash the cell green (success) or red (error), reusing the contentEditable feedback classes.
 *
 * @since   23.0.0
 * @version 23.0.0
 *
 * @param  {jQuery}  $td   The cell to flash
 * @param  {boolean} isOk  Whether the save succeeded
 * @return {void}
 */
window.saturne.listInlineSelect.flash = function flash($td, isOk) {
  $td.removeClass('ce-valid ce-invalid');
  $td[0].offsetWidth;
  $td.addClass(isOk ? 'ce-valid' : 'ce-invalid');
  setTimeout(function() {
    $td.removeClass('ce-valid ce-invalid');
  }, 1500);
};
