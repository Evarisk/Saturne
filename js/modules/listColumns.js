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
 * \file    js/modules/listColumns.js
 * \ingroup saturne
 * \brief   Per-user list column customization: edit-mode toggle, resize and drag reorder
 */

window.saturne.listColumns = {};
window.saturne.listColumns.dragSrc = null;
window.saturne.listColumns.resizing = null;

/**
 * Init
 *
 * @return {void}
 */
window.saturne.listColumns.init = function() {
    window.saturne.listColumns.event();
};

/**
 * Bind events
 *
 * @return {void}
 */
window.saturne.listColumns.event = function() {
    $(document).on('click', '.saturne-columns-toggle', window.saturne.listColumns.toggle);
    $(document).on('click', '.saturne-columns-reset', window.saturne.listColumns.reset);
    // Prevent sort navigation while editing columns
    $(document).on('click', 'table.liste.saturne-columns-editing thead th a', function(e) { e.preventDefault(); });
    // Resize
    $(document).on('mousedown', '.saturne-col-resize', window.saturne.listColumns.onResizeStart);
    // Reorder
    $(document).on('dragstart', 'table.liste.saturne-columns-editing thead th[data-colkey]', window.saturne.listColumns.onDragStart);
    $(document).on('dragover', 'table.liste.saturne-columns-editing thead th[data-colkey]', window.saturne.listColumns.onDragOver);
    $(document).on('drop', 'table.liste.saturne-columns-editing thead th[data-colkey]', window.saturne.listColumns.onDrop);
    $(document).on('dragend', 'table.liste.saturne-columns-editing thead th[data-colkey]', window.saturne.listColumns.onDragEnd);
};

/**
 * The customizable list table.
 *
 * @return {jQuery} The table element
 */
window.saturne.listColumns.table = function() {
    return $('table.liste[data-list-layout-id]').first();
};

/**
 * Endpoint URL.
 *
 * @return {String} The AJAX endpoint URL
 */
window.saturne.listColumns.url = function() {
    var root = (window.saturne.config && window.saturne.config.urlRoot) ? window.saturne.config.urlRoot : '';
    return root + '/custom/saturne/core/ajax/saturne_save_list_layout.php';
};

/**
 * Toggle column edit mode.
 *
 * @param  {Event} e Click event
 * @return {void}
 */
window.saturne.listColumns.toggle = function(e) {
    e.preventDefault();
    var $t = window.saturne.listColumns.table();
    if (!$t.length) {
        return;
    }
    var entering = !$t.hasClass('saturne-columns-editing');
    $t.toggleClass('saturne-columns-editing', entering);
    $('.saturne-columns-controls').toggleClass('editing', entering);
    $('.saturne-columns-toggle').toggleClass('active', entering);

    if (entering) {
        $t.find('thead th[data-colkey]').attr('draggable', 'true').each(function() {
            if (!$(this).find('.saturne-col-resize').length) {
                $(this).append('<span class="saturne-col-resize" title="Redimensionner"></span>');
            }
        });
    } else {
        $t.find('thead th[data-colkey]').removeAttr('draggable');
    }
};

/**
 * Resize: pointer down on a column handle.
 *
 * @param  {Event} e Mouse event
 * @return {void}
 */
window.saturne.listColumns.onResizeStart = function(e) {
    e.preventDefault();
    e.stopPropagation();
    var th = $(this).closest('th')[0];
    // Disable drag while resizing this column
    $(th).attr('draggable', 'false');
    window.saturne.listColumns.resizing = { th: th, startX: e.pageX, startW: th.offsetWidth, colkey: $(th).attr('data-colkey') };
    $('body').addClass('saturne-col-resizing-active');
    $(document).on('mousemove.saturnecol', window.saturne.listColumns.onResizeMove);
    $(document).on('mouseup.saturnecol', window.saturne.listColumns.onResizeEnd);
};

/**
 * Resize: pointer move.
 *
 * @param  {Event} e Mouse event
 * @return {void}
 */
window.saturne.listColumns.onResizeMove = function(e) {
    var r = window.saturne.listColumns.resizing;
    if (!r) {
        return;
    }
    var w = Math.max(40, r.startW + (e.pageX - r.startX));
    window.saturne.listColumns.applyWidth(r.colkey, w);
};

/**
 * Resize: pointer up — persist widths.
 *
 * @return {void}
 */
window.saturne.listColumns.onResizeEnd = function() {
    $(document).off('mousemove.saturnecol mouseup.saturnecol');
    $('body').removeClass('saturne-col-resizing-active');
    var r = window.saturne.listColumns.resizing;
    window.saturne.listColumns.resizing = null;
    if (r) {
        $(r.th).attr('draggable', 'true');
        window.saturne.listColumns.save();
    }
};

