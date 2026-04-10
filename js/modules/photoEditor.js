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
 * \file    js/modules/photoEditor.js
 * \ingroup saturne
 * \brief   Canvas-based photo editor modal (crop, rotate, draw, text, blur…)
 *
 * Public API:
 *   window.saturne.photoEditor.open(url, onSave)
 *     - url    {string|null}  Image URL to load, or null to let user pick a file.
 *     - onSave {Function}     Called with a Blob when the user validates.
 */

/**
 * Photo editor namespace
 *
 * @memberof Saturne_PhotoEditor
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @type {Object}
 */
window.saturne.photoEditor = {};

/* Internal state */
window.saturne.photoEditor._canvas       = null;
window.saturne.photoEditor._ctx          = null;
window.saturne.photoEditor._modal        = null;
window.saturne.photoEditor._historyStack = [];
window.saturne.photoEditor._currentMode  = 'pencil';
window.saturne.photoEditor._isDrawing    = false;
window.saturne.photoEditor._snapshot     = null;
window.saturne.photoEditor._startX       = 0;
window.saturne.photoEditor._startY       = 0;
window.saturne.photoEditor._startCX      = 0;
window.saturne.photoEditor._startCY      = 0;
window.saturne.photoEditor._seqCounter   = 1;
window.saturne.photoEditor._onSave       = null;
window.saturne.photoEditor._urls         = [];
window.saturne.photoEditor._currentIndex = 0;

/**
 * Photo editor init
 *
 * @memberof Saturne_PhotoEditor
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void}
 */
window.saturne.photoEditor.init = function() {
  window.saturne.photoEditor.event();
};

/**
 * Photo editor event bindings
 *
 * @memberof Saturne_PhotoEditor
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @returns {void}
 */
