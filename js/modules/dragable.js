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
 * \file    js/modules/dragable.js
 * \ingroup saturne
 * \brief   JavaScript file dragable for module Saturne.
 */


/**
 * Initialise l'objet "dragable" ainsi que la méthode "init" obligatoire pour la bibliothèque Saturne.
 *
 * @since   1.7.0
 * @version 1.7.0
 */
window.saturne.dragable = {};

/**
 * La méthode appelée automatiquement par la bibliothèque Saturne.
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @return {void}
 */
window.saturne.dragable.init = function() {
  window.saturne.dragable.event();
};

/**
 * La méthode contenant tous les événements pour les documents.
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @return {void}
 */
window.saturne.dragable.event = function() {
  $(document).on('dragstart', '#draggableTable tr', window.saturne.dragable.start);
  $(document).on('dragover', '#draggableTable tr', window.saturne.dragable.over);
  $(document).on('dragend', '#draggableTable tr', window.saturne.dragable.end);
  $(document).on('click', '#dragableSubmit', window.saturne.dragable.submit);
};

window.saturne.dragable.draggedRow = null;

window.saturne.dragable.start = function(e) {
  window.saturne.dragable.draggedRow = e.target;
  e.target.style.opacity = '0.5';
}

window.saturne.dragable.over = function(e) {
  e.preventDefault();
  const targetRow = e.target.closest('tr[draggable]');
  if (targetRow && targetRow !== window.saturne.dragable.draggedRow) {
    const boundingRect = targetRow.getBoundingClientRect();
    const offsetY = e.clientY - boundingRect.top;
    if (offsetY > targetRow.offsetHeight / 2) {
      targetRow.parentNode.insertBefore(window.saturne.dragable.draggedRow, targetRow.nextSibling);
    } else {
      targetRow.parentNode.insertBefore(window.saturne.dragable.draggedRow, targetRow);
    }
  }
}

window.saturne.dragable.end = function(e) {
  if (window.saturne.dragable.draggedRow) {
    window.saturne.dragable.draggedRow.style.opacity = '';
    window.saturne.dragable.draggedRow = null;
  }
}

window.saturne.dragable.submit = function(e) {
  e.preventDefault();
  const table = $('#draggableTable');
  const rows = table.find('tr');

  const data = [];
  rows.each(function() {
    if ($(this).data('name')) {
      data.push($(this).data('name'));
    }
  });

  window.saturne.loader.display($(e.target));

  let token = window.saturne.toolbox.getToken();
  let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);
  $.ajax({
    url: document.URL + querySeparator + 'action=dragableSubmit&token=' + token,
    method: 'POST',
    data: JSON.stringify(data),
    processData: false,
    contentType: false,
    success: function(response) {
      window.saturne.loader.remove($(e.target));
    },
    error: function(response) {
      window.saturne.loader.remove($(e.target));
    }
  });

}
