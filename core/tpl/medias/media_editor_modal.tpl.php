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
<div class="saturne-media-modal-wrapper">
    <input type="hidden" name="token" value="<?php echo newToken(); ?>">
    <div class="wpeo-modal modal-upload-image modal-close-only-with-button" id="modal-upload-image" style="z-index: 1010; overflow: hidden;">
        <?php $mediaResolution = explode('-', getDolGlobalString('SATURNE_MEDIA_RESOLUTION_USED'));
        $mediaResolution = explode('x', $mediaResolution[1]); ?>
        <input type="hidden" class="fast-upload-options" data-image-resolution-width="<?php echo $mediaResolution[0]; ?>" data-image-resolution-height="<?php echo $mediaResolution[1]; ?>">
        <div class="modal-container wpeo-modal-event" style="display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; box-sizing: border-box;">
            <!-- Modal-ADD Image Content -->
            <div class="modal-content photo-editor-modal-content" style="width: fit-content; height: fit-content; max-width: 95vw; max-height: 95vh; margin: auto; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2); display: flex; flex-direction: column; overflow: hidden !important; background: #fff; border-radius: 8px; padding: 15px; box-sizing: border-box;">
                
                <!-- ReedCRM Style Header inside Content -->
                <div style="flex-shrink: 0; display:flex; justify-content: space-between; align-items: center; margin-bottom: 10px; border-bottom: 2px solid #3498db; padding-bottom: 10px; width: 100%; box-sizing: border-box;">
                    <div style="display: flex; align-items: center;">
                        <i id="doli-editor-header-icon" class="fas fa-crop-alt" style="color: #f39c12; margin-right: 8px; font-size: 1.2em;"></i>
                        <h3 id="doli-editor-header-title" style="margin: 0; font-size: 1.1em; color: #333; font-weight: 600;">Éditer la photo</h3>
                        <span id="photo-resolution-display" style="color: #e74c3c; font-weight: normal; margin-left: 5px; font-size: 10px;"></span>
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="position: relative; color: #94a3b8; font-size: 20px; cursor: pointer; padding: 0 5px;" title="Réglages de qualité de l'image">
                            <i class="fas fa-ellipsis-v"></i>
                            <select id="photo-size-select" style="position: absolute; top: 0; right: 0; width: 30px; height: 100%; opacity: 0; cursor: pointer; -webkit-appearance: none; appearance: none;">
                                <option value="hd" selected>HD (720p)</option>
                                <option value="fullhd">Full HD (1080p)</option>
                                <option value="full">Originale (FULL)</option>
                            </select>
                        </div>
                        <i class="fas fa-times modal-close" style="color: #e74c3c; font-size: 22px; cursor: pointer;" title="Fermer"></i>
                    </div>
                </div>

                <div class="canvas-container" style="flex: 0 1 auto; display:flex; justify-content:center; align-items:center; position:relative; min-height: 0;">
                    
                    <div id="doli-editor-arrow-left" style="display:none; position:absolute; left: 10px; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.5); color: white; width: 40px; height: 40px; border-radius: 50%; justify-content: center; align-items: center; cursor: pointer; z-index: 10;"><i class="fas fa-chevron-left"></i></div>
                    <div id="doli-editor-arrow-right" style="display:none; position:absolute; right: 10px; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.5); color: white; width: 40px; height: 40px; border-radius: 50%; justify-content: center; align-items: center; cursor: pointer; z-index: 10;"><i class="fas fa-chevron-right"></i></div>

                    <canvas class="photo-editor-canvas" style="max-width: 100%; max-height: calc(95vh - 120px); height: auto; object-fit: contain; touch-action: none; cursor: crosshair;"></canvas>
                    <div class="doli-crop-selection" style="display: none; position: absolute; border: 2px dashed #3498db; background: rgba(52,152,219,0.2); pointer-events: none; z-index: 20;"></div>
                </div>

                <!-- Unified Horizontal Toolbar -->
                <div class="photo-editor-toolbar" style="flex-shrink: 0; margin-top: 15px; display:flex; flex-wrap: wrap; padding-bottom: 0px; justify-content: center; align-items: center; gap: 6px; width: 100%;">
                    <button type="button" class="btn-cancel-photo" title="<?php echo $langs->trans('RetakePhoto') ?? 'Reprendre'; ?>" style="flex-shrink: 0; background-color:#f39c12; color: white; border: none; width: 40px; height: 40px; border-radius: 4px; cursor: pointer; display:flex; justify-content:center; align-items:center; transition: opacity 0.2s;">
                        <i class="fas fa-camera" style="font-size: 1.2em;"></i>
                    </button>

                    <button type="button" class="doli-tool-btn" data-mode="crop" title="<?php echo $langs->trans('Crop'); ?>" style="flex-shrink: 0; background-color: #34495e; color: white; border: none; width: 40px; height: 40px; border-radius: 4px; cursor: pointer; display:flex; justify-content:center; align-items:center; transition: background 0.2s;">
                        <i class="fas fa-crop"></i>
                    </button>
                    <button type="button" class="doli-tool-btn image-rotate-right" data-mode="rotate" title="<?php echo $langs->trans('Rotate'); ?>" style="flex-shrink: 0; background-color: #34495e; color: white; border: none; width: 40px; height: 40px; border-radius: 4px; cursor: pointer; display:flex; justify-content:center; align-items:center; transition: background 0.2s;">
                        <i class="fas fa-redo"></i>
                    </button>
                    <div style="flex-shrink: 0; display: flex; background-color: #3498db; border-radius: 4px; overflow: hidden; height: 40px;" id="pencil-tool-container">
                        <button type="button" class="doli-tool-btn active" data-mode="pencil" title="<?php echo $langs->trans('Draw'); ?>" style="background-color: transparent; color: white; border: none; width: 40px; height: 100%; cursor: pointer; display:flex; justify-content:center; align-items:center;">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                        <div style="padding: 4px; display: flex; align-items: center; background: rgba(0,0,0,0.1);">
                            <input type="color" id="draw-color-picker" value="#e74c3c" style="width: 24px; height: 24px; border:none; padding:0; cursor:pointer;" title="<?php echo $langs->trans('Color'); ?>" />
                        </div>
                    </div>
                    <button type="button" class="doli-tool-btn" data-mode="text" title="<?php echo $langs->trans('Text'); ?>" style="flex-shrink: 0; background-color: #34495e; color: white; border: none; width: 40px; height: 40px; border-radius: 4px; cursor: pointer; display:flex; justify-content:center; align-items:center; transition: background 0.2s;">
                        <i class="fas fa-font"></i>
                    </button>
                    <button type="button" class="doli-tool-btn" data-mode="arrow" title="<?php echo $langs->trans('Arrow'); ?>" style="flex-shrink: 0; background-color: #34495e; color: white; border: none; width: 40px; height: 40px; border-radius: 4px; cursor: pointer; display:flex; justify-content:center; align-items:center; transition: background 0.2s;">
                        <i class="fas fa-long-arrow-alt-right" style="transform: rotate(-45deg);"></i>
                    </button>
                    <button type="button" class="doli-tool-btn" data-mode="rect" title="<?php echo $langs->trans('Rectangle'); ?>" style="flex-shrink: 0; background-color: #34495e; color: white; border: none; width: 40px; height: 40px; border-radius: 4px; cursor: pointer; display:flex; justify-content:center; align-items:center; transition: background 0.2s;">
                        <i class="far fa-square"></i>
                    </button>
                    <button type="button" class="doli-tool-btn" data-mode="blur" title="<?php echo $langs->trans('Blur'); ?>" style="flex-shrink: 0; background-color: #34495e; color: white; border: none; width: 40px; height: 40px; border-radius: 4px; cursor: pointer; display:flex; justify-content:center; align-items:center; transition: background 0.2s;">
                        <i class="fas fa-eye-slash"></i>
                    </button>
                    <button type="button" class="doli-tool-btn" data-mode="sequence" title="<?php echo $langs->trans('Sequence'); ?>" style="flex-shrink: 0; background-color: #34495e; color: white; border: none; width: 40px; height: 40px; border-radius: 4px; cursor: pointer; display:flex; justify-content:center; align-items:center; font-weight: bold; font-family: sans-serif;">
                        <i class="fas fa-list-ol"></i>
                    </button>

                    <div style="display: flex; gap: 6px; margin-left: auto; flex-shrink: 0; flex-wrap: nowrap;">
                        <button type="button" class="image-save-diskette" title="Sauvegarder les modifications" style="flex-shrink: 0; background-color: #95a5a6; color: white; border: none; width: 40px; height: 40px; border-radius: 4px; cursor: default; display:flex; justify-content:center; align-items:center; transition: background 0.2s;">
                            <i class="fas fa-save"></i>
                        </button>
                        <button type="button" class="doli-tool-btn image-undo" title="<?php echo $langs->trans('Undo'); ?>" style="flex-shrink: 0; background-color: #7f8c8d; color: white; border: none; width: 40px; height: 40px; border-radius: 4px; cursor: pointer; display:flex; justify-content:center; align-items:center; transition: background 0.2s;">
                            <i class="fas fa-reply"></i>
                        </button>
                        <button type="button" class="image-validate" title="<?php echo $langs->trans('Save'); ?>" style="flex-shrink: 0; background-color:#2ecc71; color: white; border: none; width: 40px; height: 40px; border-radius: 4px; cursor: pointer; display:flex; justify-content:center; align-items:center; transition: opacity 0.2s;">
                            <i class="fas fa-check" style="font-size: 1.2em;"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
