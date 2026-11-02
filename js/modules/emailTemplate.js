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
 *
 * Library javascript to enable Browser notifications
 */

/**
 * \file    js/modules/emailTemplate.js
 * \ingroup saturne
 * \brief   JavaScript EmailTemplate file for module Saturne
 */

'use strict';

/**
 * Init EmailTemplate
 *
 * @memberof Saturne_EmailTemplate
 *
 * @since   22.0.0
 * @version 22.0.0
 *
 * @type {Object}
 */
window.saturne.emailTemplate = {};

/**
 * EmailTemplate init
 *
 * @memberof Saturne_EmailTemplate
 *
 * @since   22.0.0
 * @version 23.6.0
 *
 * @returns {void}
 */
window.saturne.emailTemplate.init = function() {
  window.saturne.emailTemplate.event();
  window.saturne.emailTemplate.revealHash();
};

/**
 * emailTemplate event
 *
 * @memberof Saturne_EmailTemplate
 *
 * @since   22.0.0
 * @version 23.6.0
 *
 * @returns {void}
 */
window.saturne.emailTemplate.event = function() {
  $(document).on('input', '#saturne-mailtpl-search', window.saturne.emailTemplate.filter);
  $(document).on('change', '#saturne-mailtpl-status, #saturne-mailtpl-used', window.saturne.emailTemplate.filter);
  $(document).on('click', '.saturne-mailtpl-preview-toggle', window.saturne.emailTemplate.togglePreview);
  $(document).on('click', '.saturne-mailtpl-expand', window.saturne.emailTemplate.expandAll);
};

/**
 * Tell whether a template row of the overview (admin/emailtemplates.php) passes the filters
 *
 * @memberof Saturne_EmailTemplate
 *
 * @since   23.6.0
 * @version 23.6.0
 *
 * @param {jQuery}   $row   Template row
 * @param {string[]} words  Searched words, lower case: each of them must be found
 * @param {string}   status '' for any status, '1' active, '0' inactive
 * @param {boolean}  used   Only the templates designated by a setting
 * @returns {boolean}
 */
window.saturne.emailTemplate.rowMatches = function($row, words, status, used) {
  // attr() and not data(): jQuery would parse a value starting with "{" or "[" as JSON
  const haystack = $row.attr('data-search') || '';

  if (status !== '' && $row.attr('data-active') !== status) {
    return false;
  }
  if (used && $row.attr('data-used') !== '1') {
    return false;
  }

  return words.every(word => haystack.indexOf(word) !== -1);
};

/**
 * Filter the overview: hide the rows and groups left out, open the groups holding a match
 *
 * @memberof Saturne_EmailTemplate
 *
 * @since   23.6.0
 * @version 23.6.0
 *
 * @returns {void}
 */
window.saturne.emailTemplate.filter = function() {
  const words     = ($('#saturne-mailtpl-search').val() || '').toLowerCase().split(/\s+/).filter(word => word !== '');
  const status    = $('#saturne-mailtpl-status').val() || '';
  const used      = $('#saturne-mailtpl-used').is(':checked');
  const filtering = words.length > 0 || status !== '' || used;
  let shown       = 0;

  $('.saturne-mailtpl-group').each(function() {
    const $group   = $(this);
    let groupShown = 0;

    $group.find('.saturne-mailtpl-row').each(function() {
      const $row    = $(this);
      const visible = window.saturne.emailTemplate.rowMatches($row, words, status, used);

      $row.toggleClass('hidden', !visible);
      if (!visible) {
        $row.next('.saturne-mailtpl-preview').addClass('hidden');
        $row.removeClass('saturne-mailtpl-row--open');
      }
      groupShown += visible ? 1 : 0;
    });

    $group.toggleClass('hidden', groupShown === 0);
    if (filtering) {
      $group.prop('open', groupShown > 0);
    }
    shown += groupShown;
  });

  $('.saturne-mailtpl-summary__count').text(shown);
  $('.saturne-mailtpl-summary__filtered').toggleClass('hidden', !filtering);
  $('.saturne-mailtpl-noresult').toggleClass('hidden', shown > 0);
};

/**
 * Show or hide the preview of a template, filled on first opening
 *
 * @memberof Saturne_EmailTemplate
 *
 * @since   23.6.0
 * @version 23.6.0
 *
 * @param {Event} event Click on the label or the preview icon
 * @returns {void}
 */
window.saturne.emailTemplate.togglePreview = function(event) {
  event.preventDefault();

  const $row     = $(this).closest('.saturne-mailtpl-row');
  const $preview = $row.next('.saturne-mailtpl-preview');
  const $content = $preview.find('.saturne-mailtpl-preview__content');

  // The content waits in a <template>, so its images are only downloaded when the preview is opened
  if ($content.is(':empty')) {
    $content.html($preview.find('.saturne-mailtpl-preview__source').html());
  }

  $preview.toggleClass('hidden');
  $row.toggleClass('saturne-mailtpl-row--open', !$preview.hasClass('hidden'));
};

/**
 * Open or close every group of the overview still shown by the filters
 *
 * @memberof Saturne_EmailTemplate
 *
 * @since   23.6.0
 * @version 23.6.0
 *
 * @returns {void}
 */
window.saturne.emailTemplate.expandAll = function() {
  $('.saturne-mailtpl-group:not(.hidden)').prop('open', $(this).attr('data-open') === '1');
};

/**
 * Open the group of the template named by the URL fragment (#mailtpl-ID), set after (de)activating it
 *
 * @memberof Saturne_EmailTemplate
 *
 * @since   23.6.0
 * @version 23.6.0
 *
 * @returns {void}
 */
window.saturne.emailTemplate.revealHash = function() {
  const match = window.location.hash.match(/^#mailtpl-(\d+)$/);
  const $row  = match ? $('#mailtpl-' + match[1]) : $();

  if ($row.length === 0) {
    return;
  }

  $row.closest('.saturne-mailtpl-group').prop('open', true);
  $row.addClass('saturne-mailtpl-row--highlight');
  $row[0].scrollIntoView({block: 'center'});
};

/**
 * emailTemplate updateSub
 *
 * @since   22.0.0
 * @version 22.0.0
 *
 * @return {void}
 */
window.saturne.emailTemplate.updateSub = function() {
    const url = new URL(window.location.href);
    url.searchParams.set('action', 'updateSub');
    url.searchParams.set('type_template', $(this).val());

    $.ajax({
        url: url.toString(),
        method: 'GET',
        success: function (response) {
            data = JSON.parse(response);

            $box = $('#idfortooltiponclick_content span')
            $extraField = $box.find('.extrafield')

            if ($extraField.length === 0) {
                $extraField = $('<div class="extrafield"></div>');
                $('#idfortooltiponclick_content span').append($extraField);
            } else {
                $extraField.empty();
            }

            $extraField.append(
                $('<br>'),
                $('<strong>Extrafields</strong>')
            );

            data.forEach(item => {
                $extraField.append(
                    $('<div></div>').text(item)
                );
            });
        }
    });
};