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
 * @since   1.0.0
 * @version 1.0.0
 */
window.saturne.dragable = {};

/**
 * La méthode appelée automatiquement par la bibliothèque Saturne.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.dragable.init = function() {
	window.saturne.dragable.event();
};

/**
 * La méthode contenant tous les événements pour les dragables.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.dragable.event = function() {

  if (!$('.dragable-container').length) {
    return;
  }

  $('.dragable-container').sortable({
    items: '.dragable-item',
    connectedWith: '.dragable-container',
    cursor: 'grabbing',
    opacity: 0.6,
    tolerence: 'pointer',
    start: function(event, ui) {
        ui.placeholder.height(ui.item.outerHeight());
    },
    update: function(event, ui) {
      window.saturne.dragable.submit.call(this);
    }
  });

};

window.saturne.dragable.submit = function() {
  $this = $(this);
  $container = $this.closest('.dragable-container');

  let action   = $container.data('action') || 'dragableSubmit';
  let loader   = $container.data('loader') || 'default';
  let elements = $container.find('.dragable-item').map(function() {
    return $(this).data('name');
  }).toArray();

  if (loader !== 'none') {
    window.saturne.loader.display($container);
  }

  let token          = window.saturne.toolbox.getToken();
  let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL)
  $.ajax({
    url: document.URL + querySeparator + 'action=' + action + '&token=' + token,
    method: 'POST',
    data: JSON.stringify(elements),
    processData: false,
    contentType: false,
    success: function(response) {
      if (loader !== 'none') {
        window.saturne.loader.remove($container);
      }
    },
    error: function(response) {
      if (loader !== 'none') {
        window.saturne.loader.remove($containers);
      }
    }
  });
}