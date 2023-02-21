/* Copyright (C) 2022-2023 EVARISK <dev@evarisk.com>
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
 * \file    js/modules/notice.js
 * \ingroup saturne
 * \brief   JavaScript file notice for module Saturne.
 */

/**
 * Initialise l'objet "notice" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.4.0
 * @version 1.4.0
 */
window.saturne.notice = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.4.0
 * @version 1.4.0
 *
 * @return {void}
 */
window.saturne.notice.init = function() {
	window.saturne.notice.event();
};

/**
 * La méthode contenant tous les événements pour l'évaluateur.
 *
 * @since   1.4.0
 * @version 1.4.0
 *
 * @return {void}
 */
window.saturne.notice.event = function() {
	$(document).on('click', '.notice-close', window.saturne.notice.closeNotice);
};

/**
 * Clique sur une des user de la liste.
 *
 * @since   1.4.0
 * @version 1.4.0
 *
 * @return {void}
 */
window.saturne.notice.closeNotice = function() {
	$(this).closest('.notice').fadeOut(function () {
		$(this).closest('.notice').addClass("hidden");
	});

	if ($(this).hasClass('notice-close-forever')) {
		let token          = window.saturne.toolbox.getToken();
		let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);

		$.ajax({
			url: document.URL + querySeparator + 'action=closenotice&token='+token,
			type: "POST",
		});
	}
};
