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
 * \file    js/modules/utils.js
 * \ingroup saturne
 * \brief   JavaScript utils file for module Saturne
 */

/**
 * Init utils JS
 *
 * @memberof Saturne_Utils
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @type {Object}
 */
window.saturne.utils = {};

/**
 * Flag indicating whether the user's timezone is already stored in the PHP session.
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @type {boolean}
 */
window.saturne.utils.timezoneDefined = false;

/**
 * Utils init
 *
 * @memberof Saturne_Utils
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void}
 */
window.saturne.utils.init = function() {
  window.saturne.utils.event();
};

/**
 * Utils event
 *
 * @memberof Saturne_Utils
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void}
 */
window.saturne.utils.event = function() {
  $(document).on('mouseenter', '.move-line.ui-sortable-handle', window.saturne.utils.draganddrop);
  //$(document).on('change', '#element_type', window.saturne.utils.reloadField);
};

/**
 * Drag ana drop on move-line action
 *
 * @memberof Saturne_Utils
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.utils.draganddrop = function() {
  $(this).css('cursor', 'pointer');

  $('#tablelines tbody').sortable();
  $('#tablelines tbody').sortable({
    handle: '.move-line',
    connectWith:'#tablelines tbody .line-row',
    tolerance:'intersect',
    over:function(){
      $(this).css('cursor', 'grabbing');
    },
    stop: function() {
      $(this).css('cursor', 'default');
      let token = $('.fiche').find('input[name="token"]').val();

      let separator = '&'
      if (document.URL.match(/action=/)) {
        document.URL = document.URL.split(/\?/)[0]
        separator = '?'
      }
      let lineOrder = [];
      $('.line-row').each(function(  ) {
        lineOrder.push($(this).attr('id'));
      });
      $.ajax({
        url: document.URL + separator + "action=moveLine&token=" + token,
        type: "POST",
        data: JSON.stringify({
          order: lineOrder
        }),
        processData: false,
        contentType: false,
        success: function () {},
        error: function() {}
      });
    }
  });
};

/**
 * Reload page for ajax action on specific action
 *
 * @memberof Saturne_Utils
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @param {string}                                         action          Html action use for php
 * @param {string}                                         page            Class page use on resp for reload
 * @param {string}                                         urlMoreParams   Array for managing custom url parameters
 * @param {{removeAttr: {value: string, element: string}}} checkMoreParams Array for managing custom parameters
 *
 * @returns {void}
 */
window.saturne.utils.reloadPage = function(action, page, urlMoreParams = '', checkMoreParams = '') {
  let token          = window.saturne.toolbox.getToken();
  let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);

  $.ajax({
    url: document.URL + querySeparator + 'action=' + action + urlMoreParams + '&token=' + token,
    type: 'POST',
    processData: false,
    contentType: false,
    success: function(resp) {
      window.saturne.utils.checkMoreParams(checkMoreParams);
      $(page).replaceWith($(resp).find(page));
    },
    error: function() {}
  });
};

/**
 * Reload specific field element_type and fk_element
 *
 * @memberof Saturne_Utils
 *
 * @since   1.2.0
 * @version 1.2.0
 *
 * @returns {void}
 */
window.saturne.utils.reloadField = function() {
  let field          = $(this).val();
  let token          = window.saturne.toolbox.getToken();
  let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);

  window.saturne.loader.display($('.field_element_type'));
  window.saturne.loader.display($('.field_fk_element'));

  $.ajax({
    url: document.URL + querySeparator + "element_type=" + field + "&token=" + token,
    type: 'POST',
    processData: false,
    contentType: false,
    success: function(resp) {
      $('.field_element_type').replaceWith($(resp).find('.field_element_type'));
      $('.field_fk_element').replaceWith($(resp).find('.field_fk_element'));
    },
    error: function() {}
  });
};

/**
 * Enforce min and max value on keyup event for field input
 *
 * @memberof Saturne_Utils
 *
 * @since   1.2.1
 * @version 1.2.1
 *
 * @returns {void}
 */
window.saturne.utils.enforceMinMax = function(triggeredElement) {
  if (triggeredElement.value !== "") {
    if (parseInt(triggeredElement.value) < parseInt(triggeredElement.min)) {
      triggeredElement.value = triggeredElement.min;
    }
    if (parseInt(triggeredElement.value) > parseInt(triggeredElement.max)) {
      triggeredElement.value = triggeredElement.max;
    }
  }
};

/**
 * Check more parameters for manage visibility of element / remove elements
 *
 * @memberof Saturne_Utils
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @typedef  {Object} RemoveAttrParams
 * @property {string} element - Selector for the element
 * @property {string} value - Attribute value to remove
 *
 * @param    {Object}           checkMoreParams - Object for managing custom parameters
 * @property {RemoveAttrParams} checkMoreParams.removeAttr - Information to remove attribute
 *
 * @returns {void}
 */
