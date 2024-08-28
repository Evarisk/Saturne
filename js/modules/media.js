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
 * \file    js/modules/media.js
 * \ingroup saturne
 * \brief   JavaScript media file for module Saturne
 */

/**
 * Init media JS
 *
 * @memberof Saturne_Media
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @type {Object}
 */
window.saturne.media = {};

/**
 * Init rotation value of img on canvas
 *
 * @memberof Saturne_Media
 *
 * @since   1.3.0
 * @version 1.3.0
 */
window.saturne.media.rotation = 0;

/**
 * Init img in canvas
 *
 * @memberof Saturne_Media
 *
 * @since   1.3.0
 * @version 1.3.0
 */
window.saturne.media.img       = null;
window.saturne.media.imageData = null;

/**
 * Init signaturePad in canvas
 *
 * @memberof Saturne_Media
 *
 * @since   1.3.0
 * @version 1.3.0
 */
window.saturne.media.signaturePad = null;

/**
 * Variables to handle pan
 *
 * @memberof Saturne_Media
 *
 * @since   1.6.0
 * @version 1.6.0
 */
window.saturne.media.translateX     = 0;
window.saturne.media.translateY     = 0;
window.saturne.media.lastTranslateX = 0;
window.saturne.media.lastTranslateY = 0;

/**
 * Check moving status
 *
 * @memberof Saturne_Media
 *
 * @since   1.6.0
 * @version 1.6.0
 */
window.saturne.media.isMoving = true;

/**
 * Media init
 *
 * @memberof Saturne_Media
 *
 * @since   1.3.0
 * @version 1.6.0
 *
 * @returns {void}
 */
window.saturne.media.init = function() {
  window.saturne.media.event();
  const scriptElement = document.querySelector('script[src*="hammer.min.js"]');
  if (scriptElement) {
    window.saturne.media.initPan();
  }
};

/**
 * Media event
 *
 * @memberof Saturne_Media
 *
 * @since   1.3.0
 * @version 1.6.0
 *
 * @returns {void}
 */
window.saturne.media.event = function() {
  $(document).on('change', '.fast-upload-improvement', window.saturne.media.uploadImage);
  $(document).on('click', '.image-move', window.saturne.media.startMoving);
  $(document).on('click', '.image-rotate-left', function() {
    window.saturne.media.rotateImage(-90);
  });
  $(document).on('click', '.image-rotate-right', function() {
    window.saturne.media.rotateImage(90);
  });
  $(document).on('click', '.image-undo', window.saturne.media.undoLastDraw);
  $(document).on('click', '.image-drawing', window.saturne.media.startDrawing);
  $(document).on('click', '.image-erase', window.saturne.media.clearCanvas);
  $(document).on('click', '.image-validate', window.saturne.media.createImg);
};

/**
 * Init pan with Hammer.js
 *
 * @memberof Saturne_Media
 *
 * @since   1.6.0
 * @version 1.6.0
 *
 * @returns {void}
 */
window.saturne.media.initPan = function() {
  const modalContent = document.querySelector('.modal-content');
  const hammer = new Hammer(modalContent, {
    touchAction: 'auto', // Configure touch action behavior
    recognizers: [
      [Hammer.Pan, { direction: Hammer.DIRECTION_ALL }] // Enable panning in all directions
    ],
    threshold: 5, // Adjust this threshold for more sensitive pan detection
    velocity: 0.1 // Adjust this velocity for smoother pan movement
  });

  hammer.on('pan', (e) => {
    if (window.saturne.media.isMoving) {
      const deltaX = e.deltaX;
      const deltaY = e.deltaY;

      // Adjust the scroll position of modal content
      modalContent.scrollLeft -= deltaX;
      modalContent.scrollTop  -= deltaY;
    }
  });
};

/**
 * Upload image action
 *
 * @memberof Saturne_Media
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @returns {void}
 */
window.saturne.media.uploadImage = function() {
  const fastUploadOptions = $(this).closest('.linked-medias').find('.fast-upload-options');
  const objectSubType     = fastUploadOptions.attr('data-from-subtype');
  const objectSubdir      = fastUploadOptions.attr('data-from-subdir');
  if (this.files && this.files[0]) {
    var reader = new FileReader();

    reader.onload = function(event) {
      $(document).find('.modal-upload-image').addClass('modal-active');
      $('.modal-upload-image').find('.fast-upload-options').attr('data-from-subtype', objectSubType);
      $('.modal-upload-image').find('.fast-upload-options').attr('data-from-subdir', objectSubdir);
      window.saturne.media.drawImageOnCanvas(event);
    };

    reader.readAsDataURL(this.files[0]);
  }
};

/**
 * Rotate image action
 *
 * @memberof Saturne_Media
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @returns {void}
 */
window.saturne.media.rotateImage = function(degrees) {
  window.saturne.media.rotation += degrees;
  $('#canvas').css('transform', 'rotate(' + window.saturne.media.rotation + 'deg)');
};

/**
 * Undo last drawing action
 *
 * @memberof Saturne_Media
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.saturne.media.undoLastDraw = function() {
  let canvas = $(this).closest('.modal-upload-image').find('canvas');
  var data   = canvas[0].signaturePad.toData();
  if (data) {
    data.pop(); // remove the last dot or line
    canvas[0].signaturePad.fromData(data);
    // Redraw the image on the canvas
    window.saturne.media.drawImageOnCanvas(window.saturne.media.img);
  }
};

/**
 * Start moving action
 *
 * @memberof Saturne_Media
 *
 * @since   1.6.0
 * @version 1.6.0
 *
 * @return {void}
 */
