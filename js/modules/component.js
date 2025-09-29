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
 * \file    js/modules/component.js
 * \ingroup saturne
 * \brief   JavaScript component file
 */

'use strict';

/**
 * Init component JS
 *
 * @memberof Saturne_Component
 *
 * @since   21.0.0
 * @version 21.0.0
 */
window.saturne.component = {};

/**
 * Component init
 *
 * @memberof Saturne_Component
 *
 * @since   21.0.0
 * @version 21.0.0
 *
 * @return {void}
 */
window.saturne.component.init = function() {
  window.saturne.component.event();
};

/**
 * Component event
 *
 * @memberof Saturne_Component
 *
 * @since   21.0.0
 * @version 21.0.0
 *
 * @return {void}
 */
window.saturne.component.event = function() {
  $(document).on('click', '#update_badge_component:not(.button-disable)', window.saturne.component.updateBadgeComponent);
};

/**
 * Update badge component
 *
 * @memberof Saturne_Component
 *
 * @since   21.0.0
 * @version 21.0.0
 *
 * @return {void}
 */
window.saturne.component.updateBadgeComponent = function() {
  const token = window.saturne.toolbox.getToken();

  const $this           = $(this);
  const $modal          = $this.closest('#badge_component');
  const fromId          = $modal.data('from-id');
  const fromType        = $modal.data('from-type');
  const fromField       = $modal.data('from-field');
  const $badgeComponent = $(document).find(`#badge_component_${fromField}_${fromId}`);

  const label = $modal.find('#myTextarea').val();

  window.saturne.loader.display($badgeComponent);

  $.ajax({
    url: `${document.URL}&action=update_badge_component&token=${token}`,
    type: 'POST',
    data: JSON.stringify({
      objectLine_id:      fromId,
      objectLine_element: fromType,
      field: fromField,
      label:              label,
    }),
    success: function(resp) {
      $modal.replaceWith($(resp).find('#badge_component'));
      $badgeComponent.replaceWith($(resp).find(`#badge_component_${fromField}_${fromId}`));
    }
  });
};
