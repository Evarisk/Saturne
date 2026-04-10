<?php

/* Copyright (C) 2024-2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    core/tpl/medias/photo_editor_modal.tpl.php
 * \ingroup saturne
 * \brief   Reusable photo editor modal (canvas-based, touch-friendly).
 *
 * Include this TPL once per page that needs the photo editor.
 * Open the editor from JS via: window.saturne.photoEditor.open(url, onSave)
 *   - url    {string}   Image URL to load (or null for a new upload).
 *   - onSave {Function} Callback receiving a Blob when the user validates.
 */

?>
<!-- photo_editor_modal start -->
<div id="saturne-photo-editor-modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.85); align-items:center; justify-content:center; padding:10px; box-sizing:border-box;">
    <div class="saturne-photo-editor-content">

        <!-- Header -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; border-bottom:2px solid #3498db; padding-bottom:5px; width:100%;">
            <div style="display:flex; align-items:center;">
                <i class="fas fa-crop-alt" style="color:#f39c12; margin-right:8px; font-size:1.2em;"></i>
                <h3 style="margin:0; font-size:1.1em; color:#333; font-weight:600;">
                    <?php echo $langs->trans('Image'); ?>
                    <span id="saturne-photo-resolution-display" style="color:#e74c3c; font-weight:normal; margin-left:5px; font-size:10px;"></span>
                </h3>
            </div>
            <div style="position:relative; color:#94a3b8; font-size:20px; cursor:pointer; padding:0 5px;" title="<?php echo $langs->trans('Settings'); ?>">
                <i class="fas fa-ellipsis-v"></i>
                <select id="saturne-photo-size-select" style="position:absolute; top:0; right:0; width:30px; height:100%; opacity:0; cursor:pointer; -webkit-appearance:none; appearance:none;">
                    <option value="hd" selected>HD (720p)</option>
                    <option value="fullhd">Full HD (1080p)</option>
                    <option value="full">Original (FULL)</option>
                </select>
            </div>
        </div>

        <!-- Canvas area -->
        <div style="flex:1; display:flex; justify-content:center; align-items:center; overflow:hidden; background:#1e293b; border-radius:8px; position:relative; min-height:150px; width:100%; height:100%;" id="saturne-editor-canvas-container">
            <canvas id="saturne-photo-editor-canvas" style="max-width:100%; max-height:100%; object-fit:contain; touch-action:none; cursor:crosshair;"></canvas>
            <div id="saturne-crop-selection" style="display:none; position:absolute; border:2px dashed #fff; background:rgba(255,255,255,0.2); pointer-events:none;"></div>
            <div id="saturne-photo-index-badge" style="display:none; position:absolute; top:8px; right:8px; background:rgba(0,0,0,0.55); color:#fff; border-radius:10px; padding:2px 10px; font-size:12px; font-weight:600; pointer-events:none; backdrop-filter:blur(4px);"></div>
        </div>

        <!-- Gallery navigation (visible only when multiple images) -->
        <div id="saturne-photo-nav" style="display:none; justify-content:center; align-items:center; gap:12px; padding:6px 0; color:#fff; font-size:13px;">
            <button type="button" id="saturne-btn-prev-photo" style="background:#34495e; color:white; border:none; width:32px; height:32px; border-radius:50%; cursor:pointer; display:flex; justify-content:center; align-items:center;"><i class="fas fa-chevron-left"></i></button>
            <span id="saturne-photo-nav-label" style="font-size:14px; font-weight:600; color:#1e293b; min-width:50px; text-align:center;"></span>
            <button type="button" id="saturne-btn-next-photo" style="background:#34495e; color:white; border:none; width:32px; height:32px; border-radius:50%; cursor:pointer; display:flex; justify-content:center; align-items:center;"><i class="fas fa-chevron-right"></i></button>
        </div>

        <!-- Toolbar -->
        <div class="saturne-photo-editor-toolbar">

            <!-- Cancel / retake -->
            <button type="button" id="saturne-btn-cancel-photo" title="<?php echo $langs->trans('Cancel'); ?>" style="flex-shrink:0; background-color:#f39c12; color:white; border:none; width:40px; height:40px; border-radius:4px; cursor:pointer; display:flex; justify-content:center; align-items:center;">
                <i class="fas fa-times"></i>
            </button>

            <!-- Drawing tools -->
            <button type="button" class="saturne-tool-btn" data-mode="crop"     title="<?php echo $langs->trans('Crop'); ?>"    style="flex-shrink:0; background-color:#34495e; color:white; border:none; width:40px; height:40px; border-radius:4px; cursor:pointer; display:flex; justify-content:center; align-items:center;"><i class="fas fa-crop"></i></button>
            <button type="button" class="saturne-tool-btn" data-mode="rotate"   title="<?php echo $langs->trans('Rotate'); ?>"  style="flex-shrink:0; background-color:#34495e; color:white; border:none; width:40px; height:40px; border-radius:4px; cursor:pointer; display:flex; justify-content:center; align-items:center;"><i class="fas fa-redo"></i></button>
            <div style="flex-shrink:0; display:flex; background-color:#3498db; border-radius:4px; overflow:hidden; height:40px;" id="saturne-pencil-tool-container">
                <button type="button" class="saturne-tool-btn active" data-mode="pencil" title="<?php echo $langs->trans('Draw'); ?>" style="background-color:transparent; color:white; border:none; width:40px; height:100%; cursor:pointer; display:flex; justify-content:center; align-items:center;"><i class="fas fa-pencil-alt"></i></button>
                <div style="padding:4px; display:flex; align-items:center; background:rgba(0,0,0,0.1);">
                    <input type="color" id="saturne-draw-color-picker" value="#e74c3c" style="width:24px; height:24px; border:none; padding:0; cursor:pointer;" title="<?php echo $langs->trans('Color'); ?>" />
                </div>
            </div>
            <button type="button" class="saturne-tool-btn" data-mode="text"     title="<?php echo $langs->trans('Text'); ?>"   style="flex-shrink:0; background-color:#34495e; color:white; border:none; width:40px; height:40px; border-radius:4px; cursor:pointer; display:flex; justify-content:center; align-items:center;"><i class="fas fa-font"></i></button>
            <button type="button" class="saturne-tool-btn" data-mode="arrow"    title="<?php echo $langs->trans('Arrow'); ?>"  style="flex-shrink:0; background-color:#34495e; color:white; border:none; width:40px; height:40px; border-radius:4px; cursor:pointer; display:flex; justify-content:center; align-items:center;"><i class="fas fa-long-arrow-alt-right" style="transform:rotate(-45deg);"></i></button>
            <button type="button" class="saturne-tool-btn" data-mode="rect"     title="<?php echo $langs->trans('Frame'); ?>"  style="flex-shrink:0; background-color:#34495e; color:white; border:none; width:40px; height:40px; border-radius:4px; cursor:pointer; display:flex; justify-content:center; align-items:center;"><i class="far fa-square"></i></button>
            <button type="button" class="saturne-tool-btn" data-mode="blur"     title="<?php echo $langs->trans('Blur'); ?>"   style="flex-shrink:0; background-color:#34495e; color:white; border:none; width:40px; height:40px; border-radius:4px; cursor:pointer; display:flex; justify-content:center; align-items:center;"><i class="fas fa-eye-slash"></i></button>
            <button type="button" class="saturne-tool-btn" data-mode="sequence" title="<?php echo $langs->trans('Sequence'); ?>" style="flex-shrink:0; background-color:#34495e; color:white; border:none; width:40px; height:40px; border-radius:4px; cursor:pointer; display:flex; justify-content:center; align-items:center; font-weight:bold;"><i class="fas fa-list-ol"></i></button>

            <!-- Undo & validate -->
            <div style="display:flex; gap:6px; margin-left:auto; flex-shrink:0;">
                <button type="button" id="saturne-btn-undo-photo" title="<?php echo $langs->trans('Undo'); ?>" style="flex-shrink:0; background-color:#7f8c8d; color:white; border:none; width:40px; height:40px; border-radius:4px; cursor:pointer; display:flex; justify-content:center; align-items:center;"><i class="fas fa-reply"></i></button>
                <button type="button" id="saturne-btn-validate-photo" title="<?php echo $langs->trans('Save'); ?>" style="flex-shrink:0; background-color:#2ecc71; color:white; border:none; height:40px; padding:0 14px; border-radius:4px; cursor:pointer; display:flex; justify-content:center; align-items:center; gap:6px; font-weight:600; font-size:13px;"><i class="fas fa-save"></i> <?php echo $langs->trans('Save'); ?></button>
            </div>
        </div>

    </div>
</div>
<!-- photo_editor_modal end -->
