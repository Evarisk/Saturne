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
 */

/**
 * \file    js/modules/contentEditable.js
 * \ingroup saturne
 * \brief   JavaScript contentEditable file
 */

'use strict';

window.saturne.contentEditable = {};

/**
 * contentEditable init
 *
 * @since   22.0.0
 * @version 22.0.0
 * @return  {void}
 */
window.saturne.contentEditable.init = function init() {
  window.saturne.contentEditable.event();
};

/**
 * Binds all event listeners + initialise Flatpickr.
 * Réécoute aussi l'event custom 'saturne:listReloaded' pour réinitialiser
 * Flatpickr après un rechargement AJAX de la liste.
 *
 * @since   22.0.0
 * @version 22.0.0
 * @return  {void}
 */
window.saturne.contentEditable.event = function initializeEvents() {
  $(document)
    .on('blur',       '.contenteditable', window.saturne.contentEditable.onBlur)
    .on('focus',      '.contenteditable', window.saturne.contentEditable.onFocus)
    .on('input',      '.contenteditable', window.saturne.contentEditable.onInput)
    .on('keydown',    '.contenteditable', window.saturne.contentEditable.onKeyDown)
    .on('mouseenter', '.contenteditable', window.saturne.contentEditable.onMouseEnter)
    .on('dblclick',   '.contenteditable', window.saturne.contentEditable.onDblClick)
    .on('click',      '.contenteditable-cal-btn', window.saturne.contentEditable.onCalBtnClick)
    .on('saturne:listReloaded', function() {
      window.saturne.contentEditable.initFlatpickr();
    });

  window.saturne.contentEditable.initFlatpickr();
};

/**
 * Initialise Flatpickr sur tous les champs datepicker non encore initialisés.
 * Peut être appelé plusieurs fois sans risque (guard via $wrap.data('fp')).
 *
 * @since   22.0.0
 * @version 22.0.0
 * @return  {void}
 */
window.saturne.contentEditable.initFlatpickr = function() {
  $('.contenteditable[data-type="datepicker"]').each(function() {
    const $el   = $(this);
    const $wrap = $el.closest('.contenteditable-wrap');

    if ($wrap.data('fp')) return;

    const $fpInput = $('<input type="text" style="display:none" tabindex="-1"/>');
    $wrap.append($fpInput);

    const initialDate = window.saturne.utils.parseDateTime($.trim($el.text()));

    const fp = flatpickr($fpInput[0], {
      locale:        'fr',
      enableTime:    true,
      time_24hr:     true,
      dateFormat:    'd/m/Y H:i',
      disableMobile: true,
      defaultDate:   initialDate || new Date(),
      appendTo:      document.body,

      onChange: function(dates) {
        if (!dates[0]) return;
        $el.text(window.saturne.utils.formatDateTime(dates[0])).data('changed', false);
        window.saturne.contentEditable.saveField($el, dates[0]);
      },

      onOpen: function(_, __, instance) {
        $wrap.find('.contenteditable-cal-btn').addClass('active');
        requestAnimationFrame(function() {
          const cal  = instance.calendarContainer;
          const rect = $wrap[0].getBoundingClientRect();
          const calW = cal.offsetWidth;
          const calH = cal.offsetHeight;
          const top  = (rect.bottom + calH + 8 > window.innerHeight)
            ? rect.top - calH - 4
            : rect.bottom + 4;
          const left = Math.min(rect.left, window.innerWidth - calW - 8);
          cal.style.position = 'fixed';
          cal.style.top      = top  + 'px';
          cal.style.left     = left + 'px';
          cal.style.zIndex   = '99999';
        });
      },

      onClose: function() {
        $wrap.find('.contenteditable-cal-btn').removeClass('active');
        $el.data('changed', false);
      }
    });

    $wrap.data('fp', fp);
  });
};

/**
 * Ouvre/ferme le Flatpickr au clic sur le bouton calendrier
 *
 * @since   22.0.0
 * @version 22.0.0
 * @return  {void}
 */
window.saturne.contentEditable.onCalBtnClick = function(e) {
  e.stopPropagation();
  const fp = $(this).closest('.contenteditable-wrap').data('fp');
  if (fp) fp.toggle();
};