/**
 * Apply a width (px) to a column (header + body cells).
 *
 * @param  {String} colkey Column key
 * @param  {Number} w      Width in px
 * @return {void}
 */
window.saturne.listColumns.applyWidth = function(colkey, w) {
    window.saturne.listColumns.table().find('[data-colkey="' + colkey + '"]').css({ width: w + 'px', minWidth: w + 'px' });
};

/**
 * Reorder: drag start.
 *
 * @param  {Event} e Drag event
 * @return {void}
 */
window.saturne.listColumns.onDragStart = function(e) {
    window.saturne.listColumns.dragSrc = this;
    $(this).addClass('saturne-col-dragging');
    if (e.originalEvent.dataTransfer) {
        e.originalEvent.dataTransfer.effectAllowed = 'move';
        try { e.originalEvent.dataTransfer.setData('text/plain', ''); } catch (err) { /* IE guard */ }
    }
};

/**
 * Reorder: drag over.
 *
 * @param  {Event} e Drag event
 * @return {void}
 */
window.saturne.listColumns.onDragOver = function(e) {
    var src = window.saturne.listColumns.dragSrc;
    if (!src || src === this) {
        return;
    }
    e.preventDefault();
};

/**
 * Reorder: drop — move the header + every body cell live, then persist.
 *
 * @param  {Event} e Drag event
 * @return {void}
 */
window.saturne.listColumns.onDrop = function(e) {
    e.preventDefault();
    var src = window.saturne.listColumns.dragSrc;
    if (!src || src === this) {
        return;
    }
    var srcKey = $(src).attr('data-colkey');
    var tgtKey = $(this).attr('data-colkey');
    var rect   = this.getBoundingClientRect();
    var after  = (e.originalEvent.clientX - rect.left) > (rect.width / 2);

    if (after) { $(this).after(src); } else { $(this).before(src); }

    window.saturne.listColumns.table().find('tr.oddeven').each(function() {
        var $srcTd = $(this).find('> td[data-colkey="' + srcKey + '"]');
        var $tgtTd = $(this).find('> td[data-colkey="' + tgtKey + '"]');
        if ($srcTd.length && $tgtTd.length) {
            if (after) { $tgtTd.after($srcTd); } else { $tgtTd.before($srcTd); }
        }
    });

    window.saturne.listColumns.save();
};

/**
 * Reorder: drag end.
 *
 * @return {void}
 */
window.saturne.listColumns.onDragEnd = function() {
    $(this).removeClass('saturne-col-dragging');
    window.saturne.listColumns.dragSrc = null;
};

/**
 * Read the current column order from the header.
 *
 * @return {Array} Ordered column keys
 */
window.saturne.listColumns.currentOrder = function() {
    var order = [];
    window.saturne.listColumns.table().find('thead th[data-colkey]').each(function() {
        order.push($(this).attr('data-colkey'));
    });
    return order;
};

/**
 * Read the current column widths from the header.
 *
 * @return {Object} Map colkey => px
 */
window.saturne.listColumns.currentWidths = function() {
    var widths = {};
    window.saturne.listColumns.table().find('thead th[data-colkey]').each(function() {
        var w = this.style.width ? parseInt(this.style.width, 10) : 0;
        if (w > 0) {
            widths[$(this).attr('data-colkey')] = w;
        }
    });
    return widths;
};

/**
 * Persist the current order + widths for this list.
 *
 * @return {void}
 */
window.saturne.listColumns.save = function() {
    var $t = window.saturne.listColumns.table();
    if (!$t.length) {
        return;
    }
    $.ajax({
        url: window.saturne.listColumns.url(),
        method: 'POST',
        dataType: 'json',
        data: {
            action: 'save',
            token: window.saturne.toolbox.getToken(),
            list_id: $t.attr('data-list-layout-id'),
            layout: JSON.stringify({ order: window.saturne.listColumns.currentOrder(), widths: window.saturne.listColumns.currentWidths() })
        }
    });
};

/**
 * Reset the layout to defaults.
 *
 * @param  {Event} e Click event
 * @return {void}
 */
window.saturne.listColumns.reset = function(e) {
    e.preventDefault();
    var $t = window.saturne.listColumns.table();
    var listId = $t.length ? $t.attr('data-list-layout-id') : $(this).closest('.saturne-columns-controls').attr('data-list-layout-id');
    $.ajax({
        url: window.saturne.listColumns.url(),
        method: 'POST',
        dataType: 'json',
        data: { action: 'reset', token: window.saturne.toolbox.getToken(), list_id: listId },
        success: function() { window.location.reload(); },
        error: function() { window.location.reload(); }
    });
};