window.saturne.photoEditor.event = function() {
  var modal = document.getElementById('saturne-photo-editor-modal');
  if (!modal) {
    return;
  }

  window.saturne.photoEditor._modal  = modal;
  window.saturne.photoEditor._canvas = document.getElementById('saturne-photo-editor-canvas');
  window.saturne.photoEditor._ctx    = window.saturne.photoEditor._canvas.getContext('2d', { willReadFrequently: true });

  var canvas       = window.saturne.photoEditor._canvas;
  var sizeSelect   = document.getElementById('saturne-photo-size-select');
  var colorPicker  = document.getElementById('saturne-draw-color-picker');
  var cropDiv      = document.getElementById('saturne-crop-selection');
  var btnCancel    = document.getElementById('saturne-btn-cancel-photo');
  var btnValidate  = document.getElementById('saturne-btn-validate-photo');
  var btnUndo      = document.getElementById('saturne-btn-undo-photo');
  var resDisplay   = document.getElementById('saturne-photo-resolution-display');

  // Resolution display
  function updateResDisplay() {
    if (!resDisplay || !sizeSelect) return;
    var opt   = sizeSelect.options[sizeSelect.selectedIndex];
    var match = opt.text.match(/\(([^)]+)\)/);
    resDisplay.textContent = match ? '(' + match[1] + ')' : '(' + opt.text + ')';
  }
  updateResDisplay();
  sizeSelect.addEventListener('change', updateResDisplay);

  // Tool buttons
  var toolBtns = document.querySelectorAll('.saturne-tool-btn[data-mode]');
  toolBtns.forEach(function(btn) {
    btn.addEventListener('click', function() {
      var mode = btn.getAttribute('data-mode');
      if (mode === 'rotate') {
        window.saturne.photoEditor._rotateCanvas();
        return;
      }
      toolBtns.forEach(function(b) {
        if (b.parentElement.id === 'saturne-pencil-tool-container') {
          b.parentElement.style.backgroundColor = '#34495e';
        } else {
          b.style.backgroundColor = '#34495e';
        }
      });
      if (btn.parentElement.id === 'saturne-pencil-tool-container') {
        btn.parentElement.style.backgroundColor = '#3498db';
      } else {
        btn.style.backgroundColor = '#3498db';
      }
      window.saturne.photoEditor._currentMode = mode;
      canvas.style.cursor = (mode === 'text') ? 'text' : 'crosshair';
      if (mode === 'sequence') {
        window.saturne.photoEditor._seqCounter = 1;
      }
    });
  });

  // Gallery navigation
  var btnPrev = document.getElementById('saturne-btn-prev-photo');
  var btnNext = document.getElementById('saturne-btn-next-photo');

  btnPrev.addEventListener('click', function() {
    var pe = window.saturne.photoEditor;
    if (pe._urls.length < 2) return;
    pe._currentIndex = (pe._currentIndex - 1 + pe._urls.length) % pe._urls.length;
    pe._loadUrlIntoCanvas(pe._urls[pe._currentIndex], function() {
      var badge = document.getElementById('saturne-photo-index-badge');
      if (badge) badge.textContent = (pe._currentIndex + 1) + ' / ' + pe._urls.length;
    });
  });

  btnNext.addEventListener('click', function() {
    var pe = window.saturne.photoEditor;
    if (pe._urls.length < 2) return;
    pe._currentIndex = (pe._currentIndex + 1) % pe._urls.length;
    pe._loadUrlIntoCanvas(pe._urls[pe._currentIndex], function() {
      var badge = document.getElementById('saturne-photo-index-badge');
      if (badge) badge.textContent = (pe._currentIndex + 1) + ' / ' + pe._urls.length;
    });
  });

  // Undo
  btnUndo.addEventListener('click', function() {
    var stack = window.saturne.photoEditor._historyStack;
    if (stack.length > 0) {
      var last = stack.pop();
      canvas.width  = last.width;
      canvas.height = last.height;
      window.saturne.photoEditor._ctx.putImageData(last, 0, 0);
    }
  });

  // Canvas drawing events
  canvas.addEventListener('mousedown', window.saturne.photoEditor._onMouseDown);
  window.addEventListener('mousemove', window.saturne.photoEditor._onMouseMove);
  window.addEventListener('mouseup',   window.saturne.photoEditor._onMouseUp);
  canvas.addEventListener('touchstart', window.saturne.photoEditor._onMouseDown, { passive: false });
  window.addEventListener('touchmove',  window.saturne.photoEditor._onMouseMove, { passive: false });
  window.addEventListener('touchend',   window.saturne.photoEditor._onMouseUp);

  // Cancel — discard and close
  btnCancel.addEventListener('click', function() {
    window.saturne.photoEditor._close();
  });

  // OK — close without triggering another save
  var btnOk = document.getElementById('saturne-btn-ok-photo');
  btnOk.addEventListener('click', function() {
    window.saturne.photoEditor._close();
  });

  // Close on overlay click
  modal.addEventListener('click', function(e) {
    if (e.target === modal) {
      window.saturne.photoEditor._close();
    }
  });

  // Close on Escape
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && modal.style.display !== 'none') {
      window.saturne.photoEditor._close();
    }
  });

  // Save — upload to server, keep modal open
  btnValidate.addEventListener('click', function() {
    var activeText = document.getElementById('saturne-floating-text-input');
    if (activeText) {
      activeText.blur();
    }
    var onSave = window.saturne.photoEditor._onSave;
    if (typeof onSave !== 'function') {
      return;
    }
    canvas.toBlob(function(blob) {
      onSave(blob);
    }, 'image/jpeg', 0.85);
  });
};

/**
 * Open the photo editor with an image URL
 *
 * @memberof Saturne_PhotoEditor
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param   {string}   url    Image URL to load
 * @param   {Function} onSave Callback receiving a Blob on validate
 * @returns {void}
 */
window.saturne.photoEditor.open = function(urlOrUrls, onSave, startIndex) {
  var modal = window.saturne.photoEditor._modal;
  if (!modal) {
    return;
  }

  var pe             = window.saturne.photoEditor;
  pe._onSave         = onSave || null;
  pe._historyStack   = [];
  pe._urls           = Array.isArray(urlOrUrls) ? urlOrUrls : [urlOrUrls];
  pe._currentIndex   = (typeof startIndex === 'number') ? startIndex : 0;

  var btnPrevEl = document.getElementById('saturne-btn-prev-photo');
  var btnNextEl = document.getElementById('saturne-btn-next-photo');
  var badge     = document.getElementById('saturne-photo-index-badge');
  if (pe._urls.length > 1) {
    var label = (pe._currentIndex + 1) + ' / ' + pe._urls.length;
    if (btnPrevEl) btnPrevEl.style.display = 'flex';
    if (btnNextEl) btnNextEl.style.display = 'flex';
    if (badge) {
      badge.textContent   = label;
      badge.style.display = 'block';
    }
  } else {
    if (btnPrevEl) btnPrevEl.style.display = 'none';
    if (btnNextEl) btnNextEl.style.display = 'none';
    if (badge) badge.style.display = 'none';
  }

  pe._loadUrlIntoCanvas(pe._urls[pe._currentIndex], function() {
    modal.style.display = 'flex';
  });
};

