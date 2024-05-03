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
 * \file    js/modules/button.js
 * \ingroup saturne
 * \brief   JavaScript file button for module Saturne.
 */

/*
 * Gestion des bouttons.
 *
 * @since   1.0.0
 * @version 1.0.0
 */

/**
 * Initialise l'objet "button" ainsi que la méthode "init" obligatoire pour la bibliothèque Saturne.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.button = {};

/**
 * La méthode appelée automatiquement par la bibliothèque Saturne.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.button.init = function() {
    window.saturne.button.event();
};

/**
 * La méthode contenant tous les événements pour les buttons.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.button.event = function() {
    $(document).on('click', '.wpeo-button:submit, .wpeo-button.auto-download', window.saturne.button.addLoader);
};

/**
 * Add loader on button
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.button.addLoader = function() {
    if (!$(this).hasClass('no-load')) {
      window.saturne.loader.display($(this));
      $(this).toggleClass('button-blue button-disable');
    }
};