window.saturne.media.startMoving = function() {
  window.saturne.media.isMoving = true;

  if (window.saturne.media.signaturePad) {
    window.saturne.media.imageData = window.saturne.media.canvas.toDataURL();
    window.saturne.media.signaturePad.off();
  }

  window.saturne.toolbox.toggleButtonClass('image-drawing', 'button-grey button-blue');
  window.saturne.toolbox.toggleButtonClass('image-move', 'button-blue button-grey');
};

/**
 * Start drawing action
 *
 * @memberof Saturne_Media
 *
 * @since   1.6.0
 * @version 1.6.0
 *
 * @return {void}
 */
window.saturne.media.startDrawing = function() {
  window.saturne.media.isMoving = false; // Disable pan mode

  const canvas = document.querySelector('#canvas');
  if (canvas) {
    if (!window.saturne.media.signaturePad) {
      // Initialise SignaturePad if you have not already done so
      window.saturne.media.signaturePad = new SignaturePad(canvas, {
        penColor: 'rgb(255, 0, 0)'
      });
    } else {
      // Activate the drawing without deleting the background image
      window.saturne.media.signaturePad.on();
    }

    window.saturne.media.drawImageOnCanvas(window.saturne.media.img);

    window.saturne.toolbox.toggleButtonClass('image-drawing', 'button-blue button-grey');
    window.saturne.toolbox.toggleButtonClass('image-move', 'button-grey button-blue');
  }
};

/**
 * Clear canvas action
 *
 * @memberof Saturne_Media
 *
 * @since   1.3.0
 * @version 1.6.0
 *
 * @return {void}
 */
window.saturne.media.clearCanvas = function() {
  const canvas = document.querySelector('#canvas');
  if (canvas && window.saturne.media.signaturePad) {
    window.saturne.media.signaturePad.clear();
    window.saturne.media.drawImageOnCanvas(window.saturne.media.img);
    window.saturne.media.imageData = null;
  }
};

/**
 * Draw img on canvas action
 *
 * @memberof Saturne_Media
 *
 * @since   1.3.0
 * @version 1.6.0
 *
 * @return {void}
 */
window.saturne.media.drawImageOnCanvas = function(event) {
  window.saturne.media.canvas = document.querySelector('#modal-upload-image canvas');
  if (window.saturne.media.canvas) {
    const context = window.saturne.media.canvas.getContext('2d');

    // Draw the image on the canvas
    const img                = new Image();
    img.src                  = event.target.result;
    window.saturne.media.img = event;
    img.onload = function() {
      const maxWidth  = $('.fast-upload-options').data('image-resolution-width');
      const maxHeight = $('.fast-upload-options').data('image-resolution-height');
      let canvasWidth, canvasHeight;

      // Calculate the aspect ratio of the image
      const aspectRatio = img.width / img.height;

      if (img.width > maxWidth || img.height > maxHeight) {
        if (aspectRatio > 1) {
          // Landscape orientation
          canvasWidth  = maxWidth;
          canvasHeight = maxWidth / aspectRatio;
        } else {
          // Portrait orientation
          canvasHeight = maxHeight;
          canvasWidth  = maxHeight * aspectRatio;
        }

        // Ensure the canvas dimensions do not exceed the maximum dimensions
        if (canvasWidth > maxWidth) {
          canvasWidth  = maxWidth;
          canvasHeight = maxWidth / aspectRatio;
        }
        if (canvasHeight > maxHeight) {
          canvasHeight = maxHeight;
          canvasWidth  = maxHeight * aspectRatio;
        }
      } else {
        canvasWidth  = img.width;
        canvasHeight = img.height;
      }

      window.saturne.media.canvas.width  = canvasWidth;
      window.saturne.media.canvas.height = canvasHeight;

      context.drawImage(img, 0, 0, window.saturne.media.canvas.width, window.saturne.media.canvas.height);

      // Restore drawing state
      if (window.saturne.media.imageData) {
        const drawingImg  = new Image();
        drawingImg.src    = window.saturne.media.imageData;
        drawingImg.onload = function() {
          context.drawImage(drawingImg, 0, 0);
        };
      }
    };

    window.saturne.media.rotation = 0; // Reset rotation when a new image is selected
  }
};

/**
 * create img action
 *
 * @memberof Saturne_Media
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.saturne.media.createImg = function() {
  let canvas = $(this).closest('.modal-upload-image').find('canvas')[0];
  let img    = canvas.toDataURL('image/png');

  let objectSubType = $(this).closest('.modal-upload-image').find('.fast-upload-options').attr('data-from-subtype');
  let objectSubdir  = $(this).closest('.modal-upload-image').find('.fast-upload-options').attr('data-from-subdir');

  let token          = window.saturne.toolbox.getToken();
  let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);

  window.saturne.loader.display($(this));
  $.ajax({
    url: document.URL + querySeparator + 'subaction=add_img&token=' + token,
    type: 'POST',
    processData: false,
    contentType: 'application/octet-stream',
    data: JSON.stringify({
      img: img,
      objectSubType: objectSubType,
      objectSubdir: objectSubdir
    }),
    success: function(resp) {
      $('.wpeo-loader').removeClass('wpeo-loader');
      $('.wpeo-modal').removeClass('modal-active');
      if ($('.floatleft.inline-block.valignmiddle.divphotoref').length > 0) {
        $('.floatleft.inline-block.valignmiddle.divphotoref').replaceWith($(resp).find('.floatleft.inline-block.valignmiddle.divphotoref'));
      }
      $('.linked-medias.' + objectSubType).html($(resp).find('.linked-medias.' + objectSubType).children());
    },
    error: function () {}
  });
};
