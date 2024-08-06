/* Copyright (C) 2024 EVARISK <technique@evarisk.com>
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
 * \file    js/modules/qrcode.js
 * \ingroup saturne
 * \brief   JavaScript qrcode file for module Saturne
 */

/**
 * Init qrcode JS
 *
 * @since   1.2.0
 * @version 1.2.0
 */
window.saturne.qrcode = {};

/**
 * QR Code init
 *
 * @since   1.2.0
 * @version 1.2.0
 *
 * @return {void}
 */
window.saturne.qrcode.init = function() {
  window.saturne.qrcode.event();
};

/**
 * QR Code event
 *
 * @since   1.2.0
 * @version 1.2.0
 *
 * @return {void}
 */
window.saturne.qrcode.event = function() {
  $(document).on('click', '.preview-qr-code', window.saturne.qrcode.previewQRCode);
};


// Fonction pour afficher le QR code dans une modal
window.saturne.qrcode.previewQRCode = function() {
  // Obtenir l'image du QR code à partir des données de l'élément
  let QRCodeBase64 = $(this).find('.qrcode-base64').val();

  // Créer un élément d'image
  const img = document.createElement('img');
  img.src = QRCodeBase64;
  img.alt = 'QR Code';
  img.style.maxWidth = '100%';

  // Insérer l'image dans le conteneur désigné
  const pdfPreview = document.getElementById('pdfPreview');
  pdfPreview.innerHTML = ''; // Vider le conteneur d'abord
  pdfPreview.appendChild(img);

  // Afficher la modal
  $('#pdfModal').addClass('modal-active');

  // Ajouter un bouton de téléchargement
  const downloadBtn = document.getElementById('downloadBtn');
  downloadBtn.onclick = function() {
    const a = document.createElement('a');
    a.href = QRCodeBase64;
    a.download = 'QRCode.png';
    a.click();
  };
};
