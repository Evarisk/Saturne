/* Copyright (C) 2022-2024 EVARISK <technique@evarisk.com>
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
 * \file    js/modules/toolbox.js
 * \ingroup toolbox
 * \brief   JavaScript file toolbox for module Saturne
 */

/**
 * Init toolbox JS
 *
 * @memberof Saturne_Framework_Toolbox
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.saturne.toolbox = {};

/**
 * Toolbox Init
 *
 * @memberof Saturne_Framework_Toolbox
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.toolbox.init = function() {};

/**
 * Return suitable query separator
 *
 * @memberof Saturne_Framework_Toolbox
 *
 * @since   1.0.0
 * @version 1.3.0
 *
 * @param  {string} url Url of current page
 * @return {string}     Suitable query separator
 */
window.saturne.toolbox.getQuerySeparator = function(url) {
  return url.match(/\?/) ? '&' : "?";
};

/**
 * Replaces encoded anchor characters in the current URL
 *
 * @memberof Saturne_Framework_Toolbox
 *
 * @since   1.6.0
 * @version 1.6.0
 *
 * @return {string} The updated URL with decoded anchor characters
 */
window.saturne.toolbox.replaceUrlAnchor = function() {
  let url = window.location.href;
  return url.replace(/%23/g, '#');
};

/**
 * Return security token value
 *
 * @memberof Saturne_Framework_Toolbox
 *
 * @since   1.0.0
 * @version 1.3.0
 *
 * @return {string} Security token value
 */
window.saturne.toolbox.getToken = function() {
  return $('input[name="token"]').val();
};

/**
 * Toggle button class name
 *
 * @memberof Saturne_Framework_Toolbox
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @param  {string} className       Class name of input/button
 * @param  {string} buttonClassName Button class name to toggle
 * @return {void}
 */
window.saturne.toolbox.toggleButtonClass = function(className, buttonClassName) {
  $('.' + className).toggleClass(buttonClassName);
};

/**
 * Remove and add button class name
 *
 * @memberof Saturne_Framework_Toolbox
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @param  {string} className             Class name of input/button
 * @param  {string} removeButtonClassName Button class name to remove
 * @param  {string} addButtonClassName    Button class name to add
 * @return {void}
 */
window.saturne.toolbox.removeAddButtonClass = function(className, removeButtonClassName, addButtonClassName) {
  $('.' + className).removeClass(removeButtonClassName).addClass(addButtonClassName);
};

/**
 * Check if the iframe is created
 * If the iframe is created, check if the iframe change
 * If the iframe change, reload the page
 *
 * @memberof Saturne_Framework_Toolbox
 *
 * @since   1.5.0
 * @version 1.5.0
 *
 * @returns {void}
 */
window.saturne.toolbox.checkIframeCreation = function() {
  const interval = setInterval(function() {
    if ($('.iframedialog').length) {
      window.saturne.toolbox.checkIframeChange();
      clearInterval(interval);
    }
  }, 100);
};

/**
 * Check if the iframe change
 * If the iframe change, reload the page
 *
 * @memberof Saturne_Framework_Toolbox
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @returns {void}
 */
window.saturne.toolbox.checkIframeChange = function() {
  const iframe = $('.iframedialog')[0];
  let url = iframe.contentWindow.location.href;

  const interval = setInterval(function() {
    if (url !== iframe.contentWindow.location.href) {
      if (url === 'about:blank') {
        url = iframe.contentWindow.location.href;
      } else {
        location.reload();
        clearInterval(interval);
      }
    }
  }, 100);
};
