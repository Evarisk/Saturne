/* Copyright (C) 2024-2026 EVARISK <technique@evarisk.com>
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
 * \file    js/modules/audio.js
 * \ingroup saturne
 * \brief   JavaScript handler for audio recording in saturne_render_media_block()
 */

/**
 * Audio namespace
 *
 * @memberof Saturne_Audio
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @type {Object}
 */
window.saturne.audio = {};

/**
 * MediaRecorder instance
 *
 * @memberof Saturne_Audio
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @type {Object}
 */
window.saturne.mediaRecoder = {};

/**
 * Accumulated audio chunks during recording
 *
 * @memberof Saturne_Audio
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @type {Array}
 */
window.saturne.audio.audioChunks = [];

/**
 * Context of the currently active recording block (set on start, cleared on upload complete)
 *
 * @memberof Saturne_Audio
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @type {Object|null}
 */
window.saturne.audio._context = null;

/**
 * Audio init
 *
 * @memberof Saturne_Audio
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @returns {void}
 */
window.saturne.audio.init = function() {
  window.saturne.audio.event();
};

/**
 * Audio event bindings
 *
 * @memberof Saturne_Audio
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @returns {void}
 */
window.saturne.audio.event = function() {
  $(document).on('click', '.saturne-start-recording',    window.saturne.audio.startRecording);
  $(document).on('click', '.saturne-stop-recording',     window.saturne.audio.stopRecording);
  $(document).on('click', '.saturne-play-recording',     window.saturne.audio.playLatest);
  $(document).on('click', '.saturne-open-audio-library', window.saturne.audio.openLibrary);
  $(document).on('click', '.saturne-delete-media-icon',  window.saturne.audio.deleteAudio);
};

/**
 * Request microphone access and return the media stream
 *
 * @memberof Saturne_Audio
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @returns {Promise<MediaStream>}
 */
window.saturne.audio.getMediaStream = async function() {
  try {
    return await navigator.mediaDevices.getUserMedia({audio: true});
  } catch (err) {
    var name    = err.name;
    var message = err.message;
    window.saturne.notice.showNotice('notice-infos', 'Error', name + ': ' + message, 'error');
    throw err;
  }
};

/**
 * Accumulate audio data chunks during recording
 *
 * @memberof Saturne_Audio
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @param   {BlobEvent} event MediaRecorder dataavailable event
 * @returns {void}
 */
window.saturne.audio.onDataAvailable = function(event) {
  window.saturne.audio.audioChunks.push(event.data);
};

/**
 * Update the recording indicator with upload progress
 *
 * @memberof Saturne_Audio
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @param   {ProgressEvent} event XHR upload progress event
 * @returns {void}
 */
window.saturne.audio.onUploadProgress = function(event) {
  var ctx         = window.saturne.audio._context;
  var block       = ctx ? ctx.block : null;
  var indicator   = block ? block.find('.saturne-recording-indicator') : $();
  var uploadLabel = indicator.data('label-upload') || 'Upload';
  var percent     = Math.round((event.loaded / event.total) * 100);

  indicator.text(uploadLabel + ' : ' + percent + ' %');
};

/**
 * Re-enable page footer buttons and refresh the audio block after upload
 *
 * @memberof Saturne_Audio
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @param   {jqXHR} resp jQuery XHR response
 * @returns {void}
 */
window.saturne.audio.onUploadComplete = function(resp) {
  $('.page-footer button').prop('disabled', false);

  var ctx     = window.saturne.audio._context;
  var block   = ctx ? ctx.block : null;
  var blockId = block ? block.attr('id') : null;

  window.saturne.audio._context = null;

  if (blockId) {
    var doc     = new DOMParser().parseFromString(resp.responseText, 'text/html');
    var el      = doc.getElementById(blockId);
    var updated = el ? $(el) : $();
    if (updated.length && block && block.length) {
      block.replaceWith(updated);
      return;
    }
  }

  if (block) {
    block.find('.saturne-recording-indicator').hide();
  }
};

/**
 * Build and return an XHR object wired to the upload progress handler
 *
 * @memberof Saturne_Audio
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @returns {XMLHttpRequest}
 */
window.saturne.audio.buildXhr = function() {
  var xhr               = new XMLHttpRequest();
  xhr.upload.onprogress = window.saturne.audio.onUploadProgress;
  return xhr;
};

/**
 * Upload the recorded audio blob to the server
 *
 * @memberof Saturne_Audio
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @returns {void}
 */
window.saturne.audio.onRecordingStop = function() {
  var ctx       = window.saturne.audio._context;
  var block     = ctx ? ctx.block : null;
  var indicator = block ? block.find('.saturne-recording-indicator') : $();
  var module    = ctx ? ctx.module : '';
  var subDir    = ctx ? ctx.subDir : '';

  var uploadLabel = indicator.data('label-upload') || 'Upload';
  indicator.text(uploadLabel + ' ...').show();

  var audioBlob = new Blob(window.saturne.audio.audioChunks, {type: 'audio/wav'});
  var formData  = new FormData();
  formData.append('audio', audioBlob, 'recording.wav');
  formData.append('module_name', module);
  formData.append('sub_dir', subDir);

  var token          = window.saturne.toolbox.getToken();
  var querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);

  $.ajax({
    url         : document.URL + querySeparator + 'action=add_audio&token=' + token,
    type        : 'POST',
    data        : formData,
    processData : false,
    contentType : false,
    xhr         : window.saturne.audio.buildXhr,
    complete    : window.saturne.audio.onUploadComplete,
  });
};

