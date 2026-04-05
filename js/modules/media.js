/* global document, window, navigator, URL, indexedDB, FormData, FileReader, $, dolibarr, dolibarr_main_url_root, atob */
/* jshint maxcomplexity: 50, expr: true */
/* Copyright (C) 2024-2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

window.saturne.media = {};

window.saturne.media.img = null;
window.saturne.media.canvas = null;
window.saturne.media.ctx = null;
window.saturne.media.originalBlobUrl = null;

// Multi-files state
window.saturne.media.photoFilesArray = [];
window.saturne.media.currentIndex = 0;
window.saturne.media.uploadTargetOptions = {};

// Editor State
window.saturne.media.currentMode = 'pencil';
window.saturne.media.isDrawing = false;
window.saturne.media.snapshot = null;
window.saturne.media.sequenceCounter = 1;
window.saturne.media.historyStack = [];

// IndexedDB Wrapper
window.saturne.media.queueDb = {
    db: null,
    init: function() {
        const req = indexedDB.open('SaturneMediaQueue', 1);
        req.onupgradeneeded = function(e) {
            const db = e.target.result;
            if (!db.objectStoreNames.contains('pending_uploads')) {
                db.createObjectStore('pending_uploads', { keyPath: 'id' });
            }
        };
        req.onsuccess = function(e) {
            window.saturne.media.queueDb.db = e.target.result;
            window.saturne.media.syncPendingUploads();
        };
    },
    add: function(item, callback) {
        if (!this.db) {
            // IndexDB Unavailable Fallback -> Direct network execution!
            console.warn("Saturne Media: IndexedDB unavailable. Attempting direct sync payload.");
            // Mock the interface so it still uploads directly without crashing
            
            window.saturne.media.syncPendingUploadsSingleFallback(item, callback);
            return;
        }
        const tx = this.db.transaction(['pending_uploads'], 'readwrite');
        tx.objectStore('pending_uploads').put(item);
        tx.oncomplete = () => { if (callback) { callback(); } };
    },
    getAll: function(callback) {
        if (!this.db) { return; }
        const tx = this.db.transaction(['pending_uploads'], 'readonly');
        const req = tx.objectStore('pending_uploads').getAll();
        req.onsuccess = () => { if (callback) { callback(req.result); } };
    },
    remove: function(id, callback) {
        if (!this.db) { return; }
        const tx = this.db.transaction(['pending_uploads'], 'readwrite');
        tx.objectStore('pending_uploads').delete(id);
        tx.oncomplete = () => { if (callback) { callback(); } };
    }
};

window.saturne.media.init = function() {
    window.saturne.media.queueDb.init();
    window.saturne.media.event();
    window.saturne.media.updatePhotoResolutionDisplay();
    window.addEventListener('online', window.saturne.media.syncPendingUploads);
};

window.saturne.media.renderPendingQueue = function() {
    window.saturne.media.queueDb.getAll(items => {
        $('.saturne-offline-queue').remove();
        if (!items || items.length === 0) { return; }

        let queueHtml = '<div class="saturne-offline-queue" style="margin-top: 15px; padding: 10px; border: 1px solid #f39c12; border-radius: 8px; background: #fffdf5;">';
        queueHtml += '<strong style="color: #d35400;"><i class="fas fa-wifi" style="text-decoration: line-through;"></i> Médias en attente de synchronisation (' + items.length + ')</strong>';
        queueHtml += '<div style="display:flex; gap: 8px; margin-top: 10px; flex-wrap: wrap;">';
        
        items.forEach(item => {
            const borderCol = item.status === 'error' ? '#e74c3c' : '#f39c12';
            queueHtml += '<div style="width: 50px; height: 50px; border-radius: 4px; overflow: hidden; border: 2px solid '+borderCol+';" title="Status: '+item.status+'">';
            queueHtml += '<img src="'+item.imgData+'" style="width:100%; height:100%; object-fit:cover;">';
            queueHtml += '</div>';
        });

        queueHtml += '</div>';
        queueHtml += '<button type="button" class="doli-btn-retry-sync" style="margin-top: 10px; background: #3498db; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;"><i class="fas fa-sync"></i> Relancer l\'upload</button>';
        queueHtml += '</div>';

        // Append to the generic master container if present, or body
        if ($('#master-media-row-container').length > 0) {
            $('#master-media-row-container').after(queueHtml);
        } else if ($('.linked-medias').length > 0) {
            $('.linked-medias').eq(0).after(queueHtml);
        }
    });
};

window.saturne.media.event = function() {
    $(document).off('change', '.fast-upload-improvement').on('change', '.fast-upload-improvement', window.saturne.media.onFileInputChange);
    $(document).off('change', '#photo-size-select').on('change', '#photo-size-select', window.saturne.media.updatePhotoResolutionDisplay);
    $(document).off('click', '.btn-cancel-photo').on('click', '.btn-cancel-photo', window.saturne.media.cancelEditor);
    $(document).off('click', '.image-undo').on('click', '.image-undo', window.saturne.media.undoLastAction);
    $(document).off('click', '.image-validate').on('click', '.image-validate', window.saturne.media.validateAndQueue);
    
    // Retry Sync
    $(document).off('click', '.doli-btn-retry-sync').on('click', '.doli-btn-retry-sync', function() {
        const btn = $(this);
        btn.find('i').addClass('fa-spin');
        window.saturne.media.syncPendingUploads();
    });

    // Slider Arrows
    $(document).off('click', '#doli-editor-arrow-left').on('click', '#doli-editor-arrow-left', function() { window.saturne.media.switchImage(-1); });
    $(document).off('click', '#doli-editor-arrow-right').on('click', '#doli-editor-arrow-right', function() { window.saturne.media.switchImage(1); });

    // Thumbnail Clicks
    $(document).off('click', '.doli-thumb-preview').on('click', '.doli-thumb-preview', function() {
        const index = parseInt($(this).attr('data-index'));
        window.saturne.media.currentIndex = index;
        window.saturne.media.openEditor();
    });
    
    // Gallery Mode Interceptor
    $(document).off('click', '.open-media-editor-as-gallery').on('click', '.open-media-editor-as-gallery', function(e) {
        e.preventDefault();
        const urls = JSON.parse($(this).attr('data-json') || '[]');
        if (urls.length === 0) { return; }
        
        window.saturne.media.photoFilesArray = [];
        urls.forEach(url => {
            let actualFilename = url;
            if (url.indexOf('file=') !== -1) {
                 let fileParam = url.split('file=')[1];
                 if (fileParam.indexOf('&') !== -1) { fileParam = fileParam.split('&')[0]; }
                 actualFilename = decodeURIComponent(fileParam).split('/').pop();
            } else {
                 actualFilename = decodeURIComponent(url.split('/').pop());
            }
            window.saturne.media.photoFilesArray.push({
                blobUrl: url,
                file: { name: actualFilename },
                status: 'done' // Pre-existing on server
            });
        });
        window.saturne.media.currentIndex = 0;
        
        // Open the custom Editor modal actively
        $('.wpeo-modal.modal-upload-image').addClass('modal-active');
        window.saturne.media.openEditor();
    });

    // Tools Switching
    $(document).off('click', '.doli-tool-btn[data-mode]').on('click', '.doli-tool-btn[data-mode]', function() {
        const btn = $(this);
        const mode = btn.attr('data-mode');
        console.log("Saturne Media: Tool clicked", mode);
        
        if (mode === 'rotate') { window.saturne.media.rotateCanvas(); return; }

        $('.doli-tool-btn').each(function() {
            if ($(this).parent().attr('id') === 'pencil-tool-container') { $(this).parent().css('background-color', '#34495e'); }
            else if ($(this).attr('data-mode') !== 'rotate') { $(this).css('background-color', '#34495e'); }
        });
        
        if (btn.parent().attr('id') === 'pencil-tool-container') { btn.parent().css('background-color', '#3498db'); }
        else { btn.css('background-color', '#3498db'); }

        window.saturne.media.currentMode = mode;
        const canvasObj = window.saturne.media.canvas || $('.photo-editor-canvas')[0];
        if (canvasObj) { canvasObj.style.cursor = (mode === 'text') ? 'text' : 'crosshair'; }
        if (mode === 'sequence') { window.saturne.media.sequenceCounter = 1; }
    });

    $(document).off('mousedown touchstart', '.photo-editor-canvas').on('mousedown touchstart', '.photo-editor-canvas', function(e) {
        if (e.target.id === 'doli-floating-text-input') { return; }
        console.log("Saturne Media: Canvas mousedown event fired!");
        window.saturne.media.onMouseDown(e.originalEvent || e);
    });
    $(document).on('mousemove touchmove', function(e) {
        if (!window.saturne.media.isDrawing) { return; }
        window.saturne.media.onMouseMove(e.originalEvent || e);
    });
    $(document).on('mouseup touchend', function(e) {
        if (!window.saturne.media.isDrawing) { return; }
        window.saturne.media.onMouseUp(e.originalEvent || e);
    });
};

window.saturne.media.updatePhotoResolutionDisplay = function() {
    const sizeSelect = document.getElementById('photo-size-select');
    const displaySpan = document.getElementById('photo-resolution-display');
    if (displaySpan && sizeSelect) {
        const match = sizeSelect.options[sizeSelect.selectedIndex].text.match(/\(([^)]+)\)/);
        displaySpan.textContent = match ? '(' + match[1] + ')' : '(' + sizeSelect.options[sizeSelect.selectedIndex].text + ')';
    }
};

window.saturne.media.onFileInputChange = function() {
    const fastUploadOptions = $(this).closest('.linked-medias').find('.fast-upload-options');
    window.saturne.media.uploadTargetOptions = {
        objectType: fastUploadOptions.attr('data-from-type'),
        objectSubType: fastUploadOptions.attr('data-from-subtype'),
        objectSubdir: fastUploadOptions.attr('data-from-subdir')
    };

    if (this.files && this.files.length > 0) {
        const startIdx = window.saturne.media.photoFilesArray.length;
        Array.from(this.files).forEach(file => {
            window.saturne.media.photoFilesArray.push({
                file: file,
                blobUrl: URL.createObjectURL(file), // Active display URL
                editedDataUrl: null, // Saved after editing
                status: 'pending' // pending, done
            });
        });
        
        window.saturne.media.renderThumbnails($(this));
        
        if (window.saturne.media.photoFilesArray.length > startIdx) {
            window.saturne.media.currentIndex = startIdx; // Open first newly added
            window.saturne.media.openEditor();
        }
    }
    // Reset file input to allow standard 'change' event next time
    $(this).val('');
};

window.saturne.media.renderThumbnails = function(inputElem) {
    let container = inputElem.closest('.linked-medias').find('.saturne-thumbnails-container');
    if (container.length === 0) {
        container = $('<div class="saturne-thumbnails-container" style="display:flex; gap:10px; margin-top:15px; flex-wrap:wrap;"></div>');
        inputElem.closest('.linked-medias').append(container);
    }
    
    container.empty();
    
    window.saturne.media.photoFilesArray.forEach((item, idx) => {
        const wrapper = $('<div style="position:relative; width:60px; height:60px; border-radius:8px; overflow:hidden; border:2px solid #ddd; cursor:pointer;" class="doli-thumb-preview" data-index="'+idx+'"></div>');
        const img = $('<img style="width:100%; height:100%; object-fit:cover;" src="'+ (item.editedDataUrl || item.blobUrl) +'">');
        const badge = $('<div style="position:absolute; top:2px; right:2px; background:rgba(0,0,0,0.6); color:white; border-radius:50%; width:16px; height:16px; font-size:10px; display:flex; justify-content:center; align-items:center;">'+(idx+1)+'</div>');
        wrapper.append(img).append(badge);
        container.append(wrapper);
    });
};

window.saturne.media.openEditor = function() {
    if (window.saturne.media.photoFilesArray.length === 0) { return; }
    const item = window.saturne.media.photoFilesArray[window.saturne.media.currentIndex];
    
    const activeModal = $(document).find('.wpeo-modal.modal-upload-image').last();
    $('.wpeo-modal.modal-upload-image').removeClass('modal-active'); // Clean others just in case
    activeModal.addClass('modal-active');
    
    // Si plusieurs modales, on s'assure de cibler un composant propre
    window.saturne.media.canvas = activeModal.find('.photo-editor-canvas')[0];
    if (!window.saturne.media.canvas) {
        console.error("Saturne Media: Could not find .photo-editor-canvas inside active modal!");
        return;
    }
    
    window.saturne.media.ctx = window.saturne.media.canvas.getContext('2d', { willReadFrequently: true });
    
    // Dynamic Header and Pagination logic based on Gallery vs Editor
    const total = window.saturne.media.photoFilesArray.length;
    const isGalleryMode = (total > 0 && window.saturne.media.photoFilesArray[0].status === 'done');
    
    if (isGalleryMode) {
        activeModal.find('#doli-editor-header-icon').removeClass('fa-crop-alt').addClass('fa-camera-retro');
        activeModal.find('#doli-editor-header-title').text('Consulter les photos');
    } else {
        activeModal.find('#doli-editor-header-icon').removeClass('fa-camera-retro').addClass('fa-crop-alt');
        activeModal.find('#doli-editor-header-title').text('Éditer la photo');
    }
    
    // Add pagination overlay span if missing
    if (activeModal.find('.photo-editor-pagination').length === 0) {
        activeModal.find('#doli-editor-header-title').after('<span class="photo-editor-pagination" style="color: #64748b; font-size: 14px; font-weight: bold; margin-left: 10px; margin-right: 15px;"></span>');
    }
    
    if (isGalleryMode || total > 1) {
        activeModal.find('.photo-editor-pagination').text((window.saturne.media.currentIndex + 1) + ' / ' + total).show();
        activeModal.find('#photo-size-select').parent().hide(); // Hide the triple dots 
        
        // Show/hide arrows based on index
        if (window.saturne.media.currentIndex > 0) { activeModal.find('#doli-editor-arrow-left').css('display', 'flex'); }
        else { activeModal.find('#doli-editor-arrow-left').hide(); }
        
        if (window.saturne.media.currentIndex < total - 1) { activeModal.find('#doli-editor-arrow-right').css('display', 'flex'); }
        else { activeModal.find('#doli-editor-arrow-right').hide(); }
    } else {
        activeModal.find('.photo-editor-pagination').hide();
        activeModal.find('#photo-size-select').parent().show();
        activeModal.find('#doli-editor-arrow-left').hide();
        activeModal.find('#doli-editor-arrow-right').hide();
    }

    console.log("Saturne Media: openEditor initialized successfully on canvas", window.saturne.media.canvas);

    const img = new Image();
    
    // Reset save diskette for clean gallery image
    $('.image-save-diskette').css({'background-color': '#95a5a6', 'cursor': 'default'});
    
    img.onload = function() {
        const sizeSelect = activeModal.find('#photo-size-select')[0];
        let maxDim = 4000;
        if (sizeSelect && sizeSelect.value === 'fullhd') { maxDim = 1920; }
        if (sizeSelect && sizeSelect.value === 'hd') { maxDim = 1280; }
        
        let ratio = 1;
        if (img.width > maxDim || img.height > maxDim) {
            ratio = maxDim / Math.max(img.width, img.height);
        }
        
        window.saturne.media.canvas.width = img.width * ratio;
        window.saturne.media.canvas.height = img.height * ratio;
        window.saturne.media.ctx.clearRect(0, 0, window.saturne.media.canvas.width, window.saturne.media.canvas.height);
        window.saturne.media.ctx.drawImage(img, 0, 0, window.saturne.media.canvas.width, window.saturne.media.canvas.height);
        window.saturne.media.historyStack = []; 
        console.log("Saturne Media: Canvas rendered. Dimensions:", window.saturne.media.canvas.width, window.saturne.media.canvas.height);
        
        // Let CSS fit-content natively hug the layout 
        activeModal.find('.photo-editor-modal-content').css({
            'width': 'fit-content',
            'height': 'fit-content',
            'min-width': '450px' // Keep toolbar from squishing
        });
        
        window.saturne.media.updatePhotoResolutionDisplay();
    };
    img.src = item.editedDataUrl || item.blobUrl;
};

window.saturne.media.saveCanvasToCurrentIndex = function() {
    if (window.saturne.media.photoFilesArray[window.saturne.media.currentIndex] && window.saturne.media.canvas) {
        const dataUrl = window.saturne.media.canvas.toDataURL('image/jpeg', 0.85);
        window.saturne.media.photoFilesArray[window.saturne.media.currentIndex].editedDataUrl = dataUrl;
    }
};

window.saturne.media.switchImage = function(direction) {
    window.saturne.media.saveCanvasToCurrentIndex();
    
    let newIdx = window.saturne.media.currentIndex + direction;
    if (newIdx >= 0 && newIdx < window.saturne.media.photoFilesArray.length) {
        window.saturne.media.currentIndex = newIdx;
        window.saturne.media.openEditor();
    }
};

window.saturne.media.validateAndQueue = function() {
    window.saturne.media.saveCanvasToCurrentIndex();
    $('.modal-upload-image').removeClass('modal-active');
    window.saturne.media.renderThumbnails($('.fast-upload-improvement').eq(0)); // refresh visible thumbs
    
    // Save to IndexedDB queue
    window.saturne.media.photoFilesArray.forEach(item => {
        if (item.status === 'pending') {
            const dbItem = {
                id: Date.now() + Math.random().toString(36).substr(2, 9),
                timestamp: Date.now(),
                imgData: item.editedDataUrl || item.blobUrl, 
                objectType: window.saturne.media.uploadTargetOptions.objectType,
                objectSubType: window.saturne.media.uploadTargetOptions.objectSubType,
                objectSubdir: window.saturne.media.uploadTargetOptions.objectSubdir,
                status: 'pending' // pending, error
            };
            
            // To ensure we get a Base64 from Blob if not edited
            if (!item.editedDataUrl && item.blobUrl) {
                const reader = new FileReader();
                reader.readAsDataURL(item.file); 
                reader.onloadend = function() {
                    dbItem.imgData = reader.result;
                    window.saturne.media.queueDb.add(dbItem, window.saturne.media.syncPendingUploads);
                };
            } else {
                window.saturne.media.queueDb.add(dbItem, window.saturne.media.syncPendingUploads);
            }
        }
    });

    // Clear local active queues after validating
    window.saturne.media.photoFilesArray = [];
    $('.saturne-thumbnails-container').empty(); 
};

window.saturne.media.syncPendingUploads = function() {
    window.saturne.media.renderPendingQueue();
    if (!navigator.onLine) { return; }

    window.saturne.media.queueDb.getAll(items => {
        if (!items || items.length === 0) { return; }
        
        items.forEach(dbItem => {
            if (dbItem.status === 'pending') { // Only sync pending items
                const token = window.saturne.toolbox.getToken();
                
                let baseUrl = window.location.origin + window.location.pathname + window.location.search;
                let querySeparator = baseUrl.indexOf('?') !== -1 ? '&' : '?';
                
                let formData = new FormData();
                let byteString = atob(dbItem.imgData.split(',')[1]);
                let mimeString = dbItem.imgData.split(',')[0].split(':')[1].split(';')[0];
                let ab = new ArrayBuffer(byteString.length);
                let ia = new Uint8Array(ab);
                for (let i = 0; i < byteString.length; i++) { ia[i] = byteString.charCodeAt(i); }
                
                formData.append('img', new Blob([ab], {type: mimeString}), 'image.jpg');
                formData.append('objectType', dbItem.objectType || '');
                formData.append('objectSubType', dbItem.objectSubType || '');
                formData.append('objectSubdir', dbItem.objectSubdir || '');

                $.ajax({
                    url: baseUrl + querySeparator + 'subaction=add_img&token=' + token,
                    type: 'POST',
                    processData: false,
                    contentType: false,
                    data: formData,
                    success: function(resp) {
                        window.saturne.media.queueDb.remove(dbItem.id, window.saturne.media.renderPendingQueue);
                        
                        if ($('.floatleft.inline-block.valignmiddle.divphotoref').length > 0) {
                            $('.floatleft.inline-block.valignmiddle.divphotoref').replaceWith($(resp).find('.floatleft.inline-block.valignmiddle.divphotoref'));
                        }
                        
                        const newContent = $(resp).find('.linked-medias.' + dbItem.objectSubType).children();
                        if (newContent.length > 0) {
                            $('.linked-medias.' + dbItem.objectSubType).html(newContent);
                        } else {
                            if (window.saturne.SaturneError) {
                                let e = new window.saturne.SaturneError('Saturne-1005', "Le conteneur .linked-medias est vide ou non renvoyé par le serveur.");
                                window.saturne.showError(e, $('.linked-medias.' + dbItem.objectSubType)[0], true);
                            }
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        const failedItem = {...dbItem, status: 'error'};
                        window.saturne.media.queueDb.add(failedItem, window.saturne.media.renderPendingQueue);
                        if (window.saturne.SaturneError) {
                            let e = new window.saturne.SaturneError('Saturne-1500', errorThrown || textStatus, { status: jqXHR ? jqXHR.status : 'N/A' });
                            window.saturne.showError(e, $('.linked-medias.' + dbItem.objectSubType)[0], true);
                        }
                    }
                });
            }
        });
    });
};

window.saturne.media.syncPendingUploadsSingleFallback = function(dbItem, callback) {
    if (!navigator.onLine) {
        alert("Hors ligne : impossible d'envoyer (" + dbItem.objectSubType + ")");
        return;
    }
    const token = window.saturne.toolbox ? window.saturne.toolbox.getToken() : $('input[name="token"]').val();
    let baseUrl = window.location.origin + window.location.pathname + window.location.search;
    let querySeparator = baseUrl.indexOf('?') !== -1 ? '&' : '?';
    
    let formData = new FormData();
    let byteString = atob(dbItem.imgData.split(',')[1]);
    let mimeString = dbItem.imgData.split(',')[0].split(':')[1].split(';')[0];
    let ab = new ArrayBuffer(byteString.length);
    let ia = new Uint8Array(ab);
    for (let i = 0; i < byteString.length; i++) { ia[i] = byteString.charCodeAt(i); }
    
    formData.append('img', new Blob([ab], {type: mimeString}), 'image.jpg');
    formData.append('objectType', dbItem.objectType || '');
    formData.append('objectSubType', dbItem.objectSubType || '');
    formData.append('objectSubdir', dbItem.objectSubdir || '');
    
    $.ajax({
        url: baseUrl + querySeparator + 'subaction=add_img&token=' + token,
        type: 'POST',
        processData: false,
        contentType: false,
        data: formData,
        success: function(resp) {
            if ($('.floatleft.inline-block.valignmiddle.divphotoref').length > 0) {
                $('.floatleft.inline-block.valignmiddle.divphotoref').replaceWith($(resp).find('.floatleft.inline-block.valignmiddle.divphotoref'));
            }
            const newContent = $(resp).find('.linked-medias.' + dbItem.objectSubType).children();
            if (newContent.length > 0) {
                $('.linked-medias.' + dbItem.objectSubType).html(newContent);
            } else {
                if (window.saturne.SaturneError) {
                    let e = new window.saturne.SaturneError('Saturne-1005', "Rendu vide : Le conteneur .linked-medias est vide.");
                    window.saturne.showError(e, $('.linked-medias.' + dbItem.objectSubType)[0], true);
                }
            }
            if (callback) { callback(); }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            if (window.saturne.SaturneError) {
                let e = new window.saturne.SaturneError('Saturne-1500', errorThrown || textStatus, { status: jqXHR ? jqXHR.status : 'N/A' });
                window.saturne.showError(e, $('.linked-medias.' + dbItem.objectSubType)[0], true);
            }
        }
    });
};

window.saturne.media.cancelEditor = function() {
    $('.modal-upload-image').removeClass('modal-active');
    
    // Auto trigger the page's file input to "Reprendre" or take a new photo!
    const externalInput = $('#label-upload-media .fast-upload-improvement');
    if (externalInput.length > 0) { externalInput.click(); }
};

// Canvas drawing functions ...
window.saturne.media.getMousePos = function(e) {
    const canvas = window.saturne.media.canvas;
    const rect = canvas.getBoundingClientRect();
    let clientX = e.clientX; let clientY = e.clientY;
    if (e.touches && e.touches.length > 0) { clientX = e.touches[0].clientX; clientY = e.touches[0].clientY; }
    else if (e.changedTouches && e.changedTouches.length > 0) { clientX = e.changedTouches[0].clientX; clientY = e.changedTouches[0].clientY; }
    return {
        logicalX: clientX - rect.left, logicalY: clientY - rect.top,
        x: (clientX - rect.left) * (canvas.width / rect.width),
        y: (clientY - rect.top) * (canvas.height / rect.height),
        clientX: clientX, clientY: clientY
    };
};

window.saturne.media.saveState = function() {
    window.saturne.media.historyStack.push(window.saturne.media.ctx.getImageData(0, 0, window.saturne.media.canvas.width, window.saturne.media.canvas.height));
    if (window.saturne.media.historyStack.length > 20) { window.saturne.media.historyStack.shift(); }
    
    // Light up the save diskette because modification is active!
    $('.image-save-diskette').css({'background-color': '#2ecc71', 'cursor': 'pointer'});
};

window.saturne.media.undoLastAction = function() {
    if (window.saturne.media.historyStack.length > 0) {
        const lastState = window.saturne.media.historyStack.pop();
        window.saturne.media.canvas.width = lastState.width; window.saturne.media.canvas.height = lastState.height;
        window.saturne.media.ctx.putImageData(lastState, 0, 0);
        
        if (window.saturne.media.historyStack.length === 0) {
            $('.image-save-diskette').css({'background-color': '#95a5a6', 'cursor': 'default'});
        }
    }
};

window.saturne.media.onMouseDown = function(e) {
    window.saturne.media.saveState();
    const ctx = window.saturne.media.ctx;
    const mode = window.saturne.media.currentMode;
    const color = $('#draw-color-picker').val();

    if (mode === 'text') {
        if (e.preventDefault) { e.preventDefault(); }
        const pos = window.saturne.media.getMousePos(e);
        window.saturne.media.addTextInput(pos.x, pos.y, pos.clientX, pos.clientY);
        return;
    }

    window.saturne.media.isDrawing = true;
    const pos = window.saturne.media.getMousePos(e);
    window.saturne.media.startX = pos.x; window.saturne.media.startY = pos.y;
    window.saturne.media.startClientX = pos.clientX; window.saturne.media.startClientY = pos.clientY;

    window.saturne.media.snapshot = ctx.getImageData(0, 0, window.saturne.media.canvas.width, window.saturne.media.canvas.height);

    if (mode === 'pencil') {
        ctx.beginPath();
        ctx.arc(window.saturne.media.startX, window.saturne.media.startY, 3, 0, Math.PI * 2);
        ctx.fillStyle = color; ctx.fill(); ctx.beginPath();
    } else if (mode === 'sequence') {
        window.saturne.media.drawSequenceCircle(ctx, window.saturne.media.startX, window.saturne.media.startY, window.saturne.media.sequenceCounter, color);
    } else if (mode === 'crop') {
        const cropDiv = $(window.saturne.media.canvas).parent().find('.doli-crop-selection')[0];
        const containerRect = window.saturne.media.canvas.parentElement.getBoundingClientRect();
        cropDiv.style.left = (window.saturne.media.startClientX - containerRect.left) + 'px';
        cropDiv.style.top = (window.saturne.media.startClientY - containerRect.top) + 'px';
        cropDiv.style.width = '0px'; cropDiv.style.height = '0px';
        cropDiv.style.display = 'block';
    }
};

window.saturne.media.onMouseMove = function(e) {
    if (e.preventDefault) { e.preventDefault(); }
    const pos = window.saturne.media.getMousePos(e);
    const ctx = window.saturne.media.ctx;
    const mode = window.saturne.media.currentMode;
    const color = $('#draw-color-picker').val();
    const startX = window.saturne.media.startX; const startY = window.saturne.media.startY;

    if (mode === 'pencil') {
        ctx.strokeStyle = color; ctx.lineWidth = 6; ctx.lineCap = 'round'; ctx.lineJoin = 'round';
        ctx.moveTo(startX, startY); ctx.lineTo(pos.x, pos.y); ctx.stroke();
        window.saturne.media.startX = pos.x; window.saturne.media.startY = pos.y;
    } else if (['arrow', 'rect', 'blur', 'sequence'].includes(mode)) {
        ctx.putImageData(window.saturne.media.snapshot, 0, 0);
        if (mode === 'arrow') { window.saturne.media.drawArrow(ctx, startX, startY, pos.x, pos.y, color); }
        if (mode === 'rect') { window.saturne.media.drawRect(ctx, startX, startY, pos.x, pos.y, color); }
        if (mode === 'blur') { ctx.fillStyle = 'rgba(100, 100, 100, 0.5)'; ctx.fillRect(startX, startY, pos.x - startX, pos.y - startY); }
        if (mode === 'sequence') {
            if (Math.hypot(pos.x - startX, pos.y - startY) > 20) { window.saturne.media.drawArrow(ctx, startX, startY, pos.x, pos.y, color); }
            window.saturne.media.drawSequenceCircle(ctx, startX, startY, window.saturne.media.sequenceCounter, color);
        }
    } else if (mode === 'crop') {
        const cropDiv = $(window.saturne.media.canvas).parent().find('.doli-crop-selection')[0];
        const cRect = window.saturne.media.canvas.getBoundingClientRect();
        const pRect = window.saturne.media.canvas.parentElement.getBoundingClientRect();
        
        const cx = Math.max(cRect.left, Math.min(pos.clientX, cRect.right));
        const cy = Math.max(cRect.top, Math.min(pos.clientY, cRect.bottom));
        const sx = Math.max(cRect.left, Math.min(window.saturne.media.startClientX, cRect.right));
        const sy = Math.max(cRect.top, Math.min(window.saturne.media.startClientY, cRect.bottom));

        cropDiv.style.left = (Math.min(cx, sx) - pRect.left) + 'px';
        cropDiv.style.top = (Math.min(cy, sy) - pRect.top) + 'px';
        cropDiv.style.width = Math.abs(cx - sx) + 'px';
        cropDiv.style.height = Math.abs(cy - sy) + 'px';
    }
};

window.saturne.media.onMouseUp = function(e) {
    window.saturne.media.isDrawing = false;
    const pos = window.saturne.media.getMousePos(e);
    const ctx = window.saturne.media.ctx;
    const mode = window.saturne.media.currentMode;
    const color = $('#draw-color-picker').val();
    const startX = window.saturne.media.startX; const startY = window.saturne.media.startY;

    if (mode === 'pencil') {
        ctx.closePath();
    } else if (['arrow', 'rect', 'blur', 'sequence'].includes(mode)) {
        ctx.putImageData(window.saturne.media.snapshot, 0, 0);
        if (mode === 'arrow') { window.saturne.media.drawArrow(ctx, startX, startY, pos.x, pos.y, color); }
        if (mode === 'rect') { window.saturne.media.drawRect(ctx, startX, startY, pos.x, pos.y, color); }
        if (mode === 'blur') {
            if (Math.abs(pos.x - startX) > 5 && Math.abs(pos.y - startY) > 5) { window.saturne.media.applyAreaBlur(ctx, startX, startY, pos.x - startX, pos.y - startY, 10); }
            else { window.saturne.media.historyStack.pop(); }
        }
        if (mode === 'sequence') {
            if (Math.hypot(pos.x - startX, pos.y - startY) > 20) { window.saturne.media.drawArrow(ctx, startX, startY, pos.x, pos.y, color); }
            window.saturne.media.drawSequenceCircle(ctx, startX, startY, window.saturne.media.sequenceCounter, color);
            window.saturne.media.sequenceCounter++;
        }
    } else if (mode === 'crop') {
        $(window.saturne.media.canvas).parent().find('.doli-crop-selection')[0].style.display = 'none';
        const canvas = window.saturne.media.canvas;
        const cx = Math.max(0, Math.min(pos.x, canvas.width)); const cy = Math.max(0, Math.min(pos.y, canvas.height));
        const sx = Math.max(0, Math.min(startX, canvas.width)); const sy = Math.max(0, Math.min(startY, canvas.height));
        
        if (Math.abs(cx - sx) > 20 && Math.abs(cy - sy) > 20) { window.saturne.media.applyCrop(Math.min(cx, sx), Math.min(cy, sy), Math.abs(cx - sx), Math.abs(cy - sy)); }
        else { window.saturne.media.historyStack.pop(); }
    }
};

window.saturne.media.drawArrow = function(context, fromX, fromY, toX, toY, color) {
    const headlen = 20; 
    const angle = Math.atan2(toY - fromY, toX - fromX);
    context.beginPath(); context.strokeStyle = color; context.lineWidth = 6;
    context.moveTo(fromX, fromY); context.lineTo(toX - 15 * Math.cos(angle), toY - 15 * Math.sin(angle)); context.stroke();
    context.beginPath(); context.fillStyle = color; context.moveTo(toX, toY);
    context.lineTo(toX - headlen * Math.cos(angle - Math.PI / 6), toY - headlen * Math.sin(angle - Math.PI / 6));
    context.lineTo(toX - headlen * Math.cos(angle + Math.PI / 6), toY - headlen * Math.sin(angle + Math.PI / 6));
    context.lineTo(toX, toY); context.fill();
};

window.saturne.media.drawRect = function(context, fromX, fromY, toX, toY, color) {
    context.beginPath(); context.strokeStyle = color; context.lineWidth = 6;
    context.rect(fromX, fromY, toX - fromX, toY - fromY); context.stroke();
};

window.saturne.media.drawSequenceCircle = function(context, x, y, number, color) {
    context.beginPath(); context.arc(x, y, 20, 0, 2 * Math.PI, false); context.fillStyle = color; context.fill();
    context.lineWidth = 3; context.strokeStyle = '#ffffff'; context.stroke();
    context.fillStyle = '#ffffff'; context.font = 'bold 20px Arial'; context.textAlign = 'center'; context.textBaseline = 'middle';
    context.fillText(number.toString(), x, y + 2);
};

window.saturne.media.applyAreaBlur = function(context, x, y, w, h, blurAmount) {
    const rx = w < 0 ? x + w : x; const ry = h < 0 ? y + h : y;
    const rw = Math.abs(w); const rh = Math.abs(h);
    const imageData = context.getImageData(rx, ry, rw, rh);
    const tempCanvas = document.createElement('canvas'); tempCanvas.width = rw; tempCanvas.height = rh;
    tempCanvas.getContext('2d').putImageData(imageData, 0, 0);
    const blurCanvas = document.createElement('canvas'); blurCanvas.width = rw; blurCanvas.height = rh;
    const bCtx = blurCanvas.getContext('2d'); bCtx.filter = `blur(${blurAmount}px)`; bCtx.drawImage(tempCanvas, 0, 0);
    context.drawImage(blurCanvas, rx, ry);
};

window.saturne.media.applyCrop = function(x, y, w, h) {
    const croppedImage = window.saturne.media.ctx.getImageData(x, y, w, h);
    window.saturne.media.canvas.width = w; window.saturne.media.canvas.height = h;
    window.saturne.media.ctx.putImageData(croppedImage, 0, 0);
};

window.saturne.media.rotateCanvas = function() {
    window.saturne.media.saveState();
    const canvas = window.saturne.media.canvas;
    const tempCanvas = document.createElement('canvas'); tempCanvas.width = canvas.height; tempCanvas.height = canvas.width;
    const tctx = tempCanvas.getContext('2d'); tctx.translate(tempCanvas.width / 2, tempCanvas.height / 2);
    tctx.rotate(90 * Math.PI / 180); tctx.drawImage(canvas, -canvas.width / 2, -canvas.height / 2);
    canvas.width = tempCanvas.width; canvas.height = tempCanvas.height;
    window.saturne.media.ctx.clearRect(0, 0, canvas.width, canvas.height); window.saturne.media.ctx.drawImage(tempCanvas, 0, 0);
};

window.saturne.media.addTextInput = function(canvasX, canvasY, clientX, clientY) {
    const existing = document.getElementById('doli-floating-text-input');
    if (existing) { existing.blur(); }

    const canvas = window.saturne.media.canvas;
    const ctx = window.saturne.media.ctx;
    const initialScaleY = canvas.height / canvas.getBoundingClientRect().height;
    const color = $('#draw-color-picker').val();

    const containerRect = canvas.parentElement.getBoundingClientRect();
    const input = document.createElement('textarea');
    input.id = 'doli-floating-text-input';
    input.spellcheck = false;
    input.style.position = 'absolute'; 
    input.style.left = Math.max(0, clientX - containerRect.left) + 'px'; 
    input.style.top = Math.max(0, clientY - containerRect.top) + 'px';
    input.style.color = color; input.style.fontSize = '24px'; input.style.fontWeight = 'bold'; input.style.fontFamily = 'Arial';
    input.style.outline = 'none'; input.style.border = '2px dotted rgba(255, 255, 255, 0.8)';
    input.style.background = 'rgba(0, 0, 0, 0.15)'; input.style.zIndex = '999999';
    input.style.minWidth = '150px'; input.style.minHeight = '40px'; input.style.resize = 'none';
    canvas.parentElement.appendChild(input);

    input.addEventListener('input', function() {
        this.style.width = Math.max(150, this.scrollWidth + 10) + 'px';
        this.style.height = Math.max(40, this.scrollHeight + 10) + 'px';
    });

    requestAnimationFrame(() => input.focus());

    input.addEventListener('blur', () => {
        if (input.value.trim() !== '') {
            const fontSize = Math.max(20, Math.floor(24 * initialScaleY));
            ctx.font = 'bold ' + fontSize + 'px Arial'; ctx.fillStyle = color; ctx.textBaseline = 'top';
            ctx.shadowColor = 'rgba(0,0,0,0.8)'; ctx.shadowBlur = 4; ctx.shadowOffsetX = 1; ctx.shadowOffsetY = 1;

            const lines = input.value.split('\n');
            let currentY = canvasY;
            lines.forEach(line => { ctx.fillText(line, canvasX, currentY); currentY += fontSize * 1.2; });
            ctx.shadowColor = 'transparent';
        } else { window.saturne.media.historyStack.pop(); }
        if (input.parentNode) { input.parentNode.removeChild(input); }
    });
};

window.saturne.media.saveToServer = function(e) {
    e.preventDefault();
    const btn = $(this);
    if (btn.css('backgroundColor') === 'rgb(46, 204, 113)' || btn.css('background-color') === '#2ecc71' || btn.css('cursor') === 'pointer') {
        const item = window.saturne.media.photoFilesArray[window.saturne.media.currentIndex];
        window.saturne.media.saveCanvasToCurrentIndex();
        btn.css({'background-color': '#95a5a6', 'cursor': 'default'});
        const icon = btn.find('i');
        icon.removeClass('fa-save').addClass('fa-spinner fa-spin');
        
        if (item.status === 'done' && item.file && item.file.name) {
             let rootUrl = '';
             if (typeof dolibarr !== 'undefined' && dolibarr.url && dolibarr.url.root) {
                 rootUrl = dolibarr.url.root;
             } else if (typeof dolibarr_main_url_root !== 'undefined') {
                 rootUrl = dolibarr_main_url_root;
             } else {
                 const path = window.location.pathname;
                 if (path.indexOf('/custom/') !== -1) { rootUrl = path.substring(0, path.indexOf('/custom/')); }
             }
             
             $.ajax({
                 url: rootUrl + '/custom/saturne/admin/mediastest.php',
                 method: 'POST',
                 data: { token: $('input[name="token"]').val(), subaction: 'save_image_override', action: 'save_image_override', filename: item.file.name, base64: item.editedDataUrl },
                 dataType: 'json',
                 success: function(response) {
                     icon.removeClass('fa-spinner fa-spin').addClass('fa-save');
                     if (response && response.status === 'ok') {
                         console.log("Saturne Media: Saved to server successfully.");
                     } else {
                         const detail = response && response.msg ? response.msg : "Inconnu";
                         if (window.saturne.showError) { window.saturne.showError('Saturne-1004', ': ' + detail); }
                     }
                 },
                 error: function(xhr, status, error) {
                     icon.removeClass('fa-spinner fa-spin').addClass('fa-save');
                     if (xhr.status === 413 || (xhr.responseText && xhr.responseText.includes('exceeds'))) {
                         if (window.saturne.showError) { window.saturne.showError('Saturne-1002'); }
                     } else {
                         if (window.saturne.showError) { window.saturne.showError('Saturne-1003', error); }
                     }
                 }
             });
        } else {
             icon.removeClass('fa-spinner fa-spin').addClass('fa-save');
        }
    }
};

$(document).on('click', '.image-save-diskette', window.saturne.media.saveToServer);

// Force initialization safely
$(document).ready(function() {
    if (!window.saturne.media.queueDb.db) {
        window.saturne.media.init();
    }
});
