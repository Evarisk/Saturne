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
<div id="saturne-photo-editor-modal">
    <div class="saturne-photo-editor-content">

        <!-- Header -->
        <div class="saturne-photo-editor-header">
            <div class="saturne-photo-editor-header__left">
                <i class="fas fa-crop-alt saturne-photo-editor-header__icon"></i>
                <h3 class="saturne-photo-editor-header__title">
                    <?php echo $langs->trans('Image'); ?>
                    <span id="saturne-photo-resolution-display"></span>
                </h3>
            </div>
            <div class="saturne-photo-editor-header__settings" title="<?php echo $langs->trans('Settings'); ?>">
                <i class="fas fa-ellipsis-v"></i>
                <select id="saturne-photo-size-select">
                    <option value="hd" selected>HD (720p)</option>
                    <option value="fullhd">Full HD (1080p)</option>
                    <option value="full">Original (FULL)</option>
                </select>
            </div>
        </div>

        <!-- Canvas area -->
        <div id="saturne-editor-canvas-container">
            <canvas id="saturne-photo-editor-canvas"></canvas>
            <div id="saturne-crop-selection"></div>
            <div id="saturne-photo-index-badge"></div>
            <!-- Navigation arrows overlaid on the canvas -->
            <button type="button" id="saturne-btn-prev-photo"><i class="fas fa-chevron-left"></i></button>
            <button type="button" id="saturne-btn-next-photo"><i class="fas fa-chevron-right"></i></button>
        </div>

        <!-- Toolbar -->
        <div class="saturne-photo-editor-toolbar">

            <!-- Cancel / close -->
            <button type="button" id="saturne-btn-cancel-photo" class="saturne-editor-btn saturne-editor-btn--cancel" title="<?php echo $langs->trans('Cancel'); ?>">
                <i class="fas fa-times"></i>
            </button>

            <!-- Drawing tools -->
            <button type="button" class="saturne-tool-btn saturne-editor-btn saturne-editor-btn--tool" data-mode="crop"     title="<?php echo $langs->trans('Crop'); ?>"><i class="fas fa-crop"></i></button>
            <button type="button" class="saturne-tool-btn saturne-editor-btn saturne-editor-btn--tool" data-mode="rotate"   title="<?php echo $langs->trans('Rotate'); ?>"><i class="fas fa-redo"></i></button>
            <div id="saturne-pencil-tool-container">
                <button type="button" class="saturne-tool-btn active" data-mode="pencil" title="<?php echo $langs->trans('Draw'); ?>"><i class="fas fa-pencil-alt"></i></button>
                <div class="saturne-pencil-color-wrapper">
                    <input type="color" id="saturne-draw-color-picker" value="#e74c3c" title="<?php echo $langs->trans('Color'); ?>" />
                </div>
            </div>
            <button type="button" class="saturne-tool-btn saturne-editor-btn saturne-editor-btn--tool" data-mode="text"     title="<?php echo $langs->trans('Text'); ?>"><i class="fas fa-font"></i></button>
            <button type="button" class="saturne-tool-btn saturne-editor-btn saturne-editor-btn--tool" data-mode="arrow"    title="<?php echo $langs->trans('Arrow'); ?>"><i class="fas fa-long-arrow-alt-right saturne-icon-arrow"></i></button>
            <button type="button" class="saturne-tool-btn saturne-editor-btn saturne-editor-btn--tool" data-mode="rect"     title="<?php echo $langs->trans('Frame'); ?>"><i class="far fa-square"></i></button>
            <button type="button" class="saturne-tool-btn saturne-editor-btn saturne-editor-btn--tool" data-mode="blur"     title="<?php echo $langs->trans('Blur'); ?>"><i class="fas fa-eye-slash"></i></button>
            <button type="button" class="saturne-tool-btn saturne-editor-btn saturne-editor-btn--tool" data-mode="sequence" title="<?php echo $langs->trans('Sequence'); ?>"><i class="fas fa-list-ol"></i></button>

            <!-- Undo -->
            <button type="button" id="saturne-btn-undo-photo" class="saturne-editor-btn saturne-editor-btn--undo" title="<?php echo $langs->trans('Undo'); ?>"><i class="fas fa-reply"></i></button>

            <!-- Save (uploads, keeps modal open) -->
            <button type="button" id="saturne-btn-validate-photo" class="saturne-editor-btn saturne-editor-btn--save" title="<?php echo $langs->trans('Save'); ?>"><i class="fas fa-save"></i></button>

            <!-- OK (closes modal) -->
            <button type="button" id="saturne-btn-ok-photo" class="saturne-editor-btn saturne-editor-btn--ok" title="OK"><i class="fas fa-check"></i></button>
        </div>

    </div>
</div>
<!-- photo_editor_modal end -->
