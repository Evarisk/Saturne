/* Copyright (C) 2022-2023 EVARISK <technique@evarisk.com>
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
 * \file    js/modules/utils.js
 * \ingroup saturne
 * \brief   JavaScript utils file for module Saturne
 */

/**
 * Init utils JS
 *
 * @memberof Saturne_Utils
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @type {Object}
 */
window.saturne.utils = {};

/**
 * Utils init
 *
 * @memberof Saturne_Utils
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void}
 */
window.saturne.utils.init = function() {
  window.saturne.utils.event();
};

/**
 * Utils event
 *
 * @memberof Saturne_Utils
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void}
 */
window.saturne.utils.event = function() {
  $(document).on('mouseenter', '.move-line.ui-sortable-handle', window.saturne.utils.draganddrop);
  $(document).on('change', '#element_type', window.saturne.utils.reloadField);
};

/**
 * Drag ana drop on move-line action
 *
 * @memberof Saturne_Utils
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.utils.draganddrop = function() {
  $(this).css('cursor', 'pointer');

  $('#tablelines tbody').sortable();
  $('#tablelines tbody').sortable({
    handle: '.move-line',
    connectWith:'#tablelines tbody .line-row',
    tolerance:'intersect',
    over:function(){
      $(this).css('cursor', 'grabbing');
    },
    stop: function() {
      $(this).css('cursor', 'default');
      let token = $('.fiche').find('input[name="token"]').val();

      let separator = '&'
      if (document.URL.match(/action=/)) {
        document.URL = document.URL.split(/\?/)[0]
        separator = '?'
      }
      let lineOrder = [];
      $('.line-row').each(function(  ) {
        lineOrder.push($(this).attr('id'));
      });
      $.ajax({
        url: document.URL + separator + "action=moveLine&token=" + token,
        type: "POST",
        data: JSON.stringify({
          order: lineOrder
        }),
        processData: false,
        contentType: false,
        success: function () {},
        error: function() {}
      });
    }
  });
};

/**
 * Enforce min and max value on keyup event for field input
 *
 * @memberof Saturne_Utils
 *
 * @since   1.2.1
 * @version 1.2.1
 *
 * @returns {void}
 */
window.saturne.utils.enforceMinMax = function(triggeredElement) {
  console.log('d')
  if (triggeredElement.value !== "") {
    if (parseInt(triggeredElement.value) < parseInt(triggeredElement.min)) {
      triggeredElement.value = triggeredElement.min;
    }
    if (parseInt(triggeredElement.value) > parseInt(triggeredElement.max)) {
      triggeredElement.value = triggeredElement.max;
    }
  }
};
