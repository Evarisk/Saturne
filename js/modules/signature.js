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
 * \file    js/modules/signature.js
 * \ingroup saturne
 * \brief   JavaScript file signature for module Saturne
 */

/**
 * Init signature JS
 *
 * @memberof Saturne_Framework_Signature
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.signature = {};

/**
 * Init signature canvas
 *
 * @memberof Saturne_Framework_Signature
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.saturne.signature.canvas = {};

/**
 * Signature Init
 *
 * @memberof Saturne_Framework_Signature
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.signature.init = function() {
    window.saturne.signature.event();
};

/**
 * Signature event
 *
 * @memberof Saturne_Framework_Signature
 *
 * @since   1.0.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.saturne.signature.event = function() {
  $(document).on('click', '.signature-erase', window.saturne.signature.clearCanvas);
  $(document).on('click', '.signature-validate:not(.button-disable)', window.saturne.signature.createSignature);
  $(document).on('click', '.auto-download', window.saturne.signature.autoDownloadSpecimen);
  $(document).on('click', '.copy-signatureurl', window.saturne.signature.copySignatureUrlClipboard);
  $(document).on('click', '.set-attendance', window.saturne.signature.setAttendance);
  var scriptElement = document.querySelector('script[src*="signature-pad.min.js"]');
  if (scriptElement) {
    window.saturne.signature.drawSignatureOnCanvas();
  }
  $(document).on('touchstart mousedown', '.canvas-signature', function () {
    window.saturne.toolbox.removeAddButtonClass('signature-validate', 'button-grey button-disable', 'button-blue');
  });
};

/**
 * Draw signature on canvas
 *
 * @memberof Saturne_Framework_Signature
 *
 * @since   1.0.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.saturne.signature.drawSignatureOnCanvas = function() {
  window.saturne.signature.canvas = document.querySelector('.canvas-signature');
  if (window.saturne.signature.canvas) {
    let ratio = Math.max(window.devicePixelRatio || 1, 1);
    window.saturne.signature.canvas.signaturePad = new SignaturePad(window.saturne.signature.canvas, {
      penColor: 'rgb(0, 0, 0)'
    });

    window.saturne.signature.canvas.width = window.saturne.signature.canvas.offsetWidth * ratio;
    window.saturne.signature.canvas.height = window.saturne.signature.canvas.offsetHeight * ratio;
    window.saturne.signature.canvas.getContext('2d').scale(ratio, ratio);
  }
};

/**
 * Clear sign action
 *
 * @memberof Saturne_Framework_Signature
 *
 * @since   1.0.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.saturne.signature.clearCanvas = function() {
  window.saturne.signature.canvas.signaturePad.clear();
  window.saturne.toolbox.removeAddButtonClass('signature-validate', 'button-blue', 'button-grey button-disable');
};

/**
 * Create signature action
 *
 * @memberof Saturne_Framework_Signature
 *
 * @since   1.0.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.saturne.signature.createSignature = function() {
  let token          = window.saturne.toolbox.getToken();
  let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);

  if (!window.saturne.signature.canvas.signaturePad.isEmpty()) {
    var signature = window.saturne.signature.canvas.toDataURL();
  }

  window.saturne.loader.display($(this));

  $.ajax({
    url: document.URL + querySeparator + 'action=add_signature&token=' + token,
    type: 'POST',
    processData: false,
    contentType: 'application/octet-stream',
    data: JSON.stringify({
      signature: signature
    }),
    success: function(resp) {
      if ($('.public-card__container').data('public-interface') === true) {
        $('.card__confirmation').removeAttr('style');
        $('.signature-confirmation-close').attr('onclick', 'window.close()');
        $('.public-card__container').replaceWith($(resp).find('.public-card__container'));
      } else {
        window.location.reload();
      }
    },
    error: function() {}
  });
};

/**
 * Download signature
 *
 * @memberof Saturne_Framework_Signature
 *
 * @since   1.0.0
 * @version 1.3.0
 *
 * @param  {string} fileUrl  Url of file to download
 * @param  {string} filename Name of file to download
 * @return {void}
 */
window.saturne.signature.download = function(fileUrl, filename) {
  let a  = document.createElement('a');
  a.href = fileUrl;
  a.setAttribute('download', filename);
  a.click();
};

/**
 * Auto Download signature specimen
 *
 * @memberof Saturne_Framework_Signature
 *
 * @since   1.0.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.saturne.signature.autoDownloadSpecimen = function() {
  let element        = $(this).closest('.file-generation');
  let token          = window.saturne.toolbox.getToken();
  let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);

  $.ajax({
    url: document.URL + querySeparator + 'action=builddoc&token=' + token,
    type: 'POST',
    success: function(resp) {
      let filename = element.find('.specimen-name').attr('data-specimen-name');
      let path     = element.find('.specimen-path').attr('data-specimen-path');
      window.saturne.signature.download(path + filename, filename);
      $('.file-generation').replaceWith($(resp).find('.file-generation'));
      $.ajax({
          url: document.URL + querySeparator + 'action=remove_file&token=' + token,
          type: 'POST',
          success: function() {},
          error: function() {}
      });
    },
    error: function() {}
  });
};

/**
 * Copy signature url in clipboard
 *
 * @memberof Saturne_Framework_Signature
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.signature.copySignatureUrlClipboard = function() {
  let signatureUrl = $(this).attr('data-signature-url');
  navigator.clipboard.writeText(signatureUrl).then(() => {
    $(this).attr('class', 'fas fa-check copy-signatureurl');
    $(this).css('color', '#59ed9c');
    $(this).closest('.copy-signatureurl-container').find('.copied-to-clipboard').attr('style', '');
    $(this).closest('.copy-signatureurl-container').find('.copied-to-clipboard').fadeOut(2500, () => {
      $(this).attr('class', 'fas fa-clipboard copy-signatureurl');
      $(this).css('color', '#666');
    });
  });
};

/**
 * Set attendance signatory
 *
 * @memberof Saturne_Framework_Signature
 *
 * @since   1.0.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.saturne.signature.setAttendance = function() {
  let signatoryID       = $(this).closest('.attendance-container').find('input[name="signatoryID"]').val();
  let attendance        = $(this).attr('value');
  let token             = window.saturne.toolbox.getToken();
  let querySeparator    = window.saturne.toolbox.getQuerySeparator(document.URL);
  let urlWithoutHashtag = String(document.location.href).replace(/#formmail/, "");

  $.ajax({
    url: urlWithoutHashtag + querySeparator + 'action=set_attendance&token=' + token,
    type: 'POST',
    processData: false,
    contentType: '',
    data: JSON.stringify({
      signatoryID: signatoryID,
      attendance: attendance
    }),
    success: function(resp) {
      $('.signatures-container').html($(resp).find('.signatures-container'));
    },
    error: function() {}
  });
};
