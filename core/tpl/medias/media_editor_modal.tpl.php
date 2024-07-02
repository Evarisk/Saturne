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
    <div class="wpeo-modal modal-upload-image" id="modal-upload-image" style="z-index: 1010;">
        <?php $mediaResolution = explode('-', getDolGlobalString('SATURNE_MEDIA_RESOLUTION_USED'));
        $mediaResolution = explode('x', $mediaResolution[1]); ?>
        <input type="hidden" class="fast-upload-options" data-image-resolution-width="<?php echo $mediaResolution[0]; ?>" data-image-resolution-height="<?php echo $mediaResolution[1]; ?>">
        <div class="modal-container wpeo-modal-event">
            <!-- Modal-Header-->
            <div class="modal-header">
                <h2 class="modal-title"><?php echo $langs->trans('Image'); ?></h2>
                <div class="modal-close"><i class="fas fa-2x fa-times"></i></div>
            </div>
            <!-- Modal-ADD Image Content-->
            <div class="modal-content" id="#modalContent">
                <div class="canvas-container">
                    <canvas id="canvas" style="border: #0b419b solid 2px;"></canvas>
                </div>
            </div>
            <!-- Modal-Footer-->
            <div class="modal-footer">
                <div class="image-move wpeo-button button-blue button-square-50">
                    <span><i class="fas fa-arrows-alt"></i></span>
                </div>
                <div class="image-rotate-left wpeo-button button-grey button-square-50">
                    <span><i class="fas fa-undo-alt"></i></span>
                </div>
                <div class="image-rotate-right wpeo-button button-grey button-square-50">
                    <span><i class="fas fa-redo-alt"></i></span>
                </div>
<!--                <div class="image-undo wpeo-button button-grey button-square-50">-->
<!--                    <span><i class="fas fa-undo-alt"></i></span>-->
<!--                </div>-->
                <!-- Button to start drawing -->
                <div class="image-drawing wpeo-button button-grey button-square-50">
                    <span><i class="fas fa-pen"></i></span>
                </div>
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