window.saturne.utils.checkMoreParams = function(checkMoreParams) {
  if (checkMoreParams && checkMoreParams.removeAttr) {
    $(checkMoreParams.removeAttr.element).removeAttr(checkMoreParams.removeAttr.value);
  }
};

/**
 * Toggles a configuration setting based on a button state and updates the UI dynamically
 *
 * This function is used to send an AJAX request to toggle a specific setting and dynamically
 * update the relevant UI elements based on the response
 *
 * @memberof Saturne_Utils
 *
 * @since   1.8.0
 * @version 1.8.0
 *
 * @param {string} action  - The action name to send in the AJAX request
 * @param {string} dataKey - The key name for the data payload
 */
window.saturne.utils.toggleSetting = function(action, dataKey) {
  // Store the current button and retrieve the query parameters
  let $button        = $(this);
  let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);
  let token          = window.saturne.toolbox.getToken();
  let newValue       = $button.hasClass('fa-toggle-off') ? 1 : 0;

  // Get the list of elements to update after the AJAX response, from the data-update-targets attribute
  let updateTargets  = $button.data('update-targets')?.split(',') || []; // Defaults to empty if no targets specified

  // Show the loading animation for the button
  window.saturne.loader.display($button);

  // Perform the AJAX request
  $.ajax({
    url: `${document.URL}${querySeparator}action=${action}&token=${token}`,
    type: 'POST',
    processData: false,
    data: JSON.stringify({
      [dataKey]: newValue
    }),
    contentType: false,
    success: function(resp) {
      // Loop through each element in the updateTargets array
      updateTargets.forEach(selector => {
        // Find the new content in the response
        let $newContent = $(resp).find(selector);

        // If the new content exists, replace the old content with the new one
        if ($newContent.length) {
          $(selector).replaceWith($newContent);
        }
      });
    },
    error: function() {}
  });
};

/**
 * Helper function to get an input's value, ensuring it's a number and within 0-100.
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @param  {jQuery} $input The jQuery object of the input field.
 * @return {number} The sanitized number value.
 */
window.saturne.utils.getSanitizedPercentageValue = function($input) {
  let value = parseFloat($input.val());
  if (isNaN(value)) {
    value = 0;
  }
  return Math.max(0, Math.min(100, value));
};

/**
 * Helper function to get the browser's timezone in IANA format.
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @return {string} The detected timezone (e.g. "Europe/Paris").
 */
window.saturne.utils.getBrowserTimezone = function getBrowserTimezone() {
  try {
    return Intl.DateTimeFormat().resolvedOptions().timeZone;
  } catch (e) {
    return 'UTC';
  }
};


/**
 * Helper function to send the browser's timezone to the server via POST
 * so it can be stored in a PHP session.
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @param  {string} timezone The timezone to send to the server.
 * @return {Promise} A jQuery Promise that resolves when the request is done.
 */
window.saturne.utils.storeTimezoneInSession = function(timezone) {
  return $.post(window.location.href, { tz: timezone });
};

/**
 * Helper function to ensure the timezone is stored in the PHP session.
 * If not, sends it via AJAX and reloads the page.
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @return {void}
 */
window.saturne.utils.ensureTimezoneInSession = function() {
  if (!window.saturne.utils.timezoneDefined) {
    const tz = window.saturne.utils.getBrowserTimezone();
    window.saturne.utils.storeTimezoneInSession(tz)
      .done(function() {
        location.reload();
      });
  }
};

/**
 * Helper function to ensure the timezone is stored in the PHP session.
 * If not, sends it via AJAX and reloads the page.
 *
 * @since   22.0.0
 * @version 22.0.0
 */
window.saturne.utils.parseDateTime = function(str) {
  // Format attendu : dd/mm/yyyy hh:mm (secondes optionnelles)
  const regex = /^(\d{2})\/(\d{2})\/(\d{4})(?:\s+(\d{2}):(\d{2})(?::(\d{2}))?)?$/;
  const match = str.match(regex);
  if (!match) return null;
  const [, d, m, y, hh = '00', mm = '00', ss = '00'] = match;
  const date = new Date(`${y}-${m}-${d}T${hh}:${mm}:${ss}`);
  return isNaN(date.getTime()) ? null : date;
};

/**
 * Helper function to ensure the timezone is stored in the PHP session.
 * If not, sends it via AJAX and reloads the page.
 *
 * @since   22.0.0
 * @version 22.0.0
 */
window.saturne.utils.formatDateTime = function(date) {
  const d  = String(date.getDate()).padStart(2, '0');
  const m  = String(date.getMonth() + 1).padStart(2, '0');
  const y  = date.getFullYear();
  const hh = String(date.getHours()).padStart(2, '0');
  const mm = String(date.getMinutes()).padStart(2, '0');
  return `${d}/${m}/${y} ${hh}:${mm}`;
};
