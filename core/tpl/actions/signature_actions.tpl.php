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
 * \file    core/tpl/actions/signature_actions.tpl.php
 * \ingroup saturne
 * \brief   Template page for signature actions
 */

/**
 * The following vars must be defined:
 * Global     : $conf, $langs, $user
 * Parameters : $action, $documentType, $moduleName, $moduleNameLowerCase, $objectType, $trackID
 * Objects    : $document, $object, $signatory
 * Variable   : $upload_dir
 */

// Action to add signature
if ($action == 'add_signature') {
    $data = json_decode(file_get_contents('php://input'), true);

    $signatory->signature      = $data['signature'];
    $signatory->signature_date = dol_now();

    $result = $signatory->update($user, true);
    // Creation signature OK
    if ($result > 0) {
        $signatory->setSigned($user, false, 'public');
        // Creation signature KO
    } elseif (!empty($signatory->errors)) {
        setEventMessages('', $signatory->errors, 'errors');
    } else {
        setEventMessages($signatory->error, [], 'errors');
    }
}

// Action to build doc
if ($action == 'builddoc') {
    $outputLangs = $langs;
    $newLang = '';

    if ($conf->global->MAIN_MULTILANGS && empty($newLang) && GETPOST('lang_id', 'aZ09')) {
        $newLang = GETPOST('lang_id', 'aZ09');
    }
    if (!empty($newLang)) {
        $outputLangs = new Translate('', $conf);
        $outputLangs->setDefaultLang($newLang);
    }

    // To be sure vars is defined
    if (empty($hideDetails)) {
        $hideDetails = 0;
    }
    if (empty($hideDesc)) {
        $hideDesc = 0;
    }
    if (empty($hideRef)) {
        $hideRef = 0;
    }
    if (empty($moreParams)) {
        $moreParams = [];
    }

    // The default model constant only holds a model name : resolve it against the installed models so the
    // template of a custom ODT model is honoured here as it already is on the object card
    $model = saturne_get_default_model($db, $moduleName, $documentType);

    // An ODT model carries its template path after the model name, a native PDF model does not
    $isNativePdf = !preg_match('/_odt:/i', $model);

    // Determine if it should be a specimen or a final document
    $isSpecimen = 0;

    $moreParams['object']     = $object;
    $moreParams['user']       = $user;
    $moreParams['specimen']   = $isSpecimen;
    $moreParams['zone']       = 'public';
    $moreParams['objectType'] = $objectType;

    $subDir    = $isSpecimen ? '/public_specimen/' : '/';
    $sourceDir = $upload_dir . '/' . strtolower($objectType) . 'document/' . $object->ref . $subDir;
    $files = dol_dir_list($sourceDir, 'files', 1, '\.' . ($canServePdf ? 'pdf' : 'odt') . '$', null, 'date', SORT_DESC);
    
    $shouldGenerate = true;
    if (!empty($files)) {
        $shouldGenerate = false;
        $document->last_main_doc = $files[0]['name'];
        if (isset($signatory->signature_date) && !empty($signatory->signature_date)) {
            $filemtime = filemtime($sourceDir . $files[0]['name']);
            if ($filemtime < $signatory->signature_date) {
                $shouldGenerate = true;
            }
        }
    }

    $result = 1;
    if ($shouldGenerate) {
        $result = $document->generateDocument($model, $outputLangs, $hideDetails, $hideDesc, $hideRef, $moreParams);
    }

    if ($result > 0) {
        $subDir    = $isSpecimen ? '/public_specimen/' : '/';
        $sourceDir = $upload_dir . '/' . strtolower($objectType) . 'document/' . $object->ref . $subDir;
        $tempDir   = DOL_DOCUMENT_ROOT . '/custom/' . $moduleNameLowerCase . '/documents/temp/';
        
        if (!is_dir($tempDir)) {
            dol_mkdir($tempDir);
        }
        
        $originalName = basename($document->last_main_doc);
        $tempFileName = $isSpecimen ? 'specimen_' . $originalName : $originalName;

        if ($isNativePdf) {
            // Native PDF: the generated file is already a PDF
            dol_copy($sourceDir . $originalName, $tempDir . $tempFileName);
        } else {
            // ODT model: copy the ODT file
            dol_copy($sourceDir . $originalName, $tempDir . $tempFileName);

            // If automatic PDF conversion is enabled, also copy the PDF version
            $confAutoPdf = strtoupper($moduleName) . '_AUTOMATIC_PDF_GENERATION';
            if (!empty($conf->global->MAIN_ODT_AS_PDF) && getDolGlobalInt($confAutoPdf) > 0) {
                $pdfSource = preg_replace('/\.odt$/', '.pdf', $originalName);
                $pdfTempName = preg_replace('/\.odt$/', '.pdf', $tempFileName);
                if (file_exists($sourceDir . $pdfSource)) {
                    dol_copy($sourceDir . $pdfSource, $tempDir . $pdfTempName);
                }
            }
        }
    } else {
        setEventMessages($document->error, $document->errors, 'errors');
    }
}

// Action to remove all temp files
if ($action == 'remove_file') {
    // get all file names
    $files = dol_dir_list(DOL_DOCUMENT_ROOT . '/custom/' . $moduleNameLowerCase . '/documents/temp/');
    foreach ($files as $file) {
        if (is_file($file['fullname'])) {
            dol_delete_file($file['fullname']);
        }
    }
}
