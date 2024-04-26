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
 * Variable   : $permissiontoadd, $moduleNameLowerCase, $permissiontodelete
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
        $model     = '';
        $modelList = saturne_get_list_of_models($db, $object->element . 'document');
        if (!empty($modelList)) {
            asort($modelList);
            $modelList = array_filter($modelList, 'saturne_remove_index');
            if (is_array($modelList)) {
                $models = array_keys($modelList);
            }
        }
    } else {
        $model = GETPOST('model', 'alpha');
    }

    $moreParams['object']   = $object;
    $moreParams['user']     = $user;
    $moreParams['zone']     = 'private';
    $constName              = get_class($object) . '::STATUS_LOCKED';
    $moreParams['specimen'] = defined($constName) && $object->status < $object::STATUS_LOCKED;

    if (!empty($models) || !empty($model)) {
        $parameters = ['models' => $models, 'model' => $model, 'outputlangs' => $outputLangs, 'hidedetails' => $hideDetails, 'hidedesc' => $hideDesc, 'hideref' => $hideRef, 'moreparams' => $moreParams];
        $hookmanager->executeHooks('saturneBuildDoc', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

        $result = $document->generateDocument((!empty($models) ? $models[0] : $model), $outputLangs, $hideDetails, $hideDesc, $hideRef, $moreParams);
        if ($result <= 0) {
            setEventMessages($document->error, $document->errors, 'errors');
            $action = '';
        } else {
            $documentType = explode('_odt', (!empty($models) ? $models[0] : $model));
            if ($document->element != $documentType[0]) {
                $document->element = $documentType[0];
            }
            setEventMessages($langs->trans('FileGenerated') . ' - ' . '<a href=' . DOL_URL_ROOT . '/document.php?modulepart='. $moduleNameLowerCase . '&file=' . urlencode($document->element . '/' . (dol_strlen($object->ref) > 0 ? $object->ref . '/' : '') . $document->last_main_doc) . '&entity=' . $conf->entity . '"' . '>' . $document->last_main_doc . '</a>', []);
            $urlToRedirect = $_SERVER['REQUEST_URI'];
            $urlToRedirect = preg_replace('/#builddoc$/', '', $urlToRedirect);
            $urlToRedirect = preg_replace('/action=builddoc&?/', '', $urlToRedirect); // To avoid infinite loop.
            $urlToRedirect = preg_replace('/forcebuilddoc=1&?/', '', $urlToRedirect); // To avoid infinite loop.
            header('Location: ' . $urlToRedirect);
            exit;
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

if ($action == 'generate_csv') {
    $dashboards = $dashboard->load_dashboard($moreParams);

    if (!empty($dashboards)) {
        $now   = dol_now();
        $value = GETPOST('value');

        $titleName = str_replace(' ', '_', $value);
        $titleName = dol_sanitizeFileName(dol_strtolower($titleName));
        $fileName  = dol_print_date($now, 'dayxcard') . '_' . $titleName . '.csv';

        $data    = findArrayByTitle($dashboards, $value);
        $labels  = $data['labels'];
        $dataset = $data['data'];

        $mode = 0; // Two-dimension graph
        $line = 1;
        $fp   = fopen($upload_dir . '/graphstat/' . $fileName, 'w');

        // Empty line and title
        fputcsv($fp, []);
        fputcsv($fp, [$line => '', 2 => $value]);

        $header = [$line => ''];
        if (is_array($dataset[0]) && !empty($dataset[0])) {
            $mode = 1; // Three-dimension graph
            foreach ($dataset as $labelArray) {
                $line++;
                $header[$line] = $labelArray[0];
            }
        } else if (is_array($labels) && !empty($labels)) {
            foreach ($labels as $labelArray) {
                $line++;
                $header[$line] = $labelArray['label'];
            }
        }

        fputcsv($fp, $header);
        $line       = 1;
        $labelIndex = 1;

        if ($mode == 1 && !empty($dataset) && !empty($labels)) {
            foreach ($labels as $labelArray) {
                $line           = 1;
                $content[$line] = $labelArray['label'];
                foreach ($dataset as $value) {
                    $line++;
                    if (!empty($value['y_combined_' . $labelArray['label']])) {
                        $content[$line] = $value['y_combined_' . $labelArray['label']];
                    } else if (!empty($value[$labelIndex])) {
                        $content[$line] = $value[$labelIndex];
                    } else {
                        $content[$line] = 0;
                    }
                }
                fputcsv($fp, $content);
                $labelIndex++;
            }
        } else if (!empty($dataset)) {
            $content = [$line => ''];
            foreach ($dataset as $value) {
                $line++;
                $content[$line] = $value;
            }
            fputcsv($fp, $content);
        }

        fputcsv($fp, []);
        fclose($fp);

        setEventMessages($langs->trans('SuccessGenerateCSV', $fileName), []);
    } else {
        setEventMessages($langs->trans('ErrorMissingData'), [], 'errors');
    }
    $action = '';
}
