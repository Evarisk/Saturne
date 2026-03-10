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

/**
 * Init contentEditable JS
 *
 * @since   22.0.0
 * @version 22.0.0
 */
window.saturne.contentEditable = {};

/**
 * contentEditable init
 *
 * @since   22.0.0
 * @version 22.0.0
 *
 * @return {void}
 */
window.saturne.contentEditable.init = function init() {
  window.saturne.contentEditable.event();
};

/**
 * contentEditable event initialization. Binds all necessary event listeners
 *
 * @since   22.0.0
 * @version 22.0.0
 *
 * @return {void}
 */
window.saturne.contentEditable.event = function initializeEvents() {
  $(document)
    .on('blur',       '.contenteditable', window.saturne.contentEditable.onBlur)
    .on('focus',      '.contenteditable', window.saturne.contentEditable.onFocus)
    .on('input',      '.contenteditable', window.saturne.contentEditable.onInput)
    .on('keydown',    '.contenteditable', window.saturne.contentEditable.onKeyDown)
    .on('mouseenter', '.contenteditable', window.saturne.contentEditable.onMouseEnter)
    .on('click',      '.contenteditable-cal-btn', window.saturne.contentEditable.onCalBtnClick);

  window.saturne.contentEditable.initFlatpickr();
};

/**
 * Initialise Flatpickr sur tous les champs contenteditable de type datepicker.
 * Append au body pour éviter tout overflow/z-index parent.
 * Positionne le calendrier via requestAnimationFrame (après rendu Flatpickr).
 *
 * @since   22.0.0
 * @version 22.0.0
 *
 * @return {void}
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

        // Sauvegarde AJAX directe, sans passer par onBlur
        $.ajax({
          url: '/dolibarr/htdocs/custom/saturne/core/ajax/saturne_update_field.php',
          method: 'POST',
          contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
          data: {
            action:     'update_field',
            token:      window.saturne.toolbox.getToken(),
            field:      $el.data('field'),
            element:    'trainingsession',
            fk_element: $el.data('id'),
            type:       'datepicker',
            fieldValue: Math.floor(dates[0].getTime())
          }
        })
          .done(function() { window.saturne.contentEditable.showFeedback($el, true); })
          .fail(function() { window.saturne.contentEditable.showFeedback($el, false); });
      },

      onOpen: function(_, __, instance) {
        $wrap.find('.contenteditable-cal-btn').addClass('active');

        // requestAnimationFrame : attend que Flatpickr ait fini de rendre
        // le calendrier avant de lire offsetWidth/Height (sinon = 0)
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
        // Planifie le retrait du feedback après 2.2s
        clearTimeout($el.data('feedbackTimer'));
        $el.data('feedbackTimer', setTimeout(function() {
          $el.removeClass('is-valid is-invalid').data('changed', false);
          $wrap.find('.contenteditable-message').removeClass('visible');
        }, 2200));
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
 *
 * @return {void}
 */
window.saturne.contentEditable.onCalBtnClick = function(e) {
  e.stopPropagation();
  const fp = $(this).closest('.contenteditable-wrap').data('fp');
  if (fp) fp.toggle();
};

/**
 * Marque l'élément comme modifié dès qu'une saisie a lieu
 *
 * @since   22.0.0
 * @version 22.0.0
 *
 * @return {void}
 */
window.saturne.contentEditable.onInput = function() {
  $(this).data('changed', true);
};

