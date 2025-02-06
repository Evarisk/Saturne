<?php
/* Copyright (C) 2022-2023 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/documents/documents_action.tpl.php
 * \ingroup saturne
 * \brief   Template page for documents action.
 */

/**
 * The following vars must be defined:
 * Global     : $conf, $db, $hookmanager, $langs, $user,
 * Parameters : $action,
 * Objects    : $object, $document
 * Variable   : $permissiontoadd, $permissiontodelete, $shouldRedirect (optional)
 */

// Build doc action.
if (($action == 'builddoc' || GETPOST('forcebuilddoc')) && $permissiontoadd) {
    global $hookmanager;

    $outputLangs = $langs;
    $newLang = '';

    if ($conf->global->MAIN_MULTILANGS && empty($newLang) && GETPOST('lang_id', 'aZ09')) {
        $newLang = GETPOST('lang_id', 'aZ09');
    }
    if (!empty($newLang)) {
        $outputLangs = new Translate('', $conf);
        $outputLangs->setDefaultLang($newLang);
    }

    // To be sure vars is defined.
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

    if (GETPOST('forcebuilddoc')) {
        $model      = '';
        $modelLists = saturne_get_list_of_models($db, $object->element . 'document');
        if (is_array($modelLists) && !empty($modelLists)) {
            asort($modelLists);
            $modelLists = array_filter($modelLists, 'saturne_remove_index');
            foreach ($modelLists as $key => $modelList) {
                $confName = dol_strtoupper($object->module . '_' . $document->element) . '_DEFAULT_MODEL';
                if (dol_strlen(getDolGlobalString($confName)) > 0 && strpos($key, getDolGlobalString($confName)) !== false) {
                    $model = $key;
                }
            }
            if (!dol_strlen($model)) {
                $model = key($modelLists);
            }
        }
    } else {
        $model = GETPOST('model', 'alpha');
    }
    $model = str_replace($object->element . 'document_custom_odt', $object->element . 'document_odt', $model);

    $moreParams['object']   = $object;
    $moreParams['user']     = $user;
    $moreParams['zone']     = 'private';
    $constName              = get_class($object) . '::STATUS_LOCKED';
    $moreParams['specimen'] = defined($constName) && $object->status < $object::STATUS_LOCKED;

    if (!empty($model)) {
        $parameters = ['model' => $model, 'outputlangs' => $outputLangs, 'hidedetails' => $hideDetails, 'hidedesc' => $hideDesc, 'hideref' => $hideRef, 'moreparams' => $moreParams];
        $hookmanager->executeHooks('saturneBuildDoc', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

        $result = $document->generateDocument($model, $outputLangs, $hideDetails, $hideDesc, $hideRef, $moreParams);
        if ($result <= 0) {
            setEventMessages($document->error, $document->errors, 'errors');
            $action = '';
        } else {
            $documentType = explode('_odt', $model);
            if ($document->element != $documentType[0]) {
                $document->element = $documentType[0];
            }
            setEventMessages($langs->trans('FileGenerated') . ' - ' . '<a href=' . DOL_URL_ROOT . '/document.php?modulepart=' . (!empty($moreParams['modulePart']) ? $moreParams['modulePart'] : $object->module) . '&file=' . urlencode((empty($moreParams['modulePart']) ? $document->element . '/' : '') . (dol_strlen($object->ref) > 0 ? $object->ref . '/' : '') . $document->last_main_doc) . '&entity=' . $conf->entity . '"' . '>' . $document->last_main_doc . '</a>', []);
            $urlToRedirect = $_SERVER['REQUEST_URI'];
            $urlToRedirect = preg_replace('/#builddoc$/', '', $urlToRedirect);
            $urlToRedirect = preg_replace('/action=builddoc&?/', '', $urlToRedirect); // To avoid infinite loop.
            $urlToRedirect = preg_replace('/forcebuilddoc=1&?/', '', $urlToRedirect); // To avoid infinite loop.
            if (!isset($shouldRedirect) || $shouldRedirect) {
                header('Location: ' . $urlToRedirect);
                exit;
            }
        }
    }
}

// Remove file action.
if ($action == 'remove_file' && $permissiontodelete) {
    if (!empty($upload_dir)) {
        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

        $fileToDelete = GETPOST('file', 'alpha');
        $file         = $upload_dir . '/' . $fileToDelete;
        $result       = dol_delete_file($file, 0, 0, 0, $object);
        if ($result > 0) {
            setEventMessages($langs->trans('FileWasRemoved', $fileToDelete), []);
        } else {
            setEventMessages($langs->trans('ErrorFailToDeleteFile', $fileToDelete), [], 'errors');
        }

        // Make a redirect to avoid to keep the remove_file into the url that create side effects.
        $urlToRedirect = $_SERVER['REQUEST_URI'];
        $urlToRedirect = preg_replace('/#builddoc$/', '', $urlToRedirect);
        $urlToRedirect = preg_replace('/action=remove_file&?/', '', $urlToRedirect);

        header('Location: ' . $urlToRedirect);
        exit;
    } else {
        setEventMessages('BugFoundVarUploaddirnotDefined', [], 'errors');
    }
}
