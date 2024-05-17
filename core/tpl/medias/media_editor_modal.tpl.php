<?php
/* Copyright (C) 2024 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/medias/media_editor_modal.tpl.php
 * \ingroup saturne
 * \brief   Template page for media editor modal
 */

/**
 * The following vars must be defined :
 * Global : $langs
 */

?>

<!-- File start-->
<div class="modal-upload-image">
    <input type="hidden" name="token" value="<?php echo newToken(); ?>">
    <div class="wpeo-modal modal-upload-image" id="modal-upload-image">
        <input type="hidden" class="fast-upload-options">
        <div class="modal-container wpeo-modal-event">
            <!-- Modal-Header-->
            <div class="modal-header">
                <h2 class="modal-title"><?php echo $langs->trans('Image'); ?></h2>
                <div class="modal-close"><i class="fas fa-2x fa-times"></i></div>
            </div>
            <!-- Modal-ADD Image Content-->
            <div class="modal-content" id="#modalContent" style="height: 75%;">
                <div class="canvas-container">
                    <canvas id="canvas" style="border: #0b419b solid 2px; width: 100%;"></canvas>
                </div>
            </div>
            <!-- Modal-Footer-->
            <div class="modal-footer">
                <div class="image-rotate-left wpeo-button button-grey button-square-50">
                    <span><i class="fas fa-undo-alt"></i></span>
                </div>
                <div class="image-rotate-right wpeo-button button-grey button-square-50">
                    <span><i class="fas fa-redo-alt"></i></span>
                </div>
<!--                <div class="image-undo wpeo-button button-grey button-square-50">-->
<!--                    <span><i class="fas fa-undo-alt"></i></span>-->
<!--                </div>-->
                <div class="image-erase wpeo-button button-grey button-square-50">
                    <span><i class="fas fa-eraser"></i></span>
                </div>
                <div class="image-validate wpeo-button button-blue button-square-50" value="0">
                    <span><i class="fas fa-check"></i></span>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
