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
 * \file    js/modules/document.js
 * \ingroup saturne
 * \brief   JavaScript file document for module Saturne.
 */


/**
 * Initialise l'objet "document" ainsi que la méthode "init" obligatoire pour la bibliothèque Saturne.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.saturne.document = {};

/**
 * La méthode appelée automatiquement par la bibliothèque Saturne.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.document.init = function() {
	window.saturne.document.event();
};

/**
 * La méthode contenant tous les événements pour les documents.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.document.event = function() {
  $(document).on('click', '#builddoc_generatebutton', window.saturne.document.displayLoader);
  $(document).on('click', '.pdf-generation', window.saturne.document.displayLoader);
  $(document).on('click', '.download-template', window.saturne.document.autoDownloadTemplate);
};

/**
 * Display loader on generation document.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.document.displayLoader = function(  ) {
	window.saturne.loader.display($(this).closest('.div-table-responsive-no-min'));
};

/**
 * Auto download document template
 *
 * @memberof Saturne_Framework_Document
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.saturne.document.autoDownloadTemplate = function() {
  let token          = window.saturne.toolbox.getToken();
  let url            = document.URL.replace(/#.*$/, '');
  let querySeparator = window.saturne.toolbox.getQuerySeparator(url);
  let element        = $(this).closest('.file-generation');
  let type           = element.find('.template-type').attr('value');
  let filename       = element.find('.template-name').attr('value');

  $.ajax({
    url: url + querySeparator + 'action=download_template&filename=' + filename + '&type=' + type + '&token=' + token,
    type: 'POST',
    success: function() {
      let path = element.find('.template-path').attr('value');
      window.saturne.signature.download(path + filename, filename);
      $.ajax({
        url: document.URL + querySeparator + 'action=remove_file&filename=' + filename + '&token=' + token,
        type: 'POST',
        success: function () {},
        error: function() {}
      });
    },
    error: function () {}
  });
};
