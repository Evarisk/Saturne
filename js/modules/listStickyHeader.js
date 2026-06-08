/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    js/modules/listStickyHeader.js
 * \ingroup saturne
 * \brief   Make the list table header sticky by sizing the scroll container to the viewport
 */

window.saturne.listStickyHeader = {};

/**
 * Init
 *
 * @return {void}
 */
window.saturne.listStickyHeader.init = function() {
    window.saturne.listStickyHeader.apply();
    $(window).on('resize.saturnesticky', window.saturne.listStickyHeader.apply);
};

/**
 * Size each list scroll container so it fits the viewport; its sticky header then
 * stays visible while the rows scroll inside it.
 *
 * @return {void}
 */
window.saturne.listStickyHeader.apply = function() {
    $('.bodyforlist .div-table-responsive').each(function() {
        var top = this.getBoundingClientRect().top;
        var h   = window.innerHeight - top - 16;
        if (h > 150) {
            this.style.maxHeight = h + 'px';
        }
    });

    // Offset the sticky title row by the classic filter row height so both rows stay stacked
    // (the filter row is absent in panel mode, leaving the title row pinned at the top).
    $('.bodyforlist table.liste').each(function() {
        var filterRow = this.querySelector('thead tr.liste_titre_filter');
        var height    = filterRow ? Math.round(filterRow.getBoundingClientRect().height) : 0;
        this.style.setProperty('--saturne-filter-row-h', height + 'px');
    });
};
