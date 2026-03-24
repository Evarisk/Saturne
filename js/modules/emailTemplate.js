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
 * \file    js/modules/emailTemplate.js
 * \ingroup saturne
 * \brief   JavaScript EmailTemplate file for module Saturne
 */

'use strict';

/**
 * Init EmailTemplate
 *
 * @memberof Saturne_EmailTemplate
 *
 * @since   22.0.0
 * @version 22.0.0
 *
 * @type {Object}
 */
window.saturne.emailTemplate = {};

/**
 * Audio init
 *
 * @memberof Saturne_Audio
 *
 * @since   22.0.0
 * @version 22.0.0
 *
 * @returns {void}
 */
window.saturne.emailTemplate.init = function() {
  window.saturne.emailTemplate.event();
};

/**
 * emailTemplate event
 *
 * @memberof Saturne_EmailTemplate
 *
 * @since   22.0.0
 * @version 22.0.0
 *
 * @returns {void}
 */
window.saturne.emailTemplate.event = function() {
};

/**
 * emailTemplate updateSub
 *
 * @since   22.0.0
 * @version 22.0.0
 *
 * @return {void}
 */
window.saturne.emailTemplate.updateSub = function() {
    const url = new URL(window.location.href);
    url.searchParams.set('action', 'updateSub');
    url.searchParams.set('type_template', $(this).val());

    $.ajax({
        url: url.toString(),
        method: 'GET',
        success: function (response) {
            data = JSON.parse(response);

            $box = $('#idfortooltiponclick_content span')
            $extraField = $box.find('.extrafield')

            if ($extraField.length === 0) {
                $extraField = $('<div class="extrafield"></div>');
                $('#idfortooltiponclick_content span').append($extraField);
            } else {
                $extraField.empty();
            }

            $extraField.append(
                $('<br>'),
                $('<strong>Extrafields</strong>')
            );

            data.forEach(item => {
                $extraField.append(
                    $('<div></div>').text(item)
                );
            });
        }
    });
};