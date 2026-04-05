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
 * \file    js/modules/filter.js
 * \ingroup saturne
 * \brief   JavaScript filter panel file for module Saturne
 */

/**
 * Init filter JS
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @type {Object}
 */
window.saturne.filter = {};

/**
 * Filter init
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void}
 */
window.saturne.filter.init = function() {
    window.saturne.filter.event();
    window.saturne.filter.initCategoryPickers();
};

/**
 * Filter event
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void}
 */
window.saturne.filter.event = function() {
    $(document).on('click', '#saturne-filter-toggle', window.saturne.filter.open);
    $(document).on('click', '#saturne-filter-backdrop', window.saturne.filter.close);
    $(document).on('click', '.saturne-filter-panel-close', window.saturne.filter.close);
    $(document).on('click', '.saturne-filter-mode-toggle', window.saturne.filter.toggleMode);
    $(document).on('keydown', window.saturne.filter.handleKeyDown);
};

/**
 * Open the filter panel
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {boolean}
 */
window.saturne.filter.open = function() {
    $('#saturne-filter-backdrop').show();
    $('#saturne-filter-panel').css('right', '0');
    $('body').css('overflow', 'hidden');
    window.saturne.filter.initSelect2();
    return false;
};

/**
 * Close the filter panel
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void}
 */
window.saturne.filter.close = function() {
    $('#saturne-filter-backdrop').hide();
    $('#saturne-filter-panel').css('right', '-400px');
    $('body').css('overflow', '');
};

/**
 * Close the filter panel on Escape key
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param   {KeyboardEvent} e
 * @returns {void}
 */
window.saturne.filter.handleKeyDown = function(e) {
    if (e.key === 'Escape') {
        window.saturne.filter.close();
    }
};

/**
 * Toggle the include/exclude mode of a filter field
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void}
 */
window.saturne.filter.toggleMode = function() {
    var key    = this.id.replace('search_mode_toggle_', '');
    var $input = $('#search_' + key + '_mode');
    var exc    = $input.val() !== 'exc';
    $input.val(exc ? 'exc' : 'inc');
    $(this).html(exc ? '<span class="far fa-eye-slash"></span>' : '<span class="far fa-eye"></span>');
    $(this).toggleClass('saturne-filter-mode-exc', exc).toggleClass('saturne-filter-mode-inc', !exc);
};

/**
 * Re-init select2 fields inside the panel after it becomes visible
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void}
 */
window.saturne.filter.initSelect2 = function() {
    if (typeof jQuery === 'undefined' || !jQuery.fn.select2) {
        return;
    }
    setTimeout(function() {
        $('#saturne-filter-panel select').each(function() {
            var $el = $(this);
            if ($el.data('select2')) {
                $el.select2('destroy');
            }
            $el.select2({
                width            : '100%',
                dropdownParent   : $('body'),
                dropdownCssClass : 'saturne-filter-select2-drop'
            });
        });
    }, 50);
};

/**
 * Init all category pickers found in the page
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void}
 */
window.saturne.filter.initCategoryPickers = function() {
    $('[data-cat-colors]').each(function() {
        window.saturne.filter.initCategoryPicker(this);
    });
};

/**
 * Init a single category picker from its tags container element.
 * The container must have: data-picker-id, data-cat-icon, data-cat-colors.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param   {HTMLElement} tagsEl  The tags container element
 * @returns {void}
 */
