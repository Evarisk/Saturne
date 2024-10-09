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
 * \file    js/modules/menu.js
 * \ingroup saturne
 * \brief   JavaScript file menu for module Saturne.
 */

/**
 * Initialise l'objet "menu" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.saturne.menu = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.menu.init = function() {
	window.saturne.menu.event();
};

/**
 * La méthode contenant tous les événements pour le migration.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.menu.event = function() {
	$(document).on( 'click', ' .blockvmenu', window.saturne.menu.toggleMenu);
	$(document).ready(function() { window.saturne.menu.setMenu()});
}

/**
 * Action Toggle main menu.
 *
 * @since   8.5.0
 * @version 9.4.0
 *
 * @return {void}
 */
window.saturne.menu.toggleMenu = function() {

	var menu = $(this).closest('#id-left').find('a.vmenu, span.vmenudisabled, span.vmenu, a.vsmenu, a.help');
	var elementParent = $(this).closest('#id-left').find('div.vmenu')
	var text = '';

	if ($(this).find('span.vmenu').find('.fa-chevron-circle-left').length > 0) {

		menu.each(function () {
			text = $(this).html().split('</i>');
			if (text[1].match(/&gt;/)) {
				text[1] = text[1].replace(/&gt;/, '')
			}
			$(this).attr('title', text[1])
			$(this).html(text[0]);
		});

		elementParent.css('width', '30px');
		elementParent.find('.blockvmenusearch').hide();
		$('span.vmenu').attr('title', ' Agrandir le menu')

		$('span.vmenu').html($('span.vmenu').html());

		$(this).find('span.vmenu').find('.fa-chevron-circle-left').removeClass('fa-chevron-circle-left').addClass('fa-chevron-circle-right');
		localStorage.setItem('maximized', 'false')

	} else if ($(this).find('span.vmenu').find('.fa-chevron-circle-right').length > 0) {

		menu.each(function () {
			$(this).html($(this).html().replace('&gt;','') + ' ' + $(this).attr('title'));
		});

		elementParent.css('width', '188px');
		elementParent.find('.blockvmenusearch').show();
		$('div.menu_titre').attr('style', 'width: 188px !important; cursor : pointer' )
		$('span.vmenu').attr('title', ' Réduire le menu')
		$('span.vmenu').html('<i class="fas fa-chevron-circle-left"></i> Réduire le menu');

		localStorage.setItem('maximized', 'true')

		$(this).find('span.vmenu').find('.fa-chevron-circle-right').removeClass('fa-chevron-circle-right').addClass('fa-chevron-circle-left');
	}
};

/**
 * Action set  menu.
 *
 * @since   8.5.0
 * @version 9.0.1
 *
 * @return {void}
 */
window.saturne.menu.setMenu = function() {
	if ($('.blockvmenu.blockvmenulast .saturne-toggle-menu').length > 0) {
		$('.blockvmenu.blockvmenulast .saturne-toggle-menu').closest('.menu_titre').attr('style', 'cursor:pointer ! important')
		if (localStorage.maximized == 'false') {
			$('#id-left').attr('style', 'display:none !important')
		}

		if (localStorage.maximized == 'false') {
			var text = '';
			var menu = $('#id-left').find('a.vmenu, span.vmenudisabled, span.vmenu, a.vsmenu, a.help');
			var elementParent = $(document).find('div.vmenu')

			menu.each(function () {
				text = $(this).html().split('</i>');
				$(this).attr('title', text[1])
				$(this).html(text[0]);
				console.log(text)
			});

			$('#id-left').attr('style', 'display:block !important')
			$('div.menu_titre').attr('style', 'width: 50px !important')
			$('span.vmenu').attr('title', ' Agrandir le menu')

			$('span.vmenu').html($('span.vmenu').html())
			$('span.vmenu').find('.fa-chevron-circle-left').removeClass('fa-chevron-circle-left').addClass('fa-chevron-circle-right');

			elementParent.css('width', '30px');
			elementParent.find('.blockvmenusearch').hide();
		}
		localStorage.setItem('currentString', '')
		localStorage.setItem('keypressNumber', 0)
	}
};
