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
 * \file    js/modules/lazyUserSelect.js
 * \ingroup saturne
 * \brief   JavaScript completion of the user selects left empty by saturne_select_users()
 */

window.saturne.lazyUserSelect = {};

/**
 * lazyUserSelect init
 *
 * @since   23.0.0
 * @version 23.0.0
 * @return  {void}
 */
window.saturne.lazyUserSelect.init = function init() {
  window.saturne.lazyUserSelect.event();
};

/**
 * Bind the completion on the selects saturne_select_users() left with their preselected entry
 * only. Delegated, so selects added by an AJAX reload are covered too.
 *
 * A select whose id is shared by several dropdowns is not handled by ajax_combobox(), it stays
 * a plain <select> and opens on mousedown; a select2 one announces select2:opening before its
 * dropdown queries the options.
 *
 * @since   23.0.0
 * @version 23.0.0
 * @return  {void}
 */
window.saturne.lazyUserSelect.event = function event() {
  $(document).on('mousedown focus select2:opening', '.saturne-user-select-lazy', window.saturne.lazyUserSelect.fill);
};

/**
 * Copy the user list of the first select of the page into the one being opened.
 *
 * @since   23.0.0
 * @version 23.0.0
 * @return  {void}
 */
window.saturne.lazyUserSelect.fill = function fill() {
  const $select = $(this);
  if ($select.data('userListLoaded')) {
    return;
  }

  const $source = $('select.saturne-user-select-source').first();
  if (!$source.length) {
    return;
  }

  // Mark before filling: select2:opening fires again on the same select while it renders
  $select.data('userListLoaded', true);

  // The empty entry is the one of this select, its label may differ from the source's
  const selectedValue = $select.val();
  $select.find('option').not('[value="-1"]').remove();

  $source.find('option').not('[value="-1"]').each(function appendOption() {
    $select.append($(this).clone());
  });

  $select.val(selectedValue);
};