window.saturne.filter.initCategoryPicker = function(tagsEl) {
    var $tags    = $(tagsEl);
    var pickerId = $tags.data('picker-id');
    var catIcon  = $tags.data('cat-icon');
    var catColors = $tags.data('cat-colors');
    var FALLBACK = '#95a5a6';
    var picker   = document.getElementById(pickerId);

    if (!picker) {
        return;
    }

    function esc(s) {
        return s.replace(/[<>&"]/g, function(c) {
            return {'<': '&lt;', '>': '&gt;', '&': '&amp;', '"': '&quot;'}[c];
        });
    }

    function getColor(id) {
        return catColors[id] || FALLBACK;
    }

    function removePO(id) {
        var o = picker.querySelector('option[value="' + id + '"]');
        if (o) {
            o.remove();
        }
        if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
            jQuery(picker).trigger('change.select2');
        }
    }

    function restorePO(id, lbl, col) {
        if (picker.querySelector('option[value="' + id + '"]')) {
            return;
        }
        var o        = document.createElement('option');
        o.value      = id;
        o.text       = lbl;
        o.dataset.color = col;
        picker.appendChild(o);
        if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
            jQuery(picker).trigger('change.select2');
        }
    }

    function renderTag(id, lbl, col, mode) {
        var exc  = mode === 'exc';
        var sign = exc ? '\u2212' : '+';
        return '<span class="cat-sign saturne-cat-tag-sign" style="background:' + col + '">' + catIcon + ' ' + sign + '</span>' +
            '<span class="saturne-cat-tag-body">' +
            '<span class="saturne-cat-tag-label' + (exc ? ' is-exc' : '') + '">' + esc(lbl) + '</span>' +
            '<span class="cat-remove saturne-cat-tag-remove">\u00d7</span>' +
            '</span>' +
            '<input type="hidden" name="search_categories_filter[]" value="' + (exc ? '-' : '+') + id + '">';
    }

    function bindTag(s) {
        s.querySelector('.cat-sign').addEventListener('click', function(e) {
            e.stopPropagation();
            var m      = s.dataset.mode === 'inc' ? 'exc' : 'inc';
            s.dataset.mode = m;
            s.innerHTML    = renderTag(s.dataset.catid, s.dataset.label, s.dataset.color, m);
            bindTag(s);
        });
        s.querySelector('.cat-remove').addEventListener('click', function(e) {
            e.stopPropagation();
            restorePO(s.dataset.catid, s.dataset.label, s.dataset.color);
            s.remove();
        });
    }

    function buildTag(id, lbl, mode) {
        if (tagsEl.querySelector('[data-catid="' + id + '"]')) {
            return;
        }
        var col     = getColor(id);
        var s       = document.createElement('span');
        s.dataset.catid  = id;
        s.dataset.mode   = mode;
        s.dataset.label  = lbl;
        s.dataset.color  = col;
        s.className      = 'saturne-cat-tag';
        s.style.borderColor = col;
        s.innerHTML      = renderTag(id, lbl, col, mode);
        removePO(id);
        bindTag(s);
        tagsEl.appendChild(s);
    }

    if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
        jQuery(picker).select2({
            width          : '100%',
            dropdownParent : jQuery('body'),
            templateResult : function(o) {
                if (!o.id) {
                    return o.text;
                }
                var c = jQuery(o.element).data('color') || FALLBACK;
                return jQuery('<span>').append(
                    jQuery('<span>').css({display: 'inline-block', width: '10px', height: '10px', borderRadius: '50%', background: c, marginRight: '6px', verticalAlign: 'middle'}),
                    document.createTextNode(o.text)
                );
            },
            templateSelection : function(o) {
                if (!o.id) {
                    return o.text;
                }
                var c = jQuery(o.element).data('color') || FALLBACK;
                return jQuery('<span>').append(
                    jQuery('<span>').css({display: 'inline-block', width: '10px', height: '10px', borderRadius: '50%', background: c, marginRight: '6px', verticalAlign: 'middle'}),
                    document.createTextNode(o.text)
                );
            }
        }).on('select2:select', function(e) {
            var o = e.params.data;
            buildTag(o.id, o.text, 'inc');
            jQuery(picker).val('').trigger('change.select2');
        });
    } else {
        picker.addEventListener('change', function() {
            var o = picker.options[picker.selectedIndex];
            if (!o.value) {
                return;
            }
            buildTag(o.value, o.text, 'inc');
            picker.selectedIndex = 0;
        });
    }

    tagsEl.querySelectorAll('[data-catid]').forEach(bindTag);
};
