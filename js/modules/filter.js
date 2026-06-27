/* Copyright (C) 2024-2026 EVARISK <technique@evarisk.com>
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
 */

/**
 * \file    js/modules/filter.js
 * \ingroup saturne
 * \brief   JavaScript filter popovers for module Saturne
 */

window.saturne.filter = {
    activePopoverTarget: null,
    activePopoverTd: null,
    activePopoverInputWrapper: null,
    activePopoverTh: null,

    init: function() {
        this.event();
        this.initCategoryPickers();
        this.highlightFilteredColumns();
    },

    event: function() {
        $(document).off('click.saturneColFilter').on('click.saturneColFilter', '.saturne-col-filter-btn', this.togglePopover.bind(this));
        
        // Global reset button now uses a direct PHP link (no JS needed).
        // The JS handler has been removed to avoid e.preventDefault() blocking the link.

        // Close popover when clicking outside
        $(document).off('click.saturnePopover').on('click.saturnePopover', function(e) {
            if (!$(e.target).closest('.saturne-col-filter-popover, .saturne-col-filter-btn, .select2-container, .ui-datepicker, .ui-widget-overlay').length) {
                window.saturne.filter.closePopover();
            }
        });

        // Clear specific column filter
        $(document).off('click.saturneClearColFilter').on('click.saturneClearColFilter', '.saturne-active-filter-indicator', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var $th = $(this).closest('th, td');
            if ($th.length) {
                var colIndex = $th.index();
                var $filterRow = $th.closest('tr').next('tr.liste_titre_filter');
                if (!$filterRow.length) {
                    $filterRow = $('tr.liste_titre_filter').first();
                }
                
                var $filterCell = $filterRow.children().eq(colIndex);
                if ($filterCell.length) {
                    // Find any input/select in this specific filter cell and clear them
                    $filterCell.find('input[type="text"], input[type="number"]').val('');
                    $filterCell.find('select').each(function() {
                        if ($(this).find('option[value="-1"]').length > 0) {
                            $(this).val('-1').trigger('change');
                        } else if ($(this).find('option[value=""]').length > 0) {
                            $(this).val('').trigger('change');
                        } else {
                            $(this).prop('selectedIndex', 0).trigger('change');
                        }
                    });
                }
                
                var $form = $(this).closest('form');
                if ($form.length === 0) {
                    $form = $('form').first();
                }
                
                // Submit the form
                $form.append('<input type="hidden" name="button_search_x" value="1">');
                $form.submit();
            }
        });
    },

    highlightFilteredColumns: function() {
        var $searchRow = $('tr.liste_titre_filter');
        if (!$searchRow.length) return;
        
        $searchRow.find('td').each(function(index) {
            var $td = $(this);
            var hasFilter = false;
            $td.find('input[type="text"], input[type="number"], select').each(function() {
                var val = $(this).val();
                if (val && val !== '-1' && $.trim(val) !== '') {
                    hasFilter = true;
                }
            });
            
            if (hasFilter) {
                var $th = $td.closest('table').find('tr.liste_titre th').eq(index);
                if ($th.length) {
                    $th.addClass('saturne-filter-active');
                    if (!$th.find('.saturne-active-filter-indicator').length) {
                        $th.find('.saturne-col-filter-btn').before('<span class="saturne-active-filter-indicator" style="margin-right: 4px; cursor: pointer; display: inline-flex; align-items: center;" title="Supprimer ce filtre"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon><line x1="16" y1="16" x2="22" y2="22" stroke="#e74c3c" stroke-width="2"></line><line x1="22" y1="16" x2="16" y2="22" stroke="#e74c3c" stroke-width="2"></line></svg></span>');
                    }
                }
            }
        });
    },

    togglePopover: function(e) {
        e.preventDefault();
        e.stopPropagation();

        var $btn = $(e.currentTarget);
        var $th = $btn.closest('th');
        var colIndex = $th.index();
        var colKey = $th.data('colkey');

        if (this.activePopoverTarget && this.activePopoverTarget[0] === $btn[0]) {
            this.closePopover();
            return;
        }

        this.openPopover($btn, $th, colIndex, colKey);
    },

    openPopover: function($btn, $th, colIndex, colKey) {
        this.closePopover(); // Ensure any existing is closed

        // Build popover if not exists
        var $popover = $('#saturne-filter-popover');
        if ($popover.length === 0) {
            $popover = $('<div id="saturne-filter-popover" class="saturne-col-filter-popover"></div>');
            $('body').append($popover);
        }

        // Find the input container in the hidden row
        var $td = $('.liste_titre_filter td').eq(colIndex);
        if (!$td.length) return;

        // Build popover HTML
        var html = `
            <div class="popover-header">
                <div class="popover-input-container">
                    <i class="fas fa-search"></i>
                    <div id="saturne-popover-input-wrapper" style="flex-grow: 1;"></div>
                </div>
            </div>
            <div class="popover-body">
                <div class="popover-action action-sort-asc">
                    <i class="fas fa-sort-alpha-down"></i> Trier de A à Z
                </div>
                <div class="popover-action action-sort-desc">
                    <i class="fas fa-sort-alpha-up"></i> Trier de Z à A
                </div>
                <div class="popover-divider"></div>
                <div class="popover-action action-group" style="opacity: 0.5; cursor: not-allowed;" title="Bientôt disponible">
                    <i class="fas fa-layer-group"></i> Grouper
                </div>
                <div class="popover-action action-freeze" style="opacity: 0.5; cursor: not-allowed;" title="Bientôt disponible">
                    <i class="fas fa-snowflake"></i> Figer
                </div>
                <div class="popover-divider"></div>
                <div class="popover-action action-hide">
                    <i class="fas fa-eye-slash"></i> Masquer
                </div>
            </div>
            <div class="popover-footer">
                <button type="button" class="butAction" id="saturne-popover-apply">Valider</button>
            </div>
        `;
        
        $popover.html(html);

        // Move the input from the TD to the popover wrapper
        var $originalContent = $td.contents();
        var $inputWrapper = $('<div class="saturne-original-input" style="width: 100%;"></div>');
        $inputWrapper.append($originalContent);
        $('#saturne-popover-input-wrapper').append($inputWrapper);

        // Position the popover under the btn
        var btnOffset = $btn.offset();
        $popover.css({
            top: btnOffset.top + $btn.outerHeight() + 10 + 'px',
            left: btnOffset.left - ($popover.outerWidth() / 2) + 20 + 'px',
            display: 'block'
        });

        this.activePopoverTarget = $btn;
        this.activePopoverTd = $td;
        this.activePopoverInputWrapper = $inputWrapper;
        this.activePopoverTh = $th;

        // Reinit select2 if any
        if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
             setTimeout(function() {
                 $popover.find('select').each(function() {
                     var $el = $(this);
                     if ($el.data('select2')) {
                         $el.select2('destroy');
                     }
                     $el.select2({
                         width: '100%',
                         dropdownParent: $popover
                     });
                 });
             }, 50);
        }

        // Focus the first input
        setTimeout(function() {
            $popover.find('input[type="text"]').first().focus();
        }, 100);

        // Bind Actions
        $('#saturne-popover-apply').on('click', function() {
            var $form = $('#searchFormList');
            if (!$form.length) {
                // Sometime the form is just the closest form to the table
                $form = $td.closest('form');
            }
            if ($form.length) {
                window.saturne.filter.closePopover();
                $form.submit();
            }
        });

        $popover.find('input').on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                $('#saturne-popover-apply').click();
            }
        });

        $popover.find('.action-sort-asc').on('click', function() {
            var $a = $th.find('a.reposition'); 
            if ($a.length) {
                var href = $a.attr('href');
                href = href.replace(/sortorder=[^&]*/, 'sortorder=asc');
                window.location.href = href;
            }
        });

        $popover.find('.action-sort-desc').on('click', function() {
            var $a = $th.find('a.reposition');
            if ($a.length) {
                var href = $a.attr('href');
                href = href.replace(/sortorder=[^&]*/, 'sortorder=desc');
                window.location.href = href;
            }
        });

        $popover.find('.action-hide').on('click', function() {
            var colKey = $th.data('colkey');
            var $checkbox1 = $('.multiselectcheckboxselectedfields input[type="checkbox"][value="t.' + colKey + '"]');
            var $checkbox2 = $('.multiselectcheckboxselectedfields input[type="checkbox"][value="' + colKey + '"]');
            var $checkbox = $checkbox1.length ? $checkbox1 : $checkbox2;

            if ($checkbox.length) {
                if ($checkbox.is(':checked')) {
                    $checkbox.trigger('click');
                }
                var $form = $('#searchFormList');
                if (!$form.length) $form = $td.closest('form');
                if ($form.length) $form.submit();
            } else {
                alert("DEBUG: colKey=" + colKey + "\nCheckboxes found: t." + colKey + "=" + $checkbox1.length + ", " + colKey + "=" + $checkbox2.length + "\nIs multiselect in DOM? " + $('.multiselectcheckboxselectedfields').length);
            }
        });
    },

    closePopover: function() {
        if (this.activePopoverTarget) {
            var $popover = $('#saturne-filter-popover');
            
            // Move the input back to the original TD
            if (this.activePopoverTd && this.activePopoverInputWrapper) {
                this.activePopoverTd.append(this.activePopoverInputWrapper.contents());
            }

            $popover.hide();
            this.activePopoverTarget = null;
            this.activePopoverTd = null;
            this.activePopoverInputWrapper = null;
            this.activePopoverTh = null;
        }
    },

    initCategoryPickers: function() {
        $('[data-cat-colors]').each(function() {
            window.saturne.filter.initCategoryPicker(this);
        });
    },

    initCategoryPicker: function(tagsEl) {
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
            return '<span class="cat-sign saturne-cat-tag-sign" style="background:' + col + '">' + catIcon + ' ' + sign + '</span>'
                + '<span class="saturne-cat-tag-body">'
                + '<span class="saturne-cat-tag-label' + (exc ? ' is-exc' : '') + '">' + esc(lbl) + '</span>'
                + '<span class="cat-remove saturne-cat-tag-remove">\u00d7</span>'
                + '</span>'
                + '<input type="hidden" name="search_categories_filter[]" value="' + (exc ? '-' : '+') + id + '">';
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
    }
};
