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
 * \file    js/modules/signature.js
 * \ingroup saturne
 * \brief   JavaScript file signature for module Saturne.
 */

/*
 * Gestion des signatures.
 *
 * @since   1.0.0
 * @version 1.0.0
 */


/**
 * Initialise l'objet "signature" ainsi que la méthode "init" obligatoire pour la bibliothèque Saturne.
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
 * Initialise le canvas signature
 *
 * @memberof Saturne_Framework_Signature
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.saturne.signature.canvas;

/**
 * Initialise le boutton signature
 *
 * @memberof Saturne_Framework_Signature
 *
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.saturne.signature.buttonSignature;

/**
 * La méthode appelée automatiquement par la bibliothèque Saturne.
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
 * La méthode contenant tous les événements pour la signature.
 *
 * @memberof Saturne_Framework_Signature
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.signature.event = function() {
    $(document).on('click', '.signature-erase', window.saturne.signature.clearCanvas);
    $(document).on('click', '.signature-validate', window.saturne.signature.createSignature);
    $(document).on('click', '.auto-download', window.saturne.signature.autoDownloadSpecimen);
    $(document).on('click', '.copy-signatureurl', window.saturne.signature.copySignatureUrlClipboard);
    $(document).on('click', '.set-attendance', window.saturne.signature.setAttendance);
};

/**
 * Open modal signature
 *
 * @memberof Saturne_Framework_Signature
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.signature.modalSignatureOpened = function(triggeredElement) {
    window.saturne.signature.buttonSignature = triggeredElement;

    let ratio =  Math.max(window.devicePixelRatio || 1, 1);
    window.saturne.signature.canvas = document.querySelector('#modal-signature' + triggeredElement.attr('value') + ' canvas');

    window.saturne.signature.canvas.signaturePad = new SignaturePad(window.saturne.signature.canvas, {
        penColor: 'rgb(0, 0, 0)'
    });

    window.saturne.signature.canvas.width = window.saturne.signature.canvas.offsetWidth * ratio;
    window.saturne.signature.canvas.height = window.saturne.signature.canvas.offsetHeight * ratio;
    window.saturne.signature.canvas.getContext('2d').scale(ratio, ratio);
    window.saturne.signature.canvas.signaturePad.clear();

    let signatureData = $('#signature_data' + triggeredElement.attr('value')).val();
    window.saturne.signature.canvas.signaturePad.fromDataURL(signatureData);
};

/**
 * Action Clear sign
 *
 * @memberof Saturne_Framework_Signature
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.signature.clearCanvas = function() {
    let canvas = $(this).closest('.modal-signature').find('canvas');
    canvas[0].signaturePad.clear();
};

/**
 * Action create signature
 *
 * @memberof Saturne_Framework_Signature
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.signature.createSignature = function() {
    let elementSignatory       = $(this).attr('value');
    let elementRedirect        = '';
    let elementCode            = '';
    let elementZone            = $(this).find('#zone' + elementSignatory).attr('value');
    let elementConfCAPTCHA     = $('#confCAPTCHA').val();
    let actionContainerSuccess = $('.noticeSignatureSuccess');
    let signatoryIDPost        = '';
    if (elementSignatory !== 0) {
        signatoryIDPost = '&signatoryID=' + elementSignatory;
    }

    if (!$(this).closest('.wpeo-modal').find('canvas')[0].signaturePad.isEmpty()) {
        var signature = $(this).closest('.wpeo-modal').find('canvas')[0].toDataURL();
    }

    let token          = window.saturne.toolbox.getToken();
    let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);

    let url   = document.URL + querySeparator + 'action=add_signature' + signatoryIDPost + '&token=' + token;
    $.ajax({
        url: url,
        type: 'POST',
        processData: false,
        contentType: 'application/octet-stream',
        data: JSON.stringify({
            signature: signature,
            code: elementCode
        }),
        success: function( resp ) {
            if (elementZone == "private") {
                actionContainerSuccess.html($(resp).find('.noticeSignatureSuccess .notice-content'));
                actionContainerSuccess.removeClass('hidden');
                $('.signatures-container').html($(resp).find('.signatures-container'));
            } else {
                window.location.reload();
            }
        },
        error: function ( ) {
        }
    });
};

/**
 * Download signature
 *
 * @memberof Saturne_Framework_Signature
 *
 * @since   1.0.0
 * @version 1.0.0
 *
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
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.signature.autoDownloadSpecimen = function() {
    let element        = $(this).closest('.file-generation');
    let token          = window.saturne.toolbox.getToken();
    let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);
    let url            = document.URL + querySeparator + 'action=builddoc&token=' + token;
    $.ajax({
        url: url,
        type: 'POST',
        success: function ( ) {
            let filename = element.find('.specimen-name').attr('value');
            let path     = element.find('.specimen-path').attr('value');
            window.saturne.signature.download(path + filename, filename);
			$('.button-blue.button-disable.wpeo-loader').removeClass('wpeo-loader').removeClass('button-disable').removeClass('button-blue')
			$('.loader-spin').remove()
            $.ajax({
                url: document.URL + querySeparator + 'action=remove_file&token=' + token,
                type: 'POST',
                success: function ( ) {
                },
                error: function ( ) {
                }
            });
        },
        error: function ( ) {
        }
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
 * set Attendance signatory
 *
 * @memberof Saturne_Framework_Signature
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.signature.setAttendance = function() {
    let signatoryID       = $(this).closest('.attendance-container').find('input[name="signatoryID"]').val();
    let attendance        = $(this).attr('value');
    let token             = window.saturne.toolbox.getToken();
    let querySeparator    = window.saturne.toolbox.getQuerySeparator(document.URL);
    let urlWithoutHashtag = String(document.location.href).replace(/#formmail/, "");
    let url = urlWithoutHashtag + querySeparator + 'action=set_attendance&token=' + token;
    $.ajax({
        url: url,
        type: 'POST',
        processData: false,
        contentType: '',
        data: JSON.stringify({
            signatoryID: signatoryID,
            attendance: attendance
        }),
        success: function (resp) {
            $('.signatures-container').html($(resp).find('.signatures-container'));
        },
        error: function () {
        }
    });
};
