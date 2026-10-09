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
  $(document).on('click', '.signature-confirmation-close, .page-signature .confirmation-close', window.saturne.signature.closeConfirmation);
  var scriptElement = document.querySelector('script[src*="signature-pad.min.js"]');
  if (scriptElement) {
    window.saturne.signature.drawSignatureOnCanvas();
  }
  $(document).on('touchstart mousedown', '.canvas-signature', function () {
    if ( !$('input#validate_text_checkbox').length ||
         ($('input#validate_text_checkbox').length > 0 && $('input#validate_text_checkbox').is(':checked'))) {
      window.saturne.toolbox.removeAddButtonClass('signature-validate', 'button-grey button-disable', 'button-blue');
    }
  });
  $(document).on('change', 'input#validate_text_checkbox', function() {
    if ($(this).is(':checked') && !window.saturne.signature.canvas.signaturePad.isEmpty()) {
      window.saturne.toolbox.removeAddButtonClass('signature-validate', 'button-grey button-disable', 'button-blue');
    } else {
      window.saturne.toolbox.removeAddButtonClass('signature-validate', 'button-blue', 'button-grey button-disable');
    }
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
    // The user card signature is reused in documents : framed on the stroke it no longer ends up
    // as a tiny scribble in the corner of an empty image
    var signature = $(window.saturne.signature.canvas).closest('.user-signature-modal').length ?
      window.saturne.signature.getTrimmedDataURL(window.saturne.signature.canvas) :
      window.saturne.signature.canvas.toDataURL();
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
        $('.public-card__container').replaceWith($(resp).find('.public-card__container'));
      } else {
        window.location.reload();
      }
    },
    error: function() {
      // The refusal is queued as an event message : reloading displays it, instead of leaving the
      // loader spinning forever
      if ($('.public-card__container').data('public-interface') !== true) {
        window.location.reload();
      }
    }
  });
};

/**
 * Get the signature framed on the drawn stroke, as a data URL
 *
 * The frame keeps the proportions of the canvas : documents resize the image to a fixed width
 * while keeping its ratio, a frame tight on a vertical stroke would come out oversized there
 *
 * @memberof Saturne_Framework_Signature
 *
 * @since   23.2.1
 * @version 23.2.1
 *
 * @param  {HTMLCanvasElement} canvas Signature canvas
 * @return {string}                   PNG data URL
 */
window.saturne.signature.getTrimmedDataURL = function(canvas) {
  let width  = canvas.width;
  let height = canvas.height;
  let pixels = canvas.getContext('2d').getImageData(0, 0, width, height).data;

  let minX = width;
  let minY = height;
  let maxX = -1;
  let maxY = -1;
  for (let y = 0; y < height; y++) {
    for (let x = 0; x < width; x++) {
      if (pixels[(y * width + x) * 4 + 3] > 0) {
        minX = Math.min(minX, x);
        maxX = Math.max(maxX, x);
        minY = Math.min(minY, y);
        maxY = Math.max(maxY, y);
      }
    }
  }
  if (maxX < 0) {
    return canvas.toDataURL();
  }

  // A margin around the stroke, then the frame grows on its short side to the canvas ratio
  let margin     = Math.round(Math.max(maxX - minX, maxY - minY) * 0.08) + 4;
  let cropWidth  = maxX - minX + 1 + 2 * margin;
  let cropHeight = maxY - minY + 1 + 2 * margin;
  let ratio      = width / height;
  // Documents enlarge the image to a fixed width : a tiny stroke must not come out pixelated
  cropWidth = Math.max(cropWidth, Math.round(width * 0.4));
  if (cropWidth / cropHeight < ratio) {
    cropWidth = Math.round(cropHeight * ratio);
  } else {
    cropHeight = Math.round(cropWidth / ratio);
  }
  cropWidth  = Math.min(cropWidth, width);
  cropHeight = Math.min(cropHeight, height);

  let centerX = (minX + maxX) / 2;
  let centerY = (minY + maxY) / 2;
  let cropX   = Math.round(Math.min(Math.max(centerX - cropWidth / 2, 0), width - cropWidth));
  let cropY   = Math.round(Math.min(Math.max(centerY - cropHeight / 2, 0), height - cropHeight));

  let trimmed    = document.createElement('canvas');
  trimmed.width  = cropWidth;
  trimmed.height = cropHeight;
  trimmed.getContext('2d').drawImage(canvas, cropX, cropY, cropWidth, cropHeight, 0, 0, cropWidth, cropHeight);

  return trimmed.toDataURL();
};

/**
 * Close signature confirmation panel
 *
 * @memberof Saturne_Framework_Signature
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @return {void}
 */
window.saturne.signature.closeConfirmation = function() {
  $('.card__confirmation').attr('style', 'display: none;');

  // Le lien de signature arrive par mail : l'onglet n'ayant pas ete ouvert par un script, le
  // navigateur refuse window.close() et le bouton ne rendait plus la main. Refermer le panneau est
  // le seul retour garanti, la fermeture de l'onglet ne reste qu'un bonus quand elle est autorisee.
  window.close();
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
