<?php
/* Copyright (C) 2025-2026 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/actions/media_block_actions.tpl.php
 * \ingroup saturne
 * \brief   Actions of the media block, shared by every page rendering one
 *
 * The media block uploads and deletes over AJAX against the page that renders it. That page must
 * handle these actions and must NOT redirect while doing so, so it includes this template.
 *
 * The following vars must be defined:
 * Global     : $conf, $db, $langs, $user
 * Parameters : $action
 */

// The sub directory comes from the browser: a traversal would write outside the module folder
$mediaBlockSubDir = GETPOST('sub_dir', 'alphanohtml');
if (strpos($mediaBlockSubDir, '..') !== false) {
    $mediaBlockSubDir = '';
}

$mediaBlockModule = dol_strtolower(GETPOST('module_name', 'alpha'));
$mediaBlockDir    = '';

if (!empty($mediaBlockModule)) {
    $mediaBlockDir = !empty($conf->$mediaBlockModule->dir_output)
        ? $conf->$mediaBlockModule->dir_output
        : $conf->ecm->dir_output . '/' . $mediaBlockModule;

    if (!empty($mediaBlockSubDir)) {
        $mediaBlockDir .= '/' . $mediaBlockSubDir;
    }
}

// Upload a photo through the media block
if ($action == 'uploadPhoto' && !empty($mediaBlockDir) && !empty($conf->global->MAIN_UPLOAD_DOC)) {
    if (!dol_is_dir($mediaBlockDir)) {
        dol_mkdir($mediaBlockDir);
    }

    // The block only takes images: anything else is rejected on its real type, not on its name
    $uploadedFiles = isset($_FILES['userfile']) ? $_FILES['userfile'] : [];
    $invalidFile   = false;

    if (!empty($uploadedFiles['tmp_name'])) {
        $tmpNames = is_array($uploadedFiles['tmp_name']) ? $uploadedFiles['tmp_name'] : [$uploadedFiles['tmp_name']];

        foreach ($tmpNames as $tmpName) {
            if (empty($tmpName)) {
                continue;
            }

            $finfo    = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($tmpName);

            if (strpos($mimeType, 'image/') !== 0) {
                $invalidFile = true;
                break;
            }
        }
    }

    if ($invalidFile) {
        setEventMessages($langs->trans('ErrorFileNotAnImage'), null, 'errors');
    } else {
        // An edited media is sent back under its own name and must replace the original
        $allowOverwrite = GETPOSTINT('overwrite') ? 1 : 0;

        if (dol_add_file_process($mediaBlockDir, $allowOverwrite, 1, 'userfile', '', null, '', 1) > 0) {
            $uploadedNames = is_array($uploadedFiles['name']) ? $uploadedFiles['name'] : [$uploadedFiles['name']];

            foreach ($uploadedNames as $uploadedName) {
                $uploadedPath = $mediaBlockDir . '/' . dol_sanitizeFileName($uploadedName);

                if (!dol_is_file($uploadedPath)) {
                    continue;
                }

                foreach (['mini', 'small', 'medium', 'large'] as $mediaBlockSize) {
                    $confWidth  = 'SATURNE_MEDIA_MAX_WIDTH_' . dol_strtoupper($mediaBlockSize);
                    $confHeight = 'SATURNE_MEDIA_MAX_HEIGHT_' . dol_strtoupper($mediaBlockSize);

                    saturne_vignette($uploadedPath, getDolGlobalInt($confWidth), getDolGlobalInt($confHeight), '_' . $mediaBlockSize);
                }
            }
        }
    }
}

// Remove a photo through the media block, thumbs included
if (($action == 'deletePhoto' || $action == 'deleteFile') && !empty($mediaBlockDir) && !empty($mediaBlockSubDir)) {
    $mediaBlockFileName = dol_sanitizeFileName(GETPOST('filename', 'alphanohtml'));

    if (!empty($mediaBlockFileName)) {
        $mediaBlockFilePath = $mediaBlockDir . '/' . $mediaBlockFileName;

        if (dol_is_file($mediaBlockFilePath)) {
            dol_delete_file($mediaBlockFilePath);

            foreach (['mini', 'small', 'medium', 'large'] as $mediaBlockSize) {
                $mediaBlockThumb = $mediaBlockDir . '/thumbs/' . saturne_get_thumb_name($mediaBlockFileName, $mediaBlockSize);

                if (dol_is_file($mediaBlockThumb)) {
                    dol_delete_file($mediaBlockThumb);
                }
            }
        }
    }
}
