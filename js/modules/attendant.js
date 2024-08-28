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
 * \file    js/modules/attendant.js
 * \ingroup saturne
 * \brief   JavaScript file attendant for module Saturne.
 */

/*
 * Gestion des bouttons.
 *
 * @since   1.6.0
 * @version 1.6.0
 */

/**
 * Initialise l'objet "attendant" ainsi que la méthode "init" obligatoire pour la bibliothèque Saturne.
 *
 * @since   1.6.0
 * @version 1.6.0
 *
 * @return {void}
 */
window.saturne.attendant = {};

/**
 * La méthode appelée automatiquement par la bibliothèque Saturne.
 *
 * @since   1.6.0
 * @version 1.6.0
 *
 * @return {void}
 */
window.saturne.attendant.init = function() {
    window.saturne.attendant.event();
    window.saturne.attendant.restoreScrollPosition();
};

/**
 * La méthode contenant tous les événements pour les attendants.
 *
 * @since   1.6.0
 * @version 1.6.0
 *
 * @return {void}
 */
window.saturne.attendant.event = function() {
    $(document).on('change', 'select.flat', window.saturne.attendant.saveScrollPosition);
};

/**
 * Save current scroll position to cookies.
 *
 * @since   1.6.0
 * @version 1.6.0
 *
 * @return {void}
 */
window.saturne.attendant.saveScrollPosition = function() {
    document.cookie = "page_y=" + $(this).position().top;
};

/**
 * On page refresh, retrieve page_y from cookies, scroll to position, reset page_y.
 *
 * @since   1.6.0
 * @version 1.6.0
 *
 * @return {void}
 */
window.saturne.attendant.restoreScrollPosition = function() {
    let element = $('select.flat');

    if (element.length === 0) {
        return;
    }

    let page_y = document.cookie.match(/page_y=([0-9.]+)/)[1];
    $(window).scrollTop(page_y);
    document.cookie = "page_y=0";
};