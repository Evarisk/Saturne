<?php

/* Copyright (C) 2022-2023 EVARISK <technique@evarisk.com>
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
 * \file    lib/saturne.lib.php
 * \ingroup saturne
 * \brief   Library files with common functions for Saturne.
 */

/**
 * Prepare admin pages header.
 *
 * @return array $head Selectable tabs.
 */
function saturne_admin_prepare_head(): array
{
    // Global variables definitions.
    global $langs, $conf;

    // Load translation files required by the page.
    saturne_load_langs();

    // Initialize values.
    $h    = 0;
    $head = [];

    $head[$h][0] = dol_buildpath('/saturne/admin/setup.php', 1);
    $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-cog pictofixedwidth"></i>' . $langs->trans('ModuleSettings') : '<i class="fas fa-cog"></i>';
    $head[$h][2] = 'settings';
    $h++;

    $head[$h][0] = dol_buildpath('/saturne/admin/redirection.php', 1) . '?module_name=Saturne';
    $head[$h][1] = $conf->browser->layout == 'classic' ? '<i class="fas fa-forward pictofixedwidth"></i>' . $langs->trans('Redirections') : '<i class="fas fa-forward"></i>';
    $head[$h][2] = 'redirection';
    $h++;

    $head[$h][0] = dol_buildpath('/saturne/admin/about.php', 1) . '?module_name=Saturne';
    $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fab fa-readme pictofixedwidth"></i>' . $langs->trans('About') : '<i class="fab fa-readme"></i>';
    $head[$h][2] = 'about';
    $h++;

    $head[$h][0] = dol_buildpath('/saturne/admin/information.php', 1) . '?filename=saturne_dev&tab_name=information';
    $head[$h][1] = '<i class="fas fa-hands-helping pictofixedwidth"></i>' . $langs->trans('Contributing');
    $head[$h][2] = 'information';
    $h++;

    $head[$h][0] = dol_buildpath('/saturne/admin/information.php', 1) . '?filename=evarisk_modules&tab_name=evariskModule';
    $head[$h][1] = '<i class="fas fa-cogs pictofixedwidth"></i>' . $langs->trans('SaturneModule', 'Evarisk');
    $head[$h][2] = 'evariskModule';

    $h++;
    $head[$h][0] = dol_buildpath('/saturne/admin/information.php', 1) . '?filename=eoxia_modules&tab_name=eoxiaModule';
    $head[$h][1] = '<i class="fas fa-cogs pictofixedwidth"></i>' . $langs->trans('SaturneModule', 'Eoxia');
    $head[$h][2] = 'eoxiaModule';

    $h++;
    $head[$h][0] = dol_buildpath('/saturne/admin/mediastest.php', 1);
    $head[$h][1] = '<i class="fas fa-camera pictofixedwidth"></i>' . $langs->trans('Test Medias');
    $head[$h][2] = 'mediastest';

    $h++;
    $head[$h][0] = dol_buildpath('/saturne/admin/tools.php', 1);
    $head[$h][1] = '<i class="fas fa-wrench pictofixedwidth"></i>' . $langs->trans('Outils');
    $head[$h][2] = 'tools';
    $h++;

    complete_head_from_modules($conf, $langs, null, $head, $h, 'saturne@saturne');

    complete_head_from_modules($conf, $langs, null, $head, $h, 'saturne@saturne', 'remove');

    return $head;
}

/**
 * Render the Saturne Media Block (Photo + Audio) for external modules.
 *
 * @param string $targetModule   The module saving the file (e.g 'fraispro', 'reedcrm', 'saturne')
 * @param string $targetSubDir   The target subdirectory under dir_output (e.g 'notes_de_frais/NDF-001')
 * @param string $filePrefix     The prefix for the generated file (e.g 'NDF-001_')
 * @param string $rightString    The rights to check on API side (e.g 'fraispro,creer')
 * @param array  $options        Display options (show_photo, show_audio, show_gallery)
 * @return string html HTML block string
 */