window.saturne.photoEditor._loadUrlIntoCanvas = function(url, callback) {
  var pe        = window.saturne.photoEditor;
  var canvas    = pe._canvas;
  var ctx       = pe._ctx;
  var sizeSelect = document.getElementById('saturne-photo-size-select');
  var isFullHD  = sizeSelect && sizeSelect.value === 'fullhd';
  var maxDim    = isFullHD ? 1920 : 1280;

  pe._historyStack = [];
  var img      = new Image();
  img.crossOrigin = 'Anonymous';
  img.onload   = function() {
    var ratio = 1;
    if (img.width > maxDim || img.height > maxDim) {
      ratio = maxDim / Math.max(img.width, img.height);
    }
    canvas.width  = img.width  * ratio;
    canvas.height = img.height * ratio;
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
    if (typeof callback === 'function') callback();
  };
  img.src = url;
};

/**
 * Open the photo editor with a File or Blob object
 *
 * @memberof Saturne_PhotoEditor
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param   {File|Blob} file   File to load
 * @param   {Function}  onSave Callback receiving a Blob on validate
 * @returns {void}
 */
window.saturne.photoEditor.openFile = function(file, onSave) {
  var url = URL.createObjectURL(file);
  window.saturne.photoEditor.open(url, function(blob) {
    URL.revokeObjectURL(url);
    if (typeof onSave === 'function') {
      onSave(blob);
    }
  });
};

/* -------------------------------------------------------------------------
 * Private helpers
 * ---------------------------------------------------------------------- */

window.saturne.photoEditor._close = function() {
  var modal = window.saturne.photoEditor._modal;
  if (modal) {
    modal.style.display = 'none';
  }
  window.saturne.photoEditor._onSave = null;
};

window.saturne.photoEditor._saveState = function() {
  var canvas = window.saturne.photoEditor._canvas;
  var ctx    = window.saturne.photoEditor._ctx;
  window.saturne.photoEditor._historyStack.push(ctx.getImageData(0, 0, canvas.width, canvas.height));
  if (window.saturne.photoEditor._historyStack.length > 20) {
    window.saturne.photoEditor._historyStack.shift();
  }
};

window.saturne.photoEditor._getPos = function(e) {
  var canvas = window.saturne.photoEditor._canvas;
  var rect   = canvas.getBoundingClientRect();
  var clientX = e.clientX, clientY = e.clientY;
  if (e.touches && e.touches.length > 0) {
    clientX = e.touches[0].clientX;
    clientY = e.touches[0].clientY;
  } else if (e.changedTouches && e.changedTouches.length > 0) {
    clientX = e.changedTouches[0].clientX;
    clientY = e.changedTouches[0].clientY;
  }
  var lx = clientX - rect.left;
  var ly = clientY - rect.top;
  return {
    x: lx * (canvas.width  / rect.width),
    y: ly * (canvas.height / rect.height),
    logicalX: lx,
    logicalY: ly,
    clientX: clientX,
    clientY: clientY
  };
};

