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
 * \brief   JavaScript file utils for module Saturne.
 */


/*
 * Gestion du utils.
 *
 * @since   1.0.0
 * @version 1.0.0
 */

/**
 * Initialise l'objet "utils" ainsi que la méthode "init" obligatoire pour la bibliothèque Saturne.
 *
 * @memberof Saturne_Framework_Dropdown
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void} [description]
 */
window.saturne.utils = {};

/**
 * La méthode appelée automatiquement par la bibliothèque Saturne.
 *
 * @memberof Saturne_Framework_Dropdown
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void} [description]
 */
window.saturne.utils.init = function() {
	window.saturne.utils.event();
};

/**
 * La méthode contenant tous les événements pour la utils.
 *
 * @memberof Saturne_Framework_Dropdown
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void} [description]
 */
window.saturne.utils.event = function() {
	$(document).on('mouseenter', '.move-line.ui-sortable-handle', window.saturne.utils.draganddrop);
};

/**
 * [description]
 *
 * @memberof Saturne_Framework_Dropdown
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {void} event [description]
 * @returns {void}       [description]
 */
window.saturne.utils.draganddrop = function(event) {
	$(this).css('cursor', 'pointer');

	$('#tablelines tbody').sortable();
	$('#tablelines tbody').sortable({
		handle: '.move-line',
		connectWith:'#tablelines tbody .line-row',
		tolerance:'intersect',
		over:function(event,ui){
			$(this).css('cursor', 'grabbing');
		},
		stop: function(event, ui) {
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
				success: function ( resp ) {
				}
			});
		}
	});
};