/**
 * Sauvegarde le champ via AJAX.
 * - Annule la requête précédente si elle est encore en cours (évite les croisements)
 * - Désactive le champ pendant la requête
 * - Réutilisable sur n'importe quel objet Dolibarr via data-element / data-table
 * - Logue les erreurs dans la console avec le contexte
 *
 * @since   22.0.0
 * @version 22.0.0
 *
 * @param  {jQuery} $el    - L'élément .contenteditable
 * @param  {Date}   parsed - La date parsée
 * @return {void}
 */
window.saturne.contentEditable.saveField = function($el, parsed) {
  // Abort la requête précédente si encore en vol
  const prevXhr = $el.data('xhr');
  if (prevXhr) {
    prevXhr.abort();
  }

  // Désactive le champ pendant la requête
  $el.attr('contenteditable', 'false').addClass('ce-saving');

  const xhr = $.ajax({
    url: '/dolibarr/htdocs/custom/saturne/core/ajax/saturne_update_field.php',
    method: 'POST',
    contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
    data: {
      action:     'update_field',
      token:      window.saturne.toolbox.getToken(),
      field:      $el.data('field'),
      element:    $el.data('element'),
      fk_element: $el.data('id'),
      type:       $el.data('type'),
      fieldValue: Math.floor(parsed.getTime())
    }
  })
    .done(function() {
      window.saturne.contentEditable.showFeedback($el, true);
    })
    .fail(function(xhr, status) {
      if (status === 'abort') return; // Annulation volontaire, pas une erreur
      console.error('[contentEditable] Échec sauvegarde', {
        field:   $el.data('field'),
        element: $el.data('element'),
        id:      $el.data('id'),
        status:  status,
        value:   $.trim($el.text())
      });
      window.saturne.contentEditable.showFeedback($el, false);
    })
    .always(function() {
      // Réactive le champ dans tous les cas
      $el.attr('contenteditable', 'true').removeClass('ce-saving');
      $el.data('xhr', null);
    });

  $el.data('xhr', xhr);
};

/**
 * Marque le champ comme modifié
 *
 * @since   22.0.0
 * @version 22.0.0
 * @return  {void}
 */
window.saturne.contentEditable.onInput = function() {
  $(this).data('changed', true);
};

/**
 * Double-clic : entre en mode édition et place le curseur à la fin
 *
 * @since   22.0.0
 * @version 22.0.0
 * @return  {void}
 */
window.saturne.contentEditable.onDblClick = function() {
  const el  = this;
  const sel = window.getSelection();
  const rng = document.createRange();
  rng.selectNodeContents(el);
  rng.collapse(false); // place le curseur à la fin
  sel.removeAllRanges();
  sel.addRange(rng);
};

/**
 * Blur : validation + sauvegarde AJAX
 *
 * @since   22.0.0
 * @version 22.0.0
 * @return  {void}
 */
window.saturne.contentEditable.onBlur = function() {
  const $el    = $(this);
  const value  = $.trim($el.text());
  const parsed = window.saturne.utils.parseDateTime(value);

  if (!$el.data('changed')) return;
  $el.data('changed', false);

  const fp = $el.closest('.contenteditable-wrap').data('fp');

  if (parsed) {
    $el.text(window.saturne.utils.formatDateTime(parsed)).removeClass('invalid');
    if (fp) fp.setDate(parsed, false);
    window.saturne.contentEditable.saveField($el, parsed);
  } else {
    window.saturne.contentEditable.showFeedback($el, false);
  }
};

/**
 * Affiche le feedback :
 *  - Succès : flash bordure verte sur la <td> + icône ✓ qui pop
 *  - Erreur  : shake + bordure rouge sur la <td> + tooltip sur le wrap
 *
 * @since   22.0.0
 * @version 22.0.0
 *
 * @param  {jQuery}  $el
 * @param  {boolean} isValid
 * @return {void}
 */