window.saturne.photoEditor._onMouseDown = function(e) {
  if (e.target.id === 'saturne-floating-text-input') return;
  var pe   = window.saturne.photoEditor;
  var ctx  = pe._ctx;
  var canvas = pe._canvas;
  var mode = pe._currentMode;
  var cp   = document.getElementById('saturne-draw-color-picker');

  pe._saveState();
  if (mode === 'text') {
    e.preventDefault();
    var pos = pe._getPos(e);
    pe._addTextInput(pos.x, pos.y, pos.clientX, pos.clientY);
    return;
  }

  pe._isDrawing = true;
  var p = pe._getPos(e);
  pe._startX = p.x; pe._startY = p.y;
  pe._startCX = p.clientX; pe._startCY = p.clientY;
  pe._snapshot = ctx.getImageData(0, 0, canvas.width, canvas.height);

  if (mode === 'pencil') {
    ctx.beginPath();
    ctx.arc(pe._startX, pe._startY, 3, 0, Math.PI * 2);
    ctx.fillStyle = cp.value;
    ctx.fill();
    ctx.beginPath();
  } else if (mode === 'sequence') {
    pe._drawSequenceCircle(ctx, pe._startX, pe._startY, pe._seqCounter, cp.value);
  } else if (mode === 'crop') {
    var containerRect = canvas.parentElement.getBoundingClientRect();
    var cropDiv = document.getElementById('saturne-crop-selection');
    cropDiv.style.left   = (pe._startCX - containerRect.left) + 'px';
    cropDiv.style.top    = (pe._startCY - containerRect.top) + 'px';
    cropDiv.style.width  = '0px';
    cropDiv.style.height = '0px';
    cropDiv.style.display = 'block';
  }
};

window.saturne.photoEditor._onMouseMove = function(e) {
  var pe   = window.saturne.photoEditor;
  if (!pe._isDrawing) return;
  e.preventDefault();
  var ctx    = pe._ctx;
  var canvas = pe._canvas;
  var mode   = pe._currentMode;
  var cp     = document.getElementById('saturne-draw-color-picker');
  var p      = pe._getPos(e);

  if (mode === 'pencil') {
    ctx.strokeStyle = cp.value;
    ctx.lineWidth   = 6;
    ctx.lineCap     = 'round';
    ctx.lineJoin    = 'round';
    ctx.moveTo(pe._startX, pe._startY);
    ctx.lineTo(p.x, p.y);
    ctx.stroke();
    pe._startX = p.x; pe._startY = p.y;
  } else if (mode === 'arrow') {
    ctx.putImageData(pe._snapshot, 0, 0);
    pe._drawArrow(ctx, pe._startX, pe._startY, p.x, p.y, cp.value);
  } else if (mode === 'rect') {
    ctx.putImageData(pe._snapshot, 0, 0);
    pe._drawRect(ctx, pe._startX, pe._startY, p.x, p.y, cp.value);
  } else if (mode === 'blur') {
    ctx.putImageData(pe._snapshot, 0, 0);
    ctx.fillStyle = 'rgba(100,100,100,0.5)';
    ctx.fillRect(pe._startX, pe._startY, p.x - pe._startX, p.y - pe._startY);
  } else if (mode === 'sequence') {
    ctx.putImageData(pe._snapshot, 0, 0);
    if (Math.hypot(p.x - pe._startX, p.y - pe._startY) > 20) {
      pe._drawArrow(ctx, pe._startX, pe._startY, p.x, p.y, cp.value);
    }
    pe._drawSequenceCircle(ctx, pe._startX, pe._startY, pe._seqCounter, cp.value);
  } else if (mode === 'crop') {
    var cropDiv       = document.getElementById('saturne-crop-selection');
    var containerRect = canvas.parentElement.getBoundingClientRect();
    var canvasRect    = canvas.getBoundingClientRect();
    var cx  = Math.max(canvasRect.left, Math.min(p.clientX, canvasRect.right));
    var cy  = Math.max(canvasRect.top,  Math.min(p.clientY, canvasRect.bottom));
    var csx = Math.max(canvasRect.left, Math.min(pe._startCX, canvasRect.right));
    var csy = Math.max(canvasRect.top,  Math.min(pe._startCY, canvasRect.bottom));
    cropDiv.style.left   = (Math.min(cx, csx) - containerRect.left) + 'px';
    cropDiv.style.top    = (Math.min(cy, csy) - containerRect.top) + 'px';
    cropDiv.style.width  = Math.abs(cx - csx) + 'px';
    cropDiv.style.height = Math.abs(cy - csy) + 'px';
  }
};