/**
 * Start recording audio — switches the button to "stop" state with visual feedback
 *
 * @memberof Saturne_Audio
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @returns {void}
 */
window.saturne.audio.startRecording = async function() {
  var btn   = $(this);
  var block = btn.closest('.linked-medias');

  window.saturne.audio._context = {
    block  : block,
    module : block.find('.fast-upload-options').data('from-type') || '',
    subDir : block.find('.fast-upload-options').data('from-subdir') || '',
    stream : null,
  };

  var stream = await window.saturne.audio.getMediaStream();

  window.saturne.audio._context.stream = stream;
  window.saturne.audio.audioChunks     = [];
  window.saturne.mediaRecoder          = new MediaRecorder(stream);

  window.saturne.mediaRecoder.ondataavailable = window.saturne.audio.onDataAvailable;
  window.saturne.mediaRecoder.onstop          = window.saturne.audio.onRecordingStop;

  window.saturne.mediaRecoder.start();

  btn.find('i').removeClass('fa-microphone').addClass('fa-stop');
  btn.css('background-color', '#e74c3c');
  btn.removeClass('saturne-start-recording').addClass('saturne-stop-recording');

  var recordingLabel = block.find('.saturne-recording-indicator').data('label-recording');
  block.find('.saturne-recording-indicator').text(recordingLabel).show();

  $('.page-footer button').prop('disabled', true);
};

/**
 * Stop recording audio — restores the button and triggers the upload
 *
 * @memberof Saturne_Audio
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @returns {void}
 */
window.saturne.audio.stopRecording = function() {
  if (!window.saturne.mediaRecoder || window.saturne.mediaRecoder.state === 'inactive') {
    return;
  }

  var btn   = $(this);
  var block = btn.closest('.linked-medias');

  btn.find('i').removeClass('fa-stop').addClass('fa-microphone');
  btn.css('background-color', '#8e44ad');
  btn.removeClass('saturne-stop-recording').addClass('saturne-start-recording');
  block.find('.saturne-recording-indicator').hide();

  var stream = window.saturne.audio._context ? window.saturne.audio._context.stream : null;
  if (stream) {
    stream.getTracks().forEach(function(track) { track.stop(); });
  }

  window.saturne.mediaRecoder.stop();
};

/**
 * Toggle play/pause on the latest recorded audio
 *
 * @memberof Saturne_Audio
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @returns {void}
 */
window.saturne.audio.playLatest = function() {
  var btn   = $(this);
  var block = btn.closest('.linked-medias');
  var url   = btn.data('url');

  if (!url) {
    return;
  }

  var audio = block.data('audio-player');

  if (!audio) {
    audio = new Audio(url);
    block.data('audio-player', audio);

    $(audio).on('ended', function() {
      btn.find('i').removeClass('fa-pause').addClass('fa-play');
    });
  }

  if (audio.paused) {
    audio.play();
    btn.find('i').removeClass('fa-play').addClass('fa-pause');
  } else {
    audio.pause();
    audio.currentTime = 0;
    btn.find('i').removeClass('fa-pause').addClass('fa-play');
  }
};

/**
 * Open the audio library modal (list of all recordings)
 *
 * @memberof Saturne_Audio
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @returns {void}
 */
window.saturne.audio.openLibrary = function() {
  var block   = $(this).closest('.linked-medias');
  var blockId = block.attr('id');
  var modalId = blockId.replace('master-media-row-container-audio', 'audio-library-modal');

  $('#' + modalId).addClass('modal-active');
};

/**
 * Delete an audio file via AJAX and refresh both the audio block and the library modal
 *
 * @memberof Saturne_Audio
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @returns {void}
 */
window.saturne.audio.deleteAudio = function() {
  var btn      = $(this);
  var modal    = btn.closest('.saturne-audio-library-modal');
  var filename = btn.data('filename');
  var module   = modal.data('module') || '';
  var subDir   = modal.data('subdir') || '';
  var blockId  = modal.data('block-id') || '';
  var modalId  = modal.attr('id');

  var formData = new FormData();
  formData.append('filename', filename);
  formData.append('module_name', module);
  formData.append('sub_dir', subDir);

  var token          = window.saturne.toolbox.getToken();
  var querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);

  $.ajax({
    url         : document.URL + querySeparator + 'action=delete_audio&token=' + token,
    type        : 'POST',
    data        : formData,
    processData : false,
    contentType : false,
    complete    : function(resp) {
      var doc = new DOMParser().parseFromString(resp.responseText, 'text/html');

      // Refresh the audio block (play button, badge count)
      if (blockId) {
        var updatedBlock = doc.getElementById(blockId);
        if (updatedBlock && $('#' + blockId).length) {
          $('#' + blockId).replaceWith($(updatedBlock));
        }
      }

      // Refresh the modal content (keep it open, update the list)
      if (modalId) {
        var updatedModal = doc.getElementById(modalId);
        if (updatedModal && modal.length) {
          var wasActive = modal.hasClass('modal-active');
          modal.replaceWith($(updatedModal));
          if (wasActive) {
            $('#' + modalId).addClass('modal-active');
          }
        }
      }
    },
  });
};
