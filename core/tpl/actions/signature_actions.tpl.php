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

    // Determine if the default model is a native PDF or an ODT template.
    $confDefaultModel = strtoupper($moduleName) . '_' . strtoupper($documentType) . '_DEFAULT_MODEL';
    $defaultModel     = getDolGlobalString($confDefaultModel, '');
    $isNativePdf      = (!empty($defaultModel) && !preg_match('/_odt$/i', $defaultModel));

    if ($isNativePdf) {
        // Native PDF model: use the model name directly (e.g. "preventionplandocument")
        $model = $defaultModel;
    } else {
        // ODT model: build the model string from the template path
        $confName = strtoupper($moduleName) . '_' . strtoupper($documentType) . '_ADDON_ODT_PATH';
        $template = preg_replace('/DOL_DOCUMENT_ROOT/', DOL_DOCUMENT_ROOT, $conf->global->$confName);
        $model    = strtolower($documentType) . '_odt:' . $template . 'template_' . strtolower($documentType) . '.odt';
    }

    $moreParams['object']     = $object;
    $moreParams['user']       = $user;
    $moreParams['specimen']   = 1;
    $moreParams['zone']       = 'public';
    $moreParams['objectType'] = $objectType;

    $result = $document->generateDocument($model, $outputLangs, $hideDetails, $hideDesc, $hideRef, $moreParams);

    if ($result > 0) {
        $sourceDir = $upload_dir . '/' . strtolower($objectType) . 'document/' . $object->ref . '/public_specimen/';
        $tempDir   = DOL_DOCUMENT_ROOT . '/custom/' . $moduleNameLowerCase . '/documents/temp/';
        $baseName  = $objectType . '_specimen_' . $trackID;

        if ($isNativePdf) {
            // Native PDF: the generated file is already a PDF
            dol_copy($sourceDir . $document->last_main_doc, $tempDir . $baseName . '.pdf');
        } else {
            // ODT model: copy the ODT file
            dol_copy($sourceDir . $document->last_main_doc, $tempDir . $baseName . '.odt');

            // If automatic PDF conversion is enabled, also copy the PDF version
            $confAutoPdf = strtoupper($moduleName) . '_AUTOMATIC_PDF_GENERATION';
            if (!empty($conf->global->MAIN_ODT_AS_PDF) && getDolGlobalInt($confAutoPdf) > 0) {
                $pdfSource = preg_replace('/\.odt$/', '.pdf', $document->last_main_doc);
                if (file_exists($sourceDir . $pdfSource)) {
                    dol_copy($sourceDir . $pdfSource, $tempDir . $baseName . '.pdf');
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