window.saturne.photoEditor._onMouseUp = function(e) {
  var pe   = window.saturne.photoEditor;
  if (!pe._isDrawing) return;
  pe._isDrawing = false;
  var ctx    = pe._ctx;
  var canvas = pe._canvas;
  var mode   = pe._currentMode;
  var cp     = document.getElementById('saturne-draw-color-picker');
  var p      = pe._getPos(e);

  if (mode === 'arrow') {
    ctx.putImageData(pe._snapshot, 0, 0);
    pe._drawArrow(ctx, pe._startX, pe._startY, p.x, p.y, cp.value);
  } else if (mode === 'rect') {
    ctx.putImageData(pe._snapshot, 0, 0);
    pe._drawRect(ctx, pe._startX, pe._startY, p.x, p.y, cp.value);
  } else if (mode === 'blur') {
    ctx.putImageData(pe._snapshot, 0, 0);
    var w = p.x - pe._startX, h = p.y - pe._startY;
    if (Math.abs(w) > 5 && Math.abs(h) > 5) {
      pe._applyAreaBlur(ctx, pe._startX, pe._startY, w, h, 10);
    } else {
      pe._historyStack.pop();
    }
  } else if (mode === 'sequence') {
    ctx.putImageData(pe._snapshot, 0, 0);
    if (Math.hypot(p.x - pe._startX, p.y - pe._startY) > 20) {
      pe._drawArrow(ctx, pe._startX, pe._startY, p.x, p.y, cp.value);
    }
    pe._drawSequenceCircle(ctx, pe._startX, pe._startY, pe._seqCounter, cp.value);
    pe._seqCounter++;
  } else if (mode === 'crop') {
    document.getElementById('saturne-crop-selection').style.display = 'none';
    var cx = Math.max(0, Math.min(p.x, canvas.width));
    var cy = Math.max(0, Math.min(p.y, canvas.height));
    var sx = Math.max(0, Math.min(pe._startX, canvas.width));
    var sy = Math.max(0, Math.min(pe._startY, canvas.height));
    var cw = Math.abs(cx - sx), ch = Math.abs(cy - sy);
    if (cw > 20 && ch > 20) {
      pe._applyCrop(Math.min(cx, sx), Math.min(cy, sy), cw, ch);
    } else {
      pe._historyStack.pop();
    }
  }
};

window.saturne.photoEditor._rotateCanvas = function() {
  var pe   = window.saturne.photoEditor;
  var ctx  = pe._ctx;
  var canvas = pe._canvas;
  pe._saveState();
  var tmp  = document.createElement('canvas');
  tmp.width  = canvas.height;
  tmp.height = canvas.width;
  var tc = tmp.getContext('2d');
  tc.translate(tmp.width / 2, tmp.height / 2);
  tc.rotate(90 * Math.PI / 180);
  tc.drawImage(canvas, -canvas.width / 2, -canvas.height / 2);
  canvas.width  = tmp.width;
  canvas.height = tmp.height;
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  ctx.drawImage(tmp, 0, 0);
};

window.saturne.photoEditor._applyCrop = function(x, y, w, h) {
  var pe     = window.saturne.photoEditor;
  var ctx    = pe._ctx;
  var canvas = pe._canvas;
  var img    = ctx.getImageData(x, y, w, h);
  canvas.width  = w;
  canvas.height = h;
  ctx.putImageData(img, 0, 0);
};

window.saturne.photoEditor._applyAreaBlur = function(ctx, x, y, w, h, amount) {
  var rx = w < 0 ? x + w : x, ry = h < 0 ? y + h : y;
  var rw = Math.abs(w),        rh = Math.abs(h);
  var data = ctx.getImageData(rx, ry, rw, rh);
  var tmp  = document.createElement('canvas');
  tmp.width = rw; tmp.height = rh;
  tmp.getContext('2d').putImageData(data, 0, 0);
  var blur = document.createElement('canvas');
  blur.width = rw; blur.height = rh;
  var bc   = blur.getContext('2d');
  bc.filter = 'blur(' + amount + 'px)';
  bc.drawImage(tmp, 0, 0);
  ctx.drawImage(blur, rx, ry);
};

window.saturne.photoEditor._drawArrow = function(ctx, fx, fy, tx, ty, color) {
  var head  = 20;
  var angle = Math.atan2(ty - fy, tx - fx);
  var ex    = tx - 15 * Math.cos(angle);
  var ey    = ty - 15 * Math.sin(angle);
  ctx.beginPath();
  ctx.strokeStyle = color;
  ctx.lineWidth   = 6;
  ctx.moveTo(fx, fy);
  ctx.lineTo(ex, ey);
  ctx.stroke();
  ctx.beginPath();
  ctx.fillStyle = color;
  ctx.moveTo(tx, ty);
  ctx.lineTo(tx - head * Math.cos(angle - Math.PI / 6), ty - head * Math.sin(angle - Math.PI / 6));
  ctx.lineTo(tx - head * Math.cos(angle + Math.PI / 6), ty - head * Math.sin(angle + Math.PI / 6));
  ctx.closePath();
  ctx.fill();
};