function saturne_render_media_block(string $targetModule, string $targetSubDir, string $filePrefix = '', string $rightString = '', array $options = []): string
{
    global $conf, $langs;
    
    $showPhoto = isset($options['show_photo']) ? $options['show_photo'] : true;
    $showAudio = isset($options['show_audio']) ? $options['show_audio'] : true;
    $showGallery = isset($options['show_gallery']) ? $options['show_gallery'] : true;
    
    $out = '';
    
    $containerClass = !empty($targetSubDir) ? $targetSubDir : 'media_dyn';
    
    if ($showPhoto) {
        $out .= '<div class="linked-medias medias ' . htmlspecialchars($containerClass) . '" id="master-media-row-container-photo" style="display:flex; flex-direction:column; gap:8px;">';
        $out .= '  <div class="fast-upload-options" data-from-type="'.htmlspecialchars($targetModule).'" data-from-subtype="'.htmlspecialchars($containerClass).'" data-from-subdir="'.htmlspecialchars($targetSubDir).'" data-prefix="'.htmlspecialchars($filePrefix).'" data-rights="'.htmlspecialchars($rightString).'"></div>';
        $out .= '  <div style="display: flex; align-items: center; gap: 12px; margin-top: 10px; background: transparent; padding: 0;">';
        
        $out .= '    <label for="upload-media" id="label-upload-media" style="cursor:pointer; display:flex; flex-shrink: 0; justify-content:center; align-items:center; width: 50px; min-width: 50px; height: 50px; min-height: 50px; background-color: #f39c12; color: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 0; padding: 0; box-sizing: border-box;">';
        $out .= '      <i class="fas fa-camera" style="font-size: 20px;"></i>';
        $out .= '      <input type="file" id="upload-media" class="file-upload-input fast-upload-improvement" accept="image/*" style="display: none;">';
        $out .= '    </label>';
        
        if ($showGallery) {
            $destinationPath = (empty($conf->$targetModule->dir_output) ? $conf->ecm->dir_output . '/' . $targetModule : $conf->$targetModule->dir_output);
            if (!empty($targetSubDir)) $destinationPath .= '/' . $targetSubDir;
            
            if (dol_is_dir($destinationPath)) {
                $filearray = dol_dir_list($destinationPath, 'files', 0, '', '(\.meta|_preview.*\.png)$', 'date', SORT_DESC);
                $imageFiles = [];
                if (!empty($filearray)) {
                    foreach ($filearray as $file) {
                        if (image_format_supported($file['name']) >= 0) {
                            $imageFiles[] = $file;
                        }
                    }
                }
                $totalPhotos = count($imageFiles);
                
                if ($totalPhotos > 0) {
                    $urls = [];
                    foreach ($imageFiles as $file) {
                        if (empty($conf->$targetModule->dir_output)) {
                            $fUrl = DOL_URL_ROOT . '/document.php?modulepart=ecm&entity=1&file=' . urlencode($targetModule.'/'.$targetSubDir.'/' . $file['name']);
                        } else {
                            $fUrl = DOL_URL_ROOT . '/document.php?modulepart=' . $targetModule . '&entity=1&file=' . urlencode($targetSubDir.'/' . $file['name']);
                        }
                        $urls[] = $fUrl;
                    }
                    $urlsJson = htmlspecialchars(json_encode($urls), ENT_QUOTES, 'UTF-8');
                    
                    $firstImg = $urls[0];
                    $onErrorScript = "if(window.saturne && window.saturne.showError) { window.saturne.showError('Saturne-1009'); this.style.display='none'; } else { this.style.display='none'; }";
                    $imgTag = '<img src="' . htmlspecialchars($firstImg) . '" onerror="' . htmlspecialchars($onErrorScript) . '" style="width:100% !important; height:100% !important; object-fit:cover !important; display:block !important; border-radius:12px !important; margin:0 !important; padding:0 !important; box-sizing:border-box !important;" />';
                    
                    $out .= '    <div class="open-media-editor-as-gallery" data-json="' . $urlsJson . '" style="position: relative; flex-shrink: 0; width: 50px; min-width: 50px; height: 50px; min-height: 50px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); cursor: pointer; display: block; padding: 0; margin: 0; background: #fff; box-sizing: border-box;">';
                    $out .= $imgTag;
                    $out .= '      <span style="position:absolute; top:-6px; right:-6px; background:#8f9ba8; color:white; border-radius:12px; height:18px; min-width:18px; padding: 0 4px; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:600; border: 2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); box-sizing:border-box;">' . $totalPhotos . '</span>';
                    $out .= '    </div>';
                }
            }
        }
        $out .= '  </div>';
        $out .= '</div>';
    }
    
    if ($showAudio) {
        $audioContainerClass = $containerClass . '_audio';
        $out .= '<div class="linked-medias medias ' . htmlspecialchars($audioContainerClass) . '" id="master-media-row-container-audio" style="padding: 10px 0;">';
        $out .= '  <div class="fast-upload-options" data-from-type="'.htmlspecialchars($targetModule).'" data-from-subtype="'.htmlspecialchars($audioContainerClass).'" data-from-subdir="'.htmlspecialchars($targetSubDir).'" data-prefix="'.htmlspecialchars($filePrefix).'" data-rights="'.htmlspecialchars($rightString).'"></div>';
        // --- FETCH EXISTING AUDIOS FIRST TO POPULATE STATES ---
        $destinationPath = (empty($conf->$targetModule->dir_output) ? $conf->ecm->dir_output . '/' . $targetModule : $conf->$targetModule->dir_output);
        if (!empty($targetSubDir)) $destinationPath .= '/' . $targetSubDir;
        
        $audioFiles = [];
        if (dol_is_dir($destinationPath)) {
            $filearray = dol_dir_list($destinationPath, 'files', 0, '', '(\.meta|_preview.*\.png)$', 'date', SORT_DESC);
            if (!empty($filearray)) {
                foreach ($filearray as $file) {
                    if (preg_match('/\.(wav|mp3|ogg|m4a)$/i', $file['name'])) {
                        $audioFiles[] = $file;
                    }
                }
            }
        }
        
        $hasAudio = count($audioFiles) > 0;
        $latestUrlHtml = '';
        if ($hasAudio) {
            $latestFile = $audioFiles[0];
            if (empty($conf->$targetModule->dir_output)) {
                $fUrl = DOL_URL_ROOT . '/document.php?modulepart=ecm&entity=1&file=' . urlencode($targetModule.'/'.$targetSubDir.'/' . $latestFile['name']);
            } else {
                $fUrl = DOL_URL_ROOT . '/document.php?modulepart=' . $targetModule . '&entity=1&file=' . urlencode($targetSubDir.'/' . $latestFile['name']);
            }
            $latestUrlHtml = htmlspecialchars($fUrl, ENT_QUOTES, 'UTF-8');
        }

        $out .= '  <div style="display: flex; align-items: center; gap: 12px; margin-top: 10px; background: transparent; padding: 0;">';
        
        $out .= '    <button type="button" id="start-recording" class="btn-secondary" style="border: none; cursor:pointer; margin:0; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-radius: 12px; width: 50px; min-width: 50px; height: 50px; min-height: 50px; padding: 0; display:flex; justify-content:center; align-items:center; transition: all 0.2s ease; background-color: #8e44ad;">';
        $out .= '      <i class="fas fa-microphone" style="font-size: 20px; color: #fff;"></i>';
        $out .= '    </button>';
        
        $playColor = $hasAudio ? '#7b68ee' : '#cbd5e1';
        $playCursor = $hasAudio ? 'pointer' : 'not-allowed';
        $playDisabled = $hasAudio ? '' : 'disabled';
        
        $out .= '    <div style="position: relative; z-index: 10;">';
        $out .= '      <button type="button" id="play-recording" data-url="'.$latestUrlHtml.'" class="btn-secondary" '.$playDisabled.' style="border: none; cursor:'.$playCursor.'; margin:0; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-radius: 12px; width: 50px; min-width: 50px; height: 50px; min-height: 50px; padding: 0; display:flex; justify-content:center; align-items:center; transition: all 0.2s ease; background-color: '.$playColor.'; z-index: 5; position: relative;">';
        $out .= '        <i class="fas fa-play" style="font-size: 20px; color: #fff;"></i>';
        $out .= '      </button>';
        if ($hasAudio) {
            $nbAudios = count($audioFiles);
            $out .= '      <span class="saturne-audio-badge saturne-open-audio-library" style="position:absolute; top:-6px; right:-6px; background:#e74c3c; color:white; border-radius:12px; height:18px; min-width:18px; padding: 0 4px; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:600; border: 2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); box-sizing:border-box; cursor: pointer; z-index: 30;" title="Ouvrir la bibliothèque">' . $nbAudios . '</span>';
        }
        $out .= '      <button type="button" id="delete-recording" style="display:none; position: absolute; top: -6px; right: -6px; width: 22px; height: 22px; border-radius: 50%; background-color: #e74c3c; color: white; border: none; font-size: 12px; cursor: pointer; justify-content: center; align-items: center; z-index: 20; padding: 0; line-height: 1;">';
        $out .= '        <i class="fas fa-times"></i>';
        $out .= '      </button>';
        $out .= '    </div>';

        $out .= '    <div id="recording-indicator" class="blinking recording-indicator" style="display:none; font-size:11px; margin-left: 5px; color: #e74c3c;">' . $langs->trans('RecordingInProgress') . '</div>';
        
        $out .= '  </div>'; // End button row

        // HIDDEN MODAL CONTENT
        if ($hasAudio) {
            $out .= '<div class="saturne-audio-library-container" style="display:none;" title="Bibliothèque Audio">';
            $out .= '  <div class="saturne-audio-library-content" style="display:flex; flex-direction:column; gap:8px; width: 100%; min-width: 350px;">';
            foreach ($audioFiles as $file) {
                if (empty($conf->$targetModule->dir_output)) {
                    $fUrl = DOL_URL_ROOT . '/document.php?modulepart=ecm&entity=1&file=' . urlencode($targetModule.'/'.$targetSubDir.'/' . $file['name']);
                } else {
                    $fUrl = DOL_URL_ROOT . '/document.php?modulepart=' . $targetModule . '&entity=1&file=' . urlencode($targetSubDir.'/' . $file['name']);
                }
                $fNameHtml = htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8');
                $fUrlHtml = htmlspecialchars($fUrl, ENT_QUOTES, 'UTF-8');
                $fileDate = dol_print_date($file['date'], 'dayhour');
                
                $out .= '<div class="saturne-audio-item" style="display:flex; align-items:center; gap: 10px; background: #f8fafc; padding: 6px 12px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">';
                $out .= '  <div style="flex-grow: 1; max-width: 300px;">';
                $out .= '    <audio controls src="' . $fUrlHtml . '" controlsList="nodownload noplaybackrate" style="height: 35px; width: 100%; outline: none;"></audio>';
                $out .= '  </div>';
                $out .= '  <div style="font-size: 11px; color: #64748b; margin-right: auto; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="'.$fNameHtml.'">' . $fileDate . '</div>';
                $out .= '  <button type="button" class="saturne-delete-media-icon" data-filename="' . $fNameHtml . '" style="background:none; border:none; color: #e74c3c; cursor: pointer; padding: 6px; border-radius: 4px; transition: background 0.2s;" onmouseover="this.style.background=\'#fee2e2\'" onmouseout="this.style.background=\'none\'" title="Supprimer définitivement"><i class="fas fa-trash-alt"></i></button>';
                $out .= '</div>';
            }
            $out .= '  </div>';
            $out .= '</div>';
        }
        
        $out .= '</div>';
    }
    
    return $out;
}

