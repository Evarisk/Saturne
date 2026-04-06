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
 * Init media recoder
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
 * Audio event
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
  $(document).on('click', '.saturne-delete-media-icon', window.saturne.audio.deleteMedia);
  $(document).on('click', '#play-recording', window.saturne.audio.playRecording);
  $(document).on('click', '.saturne-open-audio-library', window.saturne.audio.openLibrary);
  $(document).on('click', '#delete-recording', window.saturne.audio.deleteLocalRecording);
};

/**
 * Audio event
 *
 * @memberof Saturne_Audio
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @returns {void}
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
 * Start recording audio
 *
 * @memberof Saturne_Audio
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @returns {void}
 */
window.saturne.audio.startRecording = async function(e) {
  const stream                = await window.saturne.audio.getMediaStream();
  window.saturne.mediaRecoder = new MediaRecorder(stream);
  let audioChunks             = [];
  
  // Extract configuration from nearest DOM block
  let $btn = (e && e.currentTarget) ? $(e.currentTarget) : $('#start-recording');
  let $optionsData = $btn.closest('.linked-medias').find('.fast-upload-options').data();
  window.saturne.audio.currentUploadOptions = $optionsData;

  window.saturne.mediaRecoder.ondataavailable = function(event) {
    audioChunks.push(event.data);
  };

  window.saturne.mediaRecoder.start();
  let $container = $btn.closest('.linked-medias');
  
  $btn.find('i').removeClass('fa-microphone').addClass('fa-stop');
  $btn.addClass('recording-pulse-active');
  $("#play-recording").prop("disabled", true).css({"background-color": "#cbd5e1", "cursor": "not-allowed"});
  $("#delete-recording").css("display", "none");
  $btn.attr('id', 'stop-recording');
  $('.page-footer button').prop('disabled', true);

  window.saturne.mediaRecoder.onstop = function() {
    const audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
    const formData  = new FormData();
    formData.append('audio', audioBlob, 'recording.wav');
    formData.append('action', 'add_audio');
    
    // Store dynamically created Blob URL so the `#play-recording` handler can find it
    window.saturne.audio.localAudioUrl = URL.createObjectURL(audioBlob);
    
    // Activate UI buttons to play or scrap local track
    $("#play-recording").prop("disabled", false).css({"background-color": "#7b68ee", "cursor": "pointer"});
    $("#delete-recording").css("display", "flex");
    
    let opt = window.saturne.audio.currentUploadOptions || {};
    formData.append('module', opt.fromType || 'saturne');
    formData.append('subdir', opt.fromSubdir || '');
    formData.append('prefix', opt.prefix || '');
    formData.append('rights', opt.rights || '');

    let token = window.saturne.toolbox.getToken();
    let apiBaseUrl = (typeof dolibarr_main_url_root !== 'undefined') ? dolibarr_main_url_root : '';

    $.ajax({
      url: apiBaseUrl + '/custom/saturne/core/ajax/medias.php?action=add_audio&token=' + token,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(resp) {
          // Success: DOM update is managed dynamically by gallery feature, no need to overwrite entire block
          $('.page-footer button').prop('disabled', false);
          $container.find('#recording-indicator').text('Enregistrement sauvegardé').css('color', '#2ecc71').show();
          setTimeout(() => { $container.find('#recording-indicator').fadeOut(); }, 2500);
      },
      error: function() {
          $('.page-footer button').prop('disabled', false);
          $container.find('#recording-indicator').text('Erreur sauvegarde').css('color', '#e74c3c').show();
      }
    });
  };
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
window.saturne.audio.stopRecording = async function(e) {
  if (window.saturne.mediaRecoder && window.saturne.mediaRecoder.state !== 'inactive') {
    window.saturne.mediaRecoder.stop();
    // Stop streams
    window.saturne.mediaRecoder.stream.getTracks().forEach(track => track.stop());
    
    let $btn = (e && e.currentTarget) ? $(e.currentTarget) : $('#stop-recording');
    $btn.find('i').removeClass('fa-stop').addClass('fa-microphone');
    $btn.removeClass('recording-pulse-active');
    $btn.attr('id', 'start-recording');
  }
};

/**
 * Delete a media file
 *
 * @memberof Saturne_Audio
 * @returns {void}
 */
window.saturne.audio.deleteMedia = function(e) {
  let $btn = $(e.currentTarget);
  let filename = $btn.data('filename');
  if (!filename) return;

  if (confirm("Êtes-vous sûr de vouloir supprimer définitivement cet audio ?")) {
    let $item = $btn.closest('.saturne-audio-item');
    let $container = $btn.closest('.saturne-audio-library-container'); // Because it's now wrapped inside a jQuery modal, closest linked-medias might be different or we just look for our target wrappers
    let $masterContainer = $('#master-media-row-container-audio'); // Simplified targeting for global widget
    
    let opt = {};
    if ($masterContainer.length) {
        let $optionsData = $masterContainer.find('.fast-upload-options').data();
        opt = $optionsData || {};
    }

    let token = window.saturne.toolbox.getToken();
    let apiBaseUrl = (typeof dolibarr_main_url_root !== 'undefined') ? dolibarr_main_url_root : '';

    let formData = new FormData();
    formData.append('filename', filename);
    formData.append('module', opt.fromType || 'saturne');
    formData.append('subdir', opt.fromSubdir || '');

    $.ajax({
      url: apiBaseUrl + '/custom/saturne/core/ajax/medias.php?subaction=delete_media&token=' + token,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(resp) {
        $item.fadeOut(300, function() { 
            $(this).remove(); 
            // Decrease badge dynamically
            let $badge = $('.saturne-audio-badge');
            let currentNb = parseInt($badge.text()) || 0;
            currentNb--;
            if (currentNb <= 0) {
                $('.saturne-audio-badge').fadeOut(function() { $(this).remove(); });
                $('#play-recording').prop('disabled', true).css({"background-color": "#cbd5e1", "cursor": "not-allowed"}).removeAttr("data-url");
                if ($container.hasClass('ui-dialog-content')) {
                    $container.dialog("close");
                }
            } else {
                $badge.text(currentNb);
            }
        });
      },
      error: function(xhr) {
        let errCode = xhr.responseText || 'Erreur inconnue';
        if (window.saturne.notice) {
           window.saturne.notice.showNotice('notice-infos', 'Erreur Suppression', errCode, 'error');
        } else {
           alert('Erreur lors de la suppression : ' + errCode);
        }
      }
    });
  }
};

/**
 * Open the audio library modal
 */
window.saturne.audio.openLibrary = function(e) {
  e.stopPropagation();
  let $badge = $(e.currentTarget);
  let $content = $badge.data('libraryContent');
  
  if (!$content) {
      $content = $badge.closest('.linked-medias').find('.saturne-audio-library-container'); // General fallback
      if ($content.length) {
          $badge.data('libraryContent', $content); // Cache the reference for future clicks because dialog() moves it to body !
      }
  }

  if ($content && $content.length) {
      if (!$content.hasClass('ui-dialog-content')) {
          $content.dialog({
              title: "Bibliothèque des pistes vocales",
              width: 500,
              modal: true,
              resizable: false,
              create: function(event, ui) {
                  $(this).css("display", "block");
              }
          });
      } else {
          $content.dialog("open");
      }
  }
};

/**
 * Handle generic Play button logic
 */
window.saturne.audio.playRecording = function(e) {
  e.stopPropagation();
  const btn = $(e.currentTarget);
  
  // If player is running, stop it
  if (window.saturne.audio.player && !window.saturne.audio.player.paused) {
      window.saturne.audio.player.pause();
      window.saturne.audio.player.currentTime = 0;
      btn.removeClass("playing-pulse-active");
      btn.find("i").removeClass("fa-stop").addClass("fa-play");
      return;
  }
  
  // If player exists, stop current memory
  if (window.saturne.audio.player) {
      window.saturne.audio.player.pause();
      window.saturne.audio.player.currentTime = 0;
  }
  
  // Check what to play: Local RAM recording, or Latest Saved Server URL
  let targetAudioUrl = window.saturne.audio.localAudioUrl;
  if (!targetAudioUrl) {
      targetAudioUrl = btn.attr('data-url');
  }
  
  if (targetAudioUrl) {
      window.saturne.audio.player = new Audio(targetAudioUrl);
      
      btn.addClass("playing-pulse-active");
      btn.find("i").removeClass("fa-play").addClass("fa-stop");
      
      window.saturne.audio.player.onended = function() {
          btn.removeClass("playing-pulse-active");
          btn.find("i").removeClass("fa-stop").addClass("fa-play");
      };
      
      window.saturne.audio.player.play();
  }
};

/**
 * Delete a LOCAL RAM recording
 */
window.saturne.audio.deleteLocalRecording = function(e) {
  if (window.saturne.audio && window.saturne.audio.player) {
      window.saturne.audio.player.pause();
  }
  window.saturne.audio.localAudioUrl = null;
  window.saturne.audio.player = null;
  
  let $playBtn = $("#play-recording");
  $playBtn.removeClass("playing-pulse-active");
  $playBtn.find("i").removeClass("fa-stop").addClass("fa-play");
  
  $(e.currentTarget).css("display", "none");
  
  // Revert play button to grey if no remote data-url
  if (!$playBtn.attr('data-url')) {
      $playBtn.prop("disabled", true).css({"background-color": "#cbd5e1", "cursor": "not-allowed"});
  }
  
  // Reactivate the microphone button
  $("#start-recording").prop("disabled", false).css({"background-color": "#8e44ad", "cursor": "pointer"});
  
  $("#recording-indicator").text("Enregistrement local supprimé.").css("color", "#64748b").show();
  setTimeout(() => $("#recording-indicator").hide(), 2000);
};