window.saturne.photoEditor._drawRect = function(ctx, fx, fy, tx, ty, color) {
  ctx.beginPath();
  ctx.strokeStyle = color;
  ctx.lineWidth   = 6;
  ctx.rect(fx, fy, tx - fx, ty - fy);
  ctx.stroke();
};

window.saturne.photoEditor._drawSequenceCircle = function(ctx, x, y, num, color) {
  ctx.beginPath();
  ctx.arc(x, y, 20, 0, 2 * Math.PI, false);
  ctx.fillStyle = color;
  ctx.fill();
  ctx.lineWidth   = 3;
  ctx.strokeStyle = '#ffffff';
  ctx.stroke();
  ctx.fillStyle      = '#ffffff';
  ctx.font           = 'bold 20px Arial';
  ctx.textAlign      = 'center';
  ctx.textBaseline   = 'middle';
  ctx.fillText(num.toString(), x, y + 2);
};

window.saturne.photoEditor._addTextInput = function(canvasX, canvasY, clientX, clientY) {
  var existing = document.getElementById('saturne-floating-text-input');
  if (existing) existing.blur();

  var canvas  = window.saturne.photoEditor._canvas;
  var ctx     = window.saturne.photoEditor._ctx;
  var cp      = document.getElementById('saturne-draw-color-picker');
  var initRect  = canvas.getBoundingClientRect();
  var initScaleY = canvas.height / initRect.height;

  var input       = document.createElement('textarea');
  input.id        = 'saturne-floating-text-input';
  input.spellcheck = false;
  input.autocomplete = 'off';
  input.style.position  = 'fixed';
  input.style.left      = clientX + 'px';
  input.style.top       = clientY + 'px';
  input.style.color     = cp.value;
  input.style.fontSize  = '24px';
  input.style.fontWeight = 'bold';
  input.style.fontFamily = 'Arial';
  input.style.outline   = 'none';
  input.style.border    = '2px dotted rgba(255,255,255,0.8)';
  input.style.padding   = '2px 8px';
  input.style.background = 'rgba(0,0,0,0.15)';
  input.style.borderRadius = '4px';
  input.style.textShadow   = '1px 1px 3px rgba(0,0,0,0.8)';
  input.style.boxShadow    = '0 0 6px rgba(0,0,0,0.3)';
  input.style.zIndex    = '999999';
  input.style.minWidth  = '150px';
  input.style.minHeight = '40px';
  input.style.boxSizing = 'border-box';
  input.style.overflow  = 'hidden';
  input.style.resize    = 'none';
  input.placeholder     = 'Texte...';
  document.body.appendChild(input);

  input.addEventListener('input', function() {
    this.style.width  = 'auto';
    this.style.width  = Math.max(150, this.scrollWidth + 10) + 'px';
    this.style.height = 'auto';
    this.style.height = Math.max(40, this.scrollHeight + 10) + 'px';
  });

  requestAnimationFrame(function() { if (input) input.focus(); });

  input.addEventListener('blur', function() {
    var text = input.value;
    if (text.trim() !== '') {
      var fontSize   = Math.max(20, Math.floor(24 * initScaleY));
      ctx.font       = 'bold ' + fontSize + 'px Arial';
      ctx.fillStyle  = cp.value;
      ctx.textBaseline  = 'top';
      ctx.shadowColor   = 'rgba(0,0,0,0.8)';
      ctx.shadowBlur    = 4;
      ctx.shadowOffsetX = 1;
      ctx.shadowOffsetY = 1;
      text.split('\n').forEach(function(line, i) {
        ctx.fillText(line, canvasX, canvasY + i * fontSize * 1.2);
      });
      ctx.shadowColor = 'transparent';
    } else {
      window.saturne.photoEditor._historyStack.pop();
    }
    if (input.parentNode) input.parentNode.removeChild(input);
  });
};