/**
 * Safely create storage directories with correct permissions and an anti-listing index
 *
 * @param string $dir  The absolute path to the directory to create/check.
 * @return int         1 if success/exists, -1 if error.
 */
function saturne_manage_storage_dir(string $dir): int
{
    global $conf;
    
    require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
    
    // Normalize path visually for OS safety
    $dir = str_replace('\\', '/', $dir);
    
    if (dol_is_dir($dir)) {
        return 1;
    }
    
    // Attempt standard Dolibarr directory creation with standard UMASK rules
    $resMkdir = dol_mkdir($dir);
    
    // Aggressive fallback for specific Windows/WAMP path resolution issues
    if ($resMkdir < 0) {
        $mask = !empty($conf->global->MAIN_UMASK) ? octdec($conf->global->MAIN_UMASK) : 0777;
        if (@mkdir($dir, $mask, true)) {
            $resMkdir = 1;
        }
    }
    
    // Post-creation security and verification
    if ($resMkdir >= 0 || dol_is_dir($dir)) {
        // Prevent generic directory listing for security
        $indexFile = $dir . '/index.php';
        if (!file_exists($indexFile)) {
            @file_put_contents($indexFile, '<?php' . "\n" . '// Forbidden' . "\n" . 'header("Location: ../");' . "\n" . 'exit;' . "\n");
        }
        return 1;
    }
    
    return -1;
}