window.saturne.contentEditable.showFeedback = function($el, isValid) {
  const $wrap = $el.closest('.contenteditable-wrap');
  const $td   = $el.closest('td');

  $td.removeClass('ce-valid ce-invalid');
  $td[0].offsetWidth;
  $td.addClass(isValid ? 'ce-valid' : 'ce-invalid');

  if (isValid) {
    let $icon = $('#ce-feedback-icon');
    if (!$icon.length) {
      $icon = $('<div id="ce-feedback-icon" class="contenteditable-icon">' +
        '<svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">' +
        '<polyline points="20 6 9 17 4 12"/>' +
        '</svg>' +
        '</div>');
      $('body').append($icon);
    }
    const rect = $wrap[0].getBoundingClientRect();
    $icon.css({ top: (rect.top - 10) + 'px', left: (rect.right - 10) + 'px' });
    $icon.removeClass('pop-valid');
    $icon[0].offsetWidth;
    $icon.addClass('pop-valid');
    $icon.one('animationend', function() { $icon.removeClass('pop-valid'); });
  } else {
    const msg = $el.data('error') || 'Format invalide';
    $wrap.attr('data-error-msg', msg).addClass('show-tooltip');
    clearTimeout($wrap.data('tooltipTimer'));
    $wrap.data('tooltipTimer', setTimeout(function() {
      $wrap.removeClass('show-tooltip');
    }, 2500));
  }

  clearTimeout($el.data('feedbackTimer'));
  $el.data('feedbackTimer', setTimeout(function() {
    $td.removeClass('ce-valid ce-invalid');
  }, 1500));
};

/**
 * Focus : mémorise la valeur originale (pour Escape) + reset états visuels
 *
 * @since   22.0.0
 * @version 22.0.0
 * @return  {void}
 */
window.saturne.contentEditable.onFocus = function() {
  const $el = $(this);
  $el.data('originalValue', $el.text());
  $el.removeClass('invalid');
  $el.closest('td').removeClass('ce-valid ce-invalid');
  clearTimeout($el.data('feedbackTimer'));
};

/**
 * Gestion clavier :
 *  - Enter       → sauvegarde (blur)
 *  - Escape      → annule et restaure la valeur originale
 *  - Tab         → sauvegarde et focus le prochain/précédent .contenteditable
 *  - ArrowUp/Down → ±1 jour sur les champs datepicker
 *
 * @since   22.0.0
 * @version 22.0.0
 * @return  {void}
 */
window.saturne.contentEditable.onKeyDown = function(e) {
  const $el = $(this);

  // Enter : sauvegarde
  if (e.key === 'Enter') {
    e.preventDefault();
    e.stopPropagation();
    $el.trigger('blur');
    return;
  }

  // Escape : annule et restaure
  if (e.key === 'Escape') {
    e.preventDefault();
    e.stopPropagation();
    const original = $el.data('originalValue');
    if (original !== undefined) {
      $el.text(original).data('changed', false);
      const fp = $el.closest('.contenteditable-wrap').data('fp');
      if (fp) {
        const parsed = window.saturne.utils.parseDateTime(original);
        if (parsed) fp.setDate(parsed, false);
      }
    }
    $el.blur();
    return;
  }

  // Tab : sauvegarde puis focus le contenteditable suivant ou précédent
  if (e.key === 'Tab') {
    e.preventDefault();
    e.stopPropagation();

    // Déclenche le blur pour sauvegarder si modifié
    $el.trigger('blur');

    // Récupère tous les contenteditable visibles dans l'ordre DOM
    const $all     = $('.contenteditable:visible');
    const idx      = $all.index($el);
    const $next    = e.shiftKey ? $all.eq(idx - 1) : $all.eq(idx + 1);

    if ($next.length) {
      // Petit délai pour laisser le blur/AJAX démarrer avant de changer de focus
      setTimeout(function() { $next.focus(); }, 50);
    }
    return;
  }

  // Flèches ↑↓ : ±1 jour sur les champs datepicker
  if ((e.key === 'ArrowUp' || e.key === 'ArrowDown') && $el.data('type') === 'datepicker') {
    e.preventDefault();
    const current = window.saturne.utils.parseDateTime($.trim($el.text())) || new Date();
    current.setDate(current.getDate() + (e.key === 'ArrowUp' ? 1 : -1));
    $el.text(window.saturne.utils.formatDateTime(current)).data('changed', true);
    const fp = $el.closest('.contenteditable-wrap').data('fp');
    if (fp) fp.setDate(current, false);
  }
};

/**
 * Curseur texte au survol
 *
 * @since   22.0.0
 * @version 22.0.0
 * @return  {void}
 */
window.saturne.contentEditable.onMouseEnter = function() {
  $(this).css('cursor', 'text');
};
