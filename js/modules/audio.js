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
 * \file    js/modules/audio.js
 * \ingroup saturne
 * \brief   JavaScript audio file for module Saturne
 */

/**
 * Init audio JS
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
  $(document).on('click', '#start-recording', window.saturne.audio.startRecording);
  $(document).on('click', '#stop-recording', window.saturne.audio.stopRecording);
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
    let {name, message} = err;
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
  let percent     = Math.round((event.loaded / event.total) * 100);
  let uploadLabel = $('#recording-indicator').data('label-upload') || 'Upload';
  $('#recording-indicator').text(uploadLabel + ' : ' + percent + ' %');
};

/**
 * Re-enable page footer buttons and refresh the recording indicator after upload
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
  $('#recording-indicator').replaceWith($(resp.responseText).find('#recording-indicator'));
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
  let xhr               = new XMLHttpRequest();
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
  const audioBlob = new Blob(window.saturne.audio.audioChunks, {type: 'audio/wav'});
  const formData  = new FormData();
  formData.append('audio', audioBlob, 'recording.wav');

  let token          = window.saturne.toolbox.getToken();
  let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);

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
 * Start recording audio
 *
 * @memberof Saturne_Audio
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @returns {void}
 */
window.saturne.audio.startRecording = async function() {
  const stream = await window.saturne.audio.getMediaStream();

  window.saturne.audio.audioChunks = [];
  window.saturne.mediaRecoder      = new MediaRecorder(stream);

  window.saturne.mediaRecoder.ondataavailable = window.saturne.audio.onDataAvailable;
  window.saturne.mediaRecoder.onstop          = window.saturne.audio.onRecordingStop;

  window.saturne.mediaRecoder.start();

  $('#recording-indicator').show();
  $('#start-recording span').toggleClass('fa-circle fa-square');
  $('#start-recording').attr('id', 'stop-recording');
  $('.page-footer button').prop('disabled', true);
};

/**
 * Stop recording audio
 *
 * @memberof Saturne_Audio
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @returns {void}
 */
window.saturne.audio.stopRecording = async function() {
  if (window.saturne.mediaRecoder && window.saturne.mediaRecoder.state !== 'inactive') {
    window.saturne.mediaRecoder.stop();
    $('#stop-recording span').toggleClass('fa-square fa-circle');
    $('#stop-recording').attr('id', 'start-recording');
  }
};
