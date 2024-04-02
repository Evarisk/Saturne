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
    if ($result > 0) {
        // Creation signature OK
        $signatory->setSigned($user, false, 'public');
    } elseif (!empty($signatory->errors)) { // Creation signature KO
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
    if (empty($hideDetails)){
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

    $confName = strtoupper($moduleName) . '_' . strtoupper($documentType) . '_ADDON_ODT_PATH';
    $template = preg_replace('/DOL_DOCUMENT_ROOT/', DOL_DOCUMENT_ROOT, $conf->global->$confName);
    $model    = strtolower($documentType) . '_odt:' . $template .'template_' . strtolower($documentType) . '.odt';

    $moreParams['object']     = $object;
    $moreParams['user']       = $user;
    $moreParams['specimen']   = 1;
    $moreParams['zone']       = 'public';
    $moreParams['objectType'] = $objectType;

    $result = $document->generateDocument($model, $outputLangs, $hideDetails, $hideDesc, $hideRef, $moreParams);

    if ($result > 0) {
        dol_copy($upload_dir . '/' . strtolower($objectType) . 'document' . '/' . $object->ref . '/public_specimen/' . $document->last_main_doc, DOL_DOCUMENT_ROOT . '/custom/' . $moduleNameLowerCase . '/documents/temp/' . $objectType . '_specimen_' . $trackID . '.odt');
    } else {
        setEventMessages($document->error, $document->errors, 'errors');
    }
}

// Action to remove all temp files
if ($action == 'remove_file') {
    $files = dol_dir_list(DOL_DOCUMENT_ROOT . '/custom/' . $moduleNameLowerCase . '/documents/temp/'); // get all file names
    foreach ($files as $file) {
        if (is_file($file['fullname'])) {
            dol_delete_file($file['fullname']);
        }
    }
}