/**
 * Gestion du blur : validation, AJAX save et feedback visuel
 *
 * @since   22.0.0
 * @version 22.0.0
 *
 * @return {void}
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

    $.ajax({
      url: '/dolibarr/htdocs/custom/saturne/core/ajax/saturne_update_field.php',
      method: 'POST',
      contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
      data: {
        action:     'update_field',
        token:      window.saturne.toolbox.getToken(),
        field:      $el.data('field'),
        element:    'trainingsession',
        fk_element: $el.data('id'),
        type:       'datepicker',
        fieldValue: Math.floor(parsed.getTime())
      }
    })
      .done(function() { window.saturne.contentEditable.showFeedback($el, true); })
      .fail(function() { window.saturne.contentEditable.showFeedback($el, false); });

  } else {
    $el.addClass('invalid');
    window.saturne.contentEditable.showFeedback($el, false);
  }
};

/**
 * Affiche le feedback visuel succès ou erreur.
 * L'icône est positionnée en fixed via getBoundingClientRect.
 * L'outline is-valid/is-invalid est retiré après 2.2s.
 *
 * @since   22.0.0
 * @version 22.0.0
 *
 * @param  {jQuery}  $el      - L'élément .contenteditable ciblé
 * @param  {boolean} isValid  - true = succès, false = erreur
 * @return {void}
 */
window.saturne.contentEditable.showFeedback = function($el, isValid) {
  const $wrap = $el.closest('.contenteditable-wrap');
  if (!$wrap.length) return;

  const $icon = $wrap.find('.contenteditable-icon');
  const $msg  = $wrap.find('.contenteditable-message');

  const msgSuccess = $el.data('success') || 'Enregistré';
  const msgError   = $el.data('error')   || 'Format invalide';

  // ── Positionne l'icône en fixed au coin haut-droit du wrap ──
  const rect = $wrap[0].getBoundingClientRect();
  $icon.css({
    position: 'fixed',
    top:      (rect.top  - 11) + 'px',
    left:     (rect.right - 11) + 'px',
    zIndex:   9999
  });

  // ── Outline champ ──
  $el.removeClass('is-valid is-invalid');
  $el[0].offsetWidth; // reflow
  $el.addClass(isValid ? 'is-valid' : 'is-invalid');

  // ── Icône ──
  $icon.removeClass('pop-valid pop-invalid');
  $icon[0].offsetWidth;
  $icon.addClass(isValid ? 'pop-valid' : 'pop-invalid');
  $icon.one('animationend', function() {
    $icon.removeClass('pop-valid pop-invalid');
  });

  // ── Message ──
  $msg
    .text(isValid ? msgSuccess : msgError)
    .removeClass('msg-valid msg-invalid visible')
    .addClass((isValid ? 'msg-valid' : 'msg-invalid') + ' visible');

  // ── Nettoyage : retire is-valid/is-invalid après 2.2s ──
  clearTimeout($el.data('feedbackTimer'));
  $el.data('feedbackTimer', setTimeout(function() {
    $el.removeClass('is-valid is-invalid');
    $msg.removeClass('visible');
  }, 2200));
};

/**
 * Gestion du focus : reset états visuels
 *
 * @since   22.0.0
 * @version 22.0.0
 *
 * @return {void}
 */
window.saturne.contentEditable.onFocus = function() {
  $(this).removeClass('invalid is-valid is-invalid').addClass('active');
};

/**
 * Gestion des flèches haut/bas pour incrémenter/décrémenter la date
 * Enter = sauvegarde immédiate
 *
 * @since   22.0.0
 * @version 22.0.0
 *
 * @return {void}
 */
window.saturne.contentEditable.onKeyDown = function(e) {
  if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
    e.preventDefault();
    const $el     = $(this);
    const current = window.saturne.utils.parseDateTime($.trim($el.text())) || new Date();
    const delta   = e.key === 'ArrowUp' ? 1 : -1;
    current.setDate(current.getDate() + delta);
    $el.text(window.saturne.utils.formatDateTime(current)).data('changed', true);

    const fp = $el.closest('.contenteditable-wrap').data('fp');
    if (fp) fp.setDate(current, false);
  }

  if (e.key === 'Enter') {
    e.preventDefault();
    $(this).trigger('blur');
  }
};

/**
 * Curseur texte au survol
 *
 * @since   22.0.0
 * @version 22.0.0
 *
 * @return {void}
 */
window.saturne.contentEditable.onMouseEnter = function() {
  $(this).css('cursor', 'text');
};
