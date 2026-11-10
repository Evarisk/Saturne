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
 * \file    lib/documents.lib.php
 * \ingroup saturne
 * \brief   Library files with common functions for Saturne Documents
 */

/**
 * Return a string to show the box with list of available documents for object.
 *
 * The files are listed newest first. When the Saturne JS is loaded, the box gets a search field, filters by file type,
 * sortable columns and only shows its first rows until the user asks for more : without it, the box stays a plain list
 *
 * @param  string            $modulepart       Module the files are related to ('mymodule', 'mymodule:nameofsubmodule', 'mymodule_temp')
 * @param  string|array      $modulesubdir     Existing (so sanitized) sub-directory to scan (Example: '0/1/10', 'FA/DD/MM/YY/9999'). Use '' if file is not into subdir of module.
 * @param  string|array      $filedir          Directory to scan
 * @param  string            $urlsource        Url of origin page (for return)
 * @param  int|string[]      $genallowed       Generation is allowed (1/0 or array list of templates)
 * @param  int               $delallowed       Remove is allowed (1/0)
 * @param  string            $modelselected    Model to preselect by default
 * @param  int               $allowgenifempty  Allow generation even if list of template ($genallowed) is empty (show however a warning)
 * @param  int               $forcenomultilang Do not show language option (even if MAIN_MULTILANGS defined)
 * @param  int               $notused          Not used
 * @param  int               $noform           Do not output html form tags
 * @param  string            $param            More param on http links
 * @param  string            $title            Title to show on top of form. Example: '' (Default to "Documents") or 'none'
 * @param  string            $buttonlabel      Label on submit button
 * @param  string            $codelang         Default language code to use on lang combo box if multilang is enabled
 * @param  string            $morepicto        Add more HTML content into cell with picto
 * @param  CommonObject|null $object           Object when method is called from an object card.
 * @param  int               $hideifempty      Hide section of generated files if there is no file
 * @param  string            $removeaction     (optional) The action to remove a file
 * @param  int               $active           (optional) To show gen button disabled
 * @param  string            $tooltiptext      (optional) Tooltip text when gen button disabled
 * @return string|int                          Output string with HTML array of documents (might be empty string), or -1 if error
 */
function saturne_show_documents(string $modulepart, $modulesubdir, $filedir, string $urlsource, $genallowed, int $delallowed = 0, string $modelselected = '', int $allowgenifempty = 1, int $forcenomultilang = 0, int $notused = 0, int $noform = 0, string $param = '', string $title = '', string $buttonlabel = '', string $codelang = '', string $morepicto = '', $object = null, int $hideifempty = 0, string $removeaction = 'remove_file', int $active = 1, string $tooltiptext = '')
{
    global $conf, $db, $form, $hookmanager, $langs;

    if (!is_object($form)) {
        $form = new Form($db);
    }

    include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

    // The box is also printed by hooks on pages of Dolibarr, where the Saturne translations are not loaded
    $langs->load('saturne@saturne');

    // Add entity in $param if not already exists
    if (! preg_match('/entity=[0-9]+/', $param)) {
        $param .= ($param ? '&' : '') . 'entity=' . (!empty($object->entity) ? $object->entity : $conf->entity);
    }

    $hookmanager->initHooks(['formfile']);

    // Get list of files
    $fileList = [];
    if (!empty($filedir)) {
        if (is_array($filedir)) {
            foreach ($filedir as $fileDirSingle) {
                $fileList = array_merge($fileList, dol_dir_list($fileDirSingle, 'files', 0, '(\.jpg|\.jpeg|\.png|\.odt|\.zip|\.pdf)', '', 'date', SORT_DESC, 1));
            }
            // Each directory is listed newest first: once merged, the whole list is sorted again
            $fileList = dol_sort_array($fileList, 'date', 'desc');
        } else {
            $fileList = dol_dir_list($filedir, 'files', 0, '(\.jpg|\.jpeg|\.png|\.odt|\.zip|\.pdf)', '', 'date', SORT_DESC, 1);
        }
    }

    if ($hideifempty && empty($fileList)) {
        return '';
    }

    // modulepart = 'nameofmodule' or 'nameofmodule:NameOfObject'. The download links need the module alone, with or
    // without the generation allowed
    $submodulepart = $modulepart;
    $tmp           = explode(':', $modulepart);
    if (!empty($tmp[1])) {
        $modulepart    = $tmp[0];
        $submodulepart = $tmp[1];
    }
    $manualPdfGeneration = getDolGlobalInt(dol_strtoupper($modulepart) . '_MANUAL_PDF_GENERATION') > 0;

    $linkList = [];
    if (!empty($filedir) && is_object($object) && $object->id > 0) {
        require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';
        $link      = new Link($db);
        $sortfield = $sortorder = null;
        $link->fetchAll($linkList, $object->element, $object->id, $sortfield, $sortorder);
    }

    $nbFiles = count($fileList) + count($linkList);
    if (!$genallowed && (empty($filedir) || ($nbFiles == 0 && !preg_match('/^massfilesarea/', $modulepart)))) {
        return "\n" . '<!-- Start saturne_show_documents -->' . "\n" . '<!-- End show_document -->' . "\n";
    }

    // Number of files of each type, for the filters of the box
    $fileTypes = [];
    foreach ($fileList as $file) {
        $fileType             = saturne_get_document_file_type($file['name']);
        $fileTypes[$fileType] = ($fileTypes[$fileType] ?? 0) + 1;
    }
    if (!empty($linkList)) {
        $fileTypes['link'] = count($linkList);
    }
    $typeOrder = ['odt', 'pdf', 'zip', 'image', 'link'];
    uksort($fileTypes, function ($first, $second) use ($typeOrder) {
        $firstPosition  = array_search($first, $typeOrder);
        $secondPosition = array_search($second, $typeOrder);
        return ($firstPosition === false ? 99 : $firstPosition) <=> ($secondPosition === false ? 99 : $secondPosition);
    });

    // Columns: name, size, date, PDF generation, actions, then one per hook completing the lines
    $addcolumforpicto = ($delallowed || $morepicto);
    $hookColumns      = 0;
    if (!empty($hookmanager->hooks['formfile'])) {
        foreach ($hookmanager->hooks['formfile'] as $moduleHook) {
            if (method_exists($moduleHook, 'formBuilddocLineOptions')) {
                $hookColumns++;
            }
        }
    }
    $nbColumns = 3 + ($manualPdfGeneration ? 1 : 0) + ($addcolumforpicto ? 1 : 0) + $hookColumns;

    $out     = '';
    $forname = 'builddoc';

    $out .= "\n" . '<!-- Start saturne_show_documents -->' . "\n";

    $titletoshow = $langs->trans('Documents');
    if (!empty($title)) {
        $titletoshow = ($title == 'none' ? '' : $title);
    }
    if (dol_strlen($titletoshow) && $nbFiles > 0) {
        $titletoshow .= '<span class="badge marginleftonlyshort saturne-documents__count">' . $nbFiles . '</span>';
    }

    $toolbar = '';
    if ($genallowed) {
        $toolbar .= saturne_show_documents_generation($modulepart, $submodulepart, $param, $forname, $modelselected, $allowgenifempty, $object, $active, $tooltiptext);
        if ($toolbar === '-1') {
            return -1;
        }
    }

    // Search and type filters: hidden until the Saturne JS that runs them shows them
    if ($nbFiles > 1) {
        $toolbar .= '<div class="saturne-documents__filters" hidden>';
        $toolbar .= '<label class="saturne-documents__search">';
        $toolbar .= '<i class="fas fa-search" aria-hidden="true"></i>';
        $toolbar .= '<input type="search" class="saturne-documents__search-input" placeholder="' . dol_escape_htmltag($langs->trans('DocumentsSearchPlaceholder')) . '" aria-label="' . dol_escape_htmltag($langs->trans('DocumentsSearchPlaceholder')) . '" autocomplete="off">';
        $toolbar .= '</label>';
        if (count($fileTypes) > 1) {
            $toolbar .= '<div class="saturne-documents__types" role="group" aria-label="' . dol_escape_htmltag($langs->trans('DocumentsFilterByType')) . '">';
            $toolbar .= '<button type="button" class="saturne-documents__type saturne-documents__type--active" data-type="" aria-pressed="true">' . $langs->trans('All') . '<span class="saturne-documents__type-count">' . $nbFiles . '</span></button>';
            foreach ($fileTypes as $fileType => $nbFilesOfType) {
                $toolbar .= '<button type="button" class="saturne-documents__type" data-type="' . dol_escape_htmltag($fileType) . '" aria-pressed="false">' . saturne_get_document_file_type_label($fileType) . '<span class="saturne-documents__type-count">' . $nbFilesOfType . '</span></button>';
            }
            $toolbar .= '</div>';
        }
        $toolbar .= '</div>';
    }

    $out .= '<div class="saturne-documents" data-sort-storage="' . dol_escape_htmltag($modulepart . ':' . $submodulepart) . '" data-page-size="10">';

    if ($genallowed) {
        if ($conf->browser->layout == 'phone') {
            // So we switch to form after a generation
            $urlsource .= '#' . $forname . '_form';
        }
        if (empty($noform)) {
            $out .= '<form action="' . $urlsource . (empty($conf->global->MAIN_JUMP_TAG) ? '' : '#builddoc') . '" id="' . $forname . '_form" method="post">';
        }
        $out .= '<input type="hidden" name="action" value="builddoc">';
        $out .= '<input type="hidden" name="token" value="' . newToken() . '">';
    }

    $out .= load_fiche_titre($titletoshow, '', '', 0, 'builddoc');
    $out .= '<div class="div-table-responsive-no-min">';
    $out .= '<table class="liste formdoc noborder centpercent saturne-documents__table"' . ($genallowed ? '' : ' id="' . $modulepart . '_table"') . '>' . "\n";

    if (dol_strlen($toolbar)) {
        $out .= '<tr class="liste_titre saturne-documents__toolbar-row">';
        $out .= '<th colspan="' . $nbColumns . '" class="formdoc liste_titre saturne-documents__toolbar-cell">';
        $out .= '<div class="saturne-documents__toolbar">' . $toolbar . '</div>';
        $out .= '</th>';
        $out .= '</tr>';
    }

    // Execute hooks
    if ($genallowed && is_object($hookmanager)) {
        $parameters = ['colspan' => $nbColumns, 'socid' => ($GLOBALS['socid'] ?? ''), 'id' => ($GLOBALS['id'] ?? ''), 'modulepart' => $modulepart];
        $hookObject = $GLOBALS['object'] ?? $object;
        $hookmanager->executeHooks('formBuilddocOptions', $parameters, $hookObject);
        $out .= $hookmanager->resPrint;
    }

    $out .= '<!-- documents.lib::saturne_show_documents -->' . "\n";

    // Column titles, sorted by the Saturne JS
    if ($nbFiles > 0) {
        $out .= '<tr class="liste_titre saturne-documents__head">';
        $out .= '<th class="saturne-documents__col-name" data-sort-key="name">' . $langs->trans('Name') . '</th>';
        $out .= '<th class="right nowraponall hideonsmartphone" data-sort-key="size">' . $langs->trans('Size') . '</th>';
        $out .= '<th class="right nowraponall" data-sort-key="date">' . $langs->trans('Date') . '</th>';
        if ($manualPdfGeneration) {
            $out .= '<th></th>';
        }
        if ($addcolumforpicto) {
            $out .= '<th></th>';
        }
        $out .= str_repeat('<th></th>', $hookColumns);
        $out .= '</tr>' . "\n";
    }

    $documenturl = DOL_URL_ROOT . '/document.php';
    if (isset($conf->global->DOL_URL_ROOT_DOCUMENT_PHP)) {
        $documenturl = $conf->global->DOL_URL_ROOT_DOCUMENT_PHP; // To use another wrapper
    }
    $tmpurlsource = preg_replace('/#[a-zA-Z0-9_]*$/', '', $urlsource);

    // Loop on each file found
    foreach ($fileList as $file) {
        // Define relative path for download link (depends on module)
        $relativepath = $file['name']; // Cas general
        if (is_array($modulesubdir)) {
            foreach ($modulesubdir as $moduleSubDirSingle) {
                if (strstr($file['path'], $moduleSubDirSingle)) {
                    $relativepath = $moduleSubDirSingle . '/' . $file['name'];
                }
            }
        } elseif ($modulesubdir) {
            $relativepath = $modulesubdir . '/' . $file['name'];
        }

        $size = (!empty($file['size']) ? $file['size'] : dol_filesize($file['fullname']));
        $date = (!empty($file['date']) ? $file['date'] : dol_filemtime($file['fullname']));

        $out .= '<tr class="oddeven saturne-documents__row" data-name="' . dol_escape_htmltag($file['name']) . '" data-size="' . ((int) $size) . '" data-date="' . ((int) $date) . '" data-type="' . dol_escape_htmltag(saturne_get_document_file_type($file['name'])) . '">';

        // Show file name with link to download
        $out .= '<td class="minwidth200 saturne-documents__name">';
        $out .= '<a class="documentdownload paddingright" href="' . $documenturl . '?modulepart=' . $modulepart . '&amp;file=' . urlencode($relativepath) . ($param ? '&' . $param : '') . '"';

        $mime = dol_mimetype($relativepath, '', 0);
        if (preg_match('/text/', $mime)) {
            $out .= ' target="_blank"';
        }
        $out .= '>';
        $out .= img_mime($file['name'], $langs->trans('File') . ': ' . $file['name']);
        $out .= dol_trunc($file['name'], 150);
        $out .= '</a>' . "\n";

        // Preview
        if (!empty($conf->use_javascript_ajax) && ($conf->browser->layout != 'phone')) {
            $tmparray = getAdvancedPreviewUrl($modulepart, $relativepath, 1, '&entity=' . $conf->entity);
            if ($tmparray && $tmparray['url']) {
                $out .= '<a href="' . $tmparray['url'] . '"' . ($tmparray['css'] ? ' class="' . $tmparray['css'] . '"' : '') . ($tmparray['mime'] ? ' mime="' . $tmparray['mime'] . '"' : '') . ($tmparray['target'] ? ' target="' . $tmparray['target'] . '"' : '') . '>';
                $out .= '<i class="fa fa-search-plus paddingright saturne-documents__preview"></i>';
                $out .= '</a>';
            }
        }
        $out .= '</td>';

        $out .= '<td class="nowrap right hideonsmartphone saturne-documents__size">' . dol_print_size($size, 1, 1) . '</td>';
        $out .= '<td class="nowrap right saturne-documents__date">' . dol_print_date($date, 'dayhour', 'tzuser') . '</td>';

        // Show pdf generation icon
        if ($manualPdfGeneration) {
            $out .= '<td class="right nowraponall">';
            if (pathinfo($file['name'], PATHINFO_EXTENSION) == 'odt') {
                $out .= '<a class="pdf-generation" href="' . $tmpurlsource . ((strpos($tmpurlsource, '?') === false) ? '?' : '&amp;') . 'action=pdfGeneration&amp;file=' . urlencode($relativepath) . '&token=' . newToken();
                $out .= ($param ? '&amp;' . $param : '');
                $out .= '">' . img_picto($langs->trans('PDFGeneration'), 'fontawesome_fa-file-pdf_fas_red') . '</a>';
                $out .= ' ' . $form->textwithpicto('', $langs->trans('PDFGenerationTooltip'));
            }
            $out .= '</td>';
        }

        if ($addcolumforpicto) {
            $out .= '<td class="right nowraponall">';
            if ($delallowed) {
                $out .= '<a href="' . $tmpurlsource . ((strpos($tmpurlsource, '?') === false) ? '?' : '&amp;') . 'action=' . $removeaction . '&amp;file=' . urlencode($relativepath) . '&token=' . newToken();
                $out .= ($param ? '&amp;' . $param : '');
                $out .= '">' . img_picto($langs->trans('Delete'), 'delete') . '</a>';
            }
            if ($morepicto) {
                $out .= preg_replace('/__FILENAMEURLENCODED__/', urlencode($relativepath), $morepicto);
            }
            $out .= '</td>';
        }

        $res = 0;
        if (is_object($hookmanager)) {
            $parameters = ['colspan' => $nbColumns, 'socid' => ($GLOBALS['socid'] ?? ''), 'id' => ($GLOBALS['id'] ?? ''), 'modulepart' => $modulepart, 'relativepath' => $relativepath];
            $res        = $hookmanager->executeHooks('formBuilddocLineOptions', $parameters, $file);
        }
        if (empty($res)) {
            // Complete line
            $out .= (is_object($hookmanager) ? $hookmanager->resPrint : '');
            $out .= '</tr>' . "\n";
        } else {
            // Replace all $out
            $out = $hookmanager->resPrint;
        }
    }

    // Loop on each link found
    foreach ($linkList as $linkFile) {
        $out .= '<tr class="oddeven saturne-documents__row" data-name="' . dol_escape_htmltag($linkFile->label) . '" data-size="0" data-date="' . ((int) $linkFile->datea) . '" data-type="link">';
        $out .= '<td class="minwidth200 saturne-documents__name">';
        $out .= '<a data-ajax="false" href="' . dol_escape_htmltag($linkFile->url) . '" target="_blank" rel="noopener noreferrer">';
        $out .= '<i class="fas fa-link paddingright saturne-documents__link-icon"></i>' . dol_escape_htmltag($linkFile->label);
        $out .= '</a>';
        $out .= '</td>';
        $out .= '<td class="hideonsmartphone"></td>';
        $out .= '<td class="nowrap right saturne-documents__date">' . dol_print_date($linkFile->datea, 'dayhour', 'tzuser') . '</td>';
        $out .= str_repeat('<td></td>', $nbColumns - 3);
        $out .= '</tr>' . "\n";
    }

    if ($nbFiles == 0) {
        $out .= '<tr><td colspan="' . $nbColumns . '" class="opacitymedium saturne-documents__none"><i class="far fa-folder-open paddingright"></i>' . $langs->trans('DocumentsNone') . '</td></tr>' . "\n";
    } elseif ($nbFiles > 1) {
        $out .= '<tr class="saturne-documents__no-match" hidden><td colspan="' . $nbColumns . '" class="opacitymedium center">' . $langs->trans('DocumentsNoMatch') . '</td></tr>' . "\n";
        $out .= '<tr class="saturne-documents__more" hidden><td colspan="' . $nbColumns . '" class="center">';
        $out .= '<button type="button" class="saturne-documents__more-button" data-label-more="' . dol_escape_htmltag($langs->trans('DocumentsShowMore', '%s')) . '" data-label-less="' . dol_escape_htmltag($langs->trans('DocumentsShowLess')) . '"></button>';
        $out .= '</td></tr>' . "\n";
    }

    $out .= "</table>\n";
    $out .= "</div>\n";
    if ($genallowed && empty($noform)) {
        $out .= '</form>' . "\n";
    }
    $out .= '</div>' . "\n";
    $out .= '<!-- End show_document -->' . "\n";

    return $out;
}

/**
 * Return the model selector and the generation button of the documents box
 *
 * @param  string            $modulepart      Module the files are related to
 * @param  string            $submodulepart   Document type of the module, $modulepart if it has none
 * @param  string            $param           More param on http links (a specimen box lists the specimen models)
 * @param  string            $forname         Name of the generation form
 * @param  string            $modelselected   Model to preselect by default
 * @param  int               $allowgenifempty Show a warning when no model is activated
 * @param  CommonObject|null $object          Object when method is called from an object card
 * @param  int               $active          0 to show the generation button disabled
 * @param  string            $tooltiptext     Tooltip text when the generation button is disabled
 * @return string                             HTML of the generation tools, '-1' if the module has a bad document class
 */
function saturne_show_documents_generation(string $modulepart, string $submodulepart, string $param, string $forname, string $modelselected, int $allowgenifempty, $object, int $active, string $tooltiptext): string
{
    global $conf, $db, $form, $langs;

    $modellist = [];

    // For normalized external modules.
    $file = dol_buildpath('/' . $modulepart . '/core/modules/' . $modulepart . '/' . $modulepart . 'documents/' . strtolower($submodulepart) . '/modules_' . strtolower($submodulepart) . '.php');
    if (file_exists($file)) {
        include_once $file;
        $class = 'ModeleODT' . $submodulepart;

        if (class_exists($class)) {
            if (preg_match('/specimen/', $param)) {
                $type      = strtolower($class) . 'specimen';
                include_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
                $modellist = getListOfModels($db, $type);
            } else {
                $modellist = call_user_func($class . '::liste_modeles', $db, 100);
            }
        } else {
            dol_print_error($db, "Bad value for modulepart '" . $modulepart . "' in saturne_show_documents");
            return '-1';
        }
    } else {
        if (preg_match('/specimen/', $param)) {
            $type      = 'SaturneDocumentModelspecimen';
            include_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
            $modellist = getListOfModels($db, $type);
        } else {
            require_once __DIR__ . '/../core/modules/saturne/modules_saturne.php';
            $saturneDocumentModel = new SaturneDocumentModel($db, $modulepart, $submodulepart);
            $documentType         = strtolower($submodulepart);
            $modellist            = $saturneDocumentModel->liste_modeles($db, $documentType, 100);
        }
    }

    $out = '<div class="saturne-documents__generate">';

    if (empty($modellist)) {
        $out .= '<span class="saturne-documents__generate-label">' . $langs->trans('Files') . '</span>';
    } else {
        asort($modellist);
        $modellist  = array_filter($modellist, 'saturne_remove_index');
        $modellists = [];
        $confName   = dol_strtoupper($modulepart . '_' . $submodulepart) . '_DEFAULT_MODEL';
        foreach ($modellist as $key => $modellistsingle) {
            $arrayvalues         = preg_replace('/template_/', '', $modellistsingle);
            $newKey              = (is_object($object) ? str_replace($object->element . 'document_custom_odt', $object->element . 'document_odt', $key) : $key);
            $modellists[$newKey] = $langs->trans($arrayvalues);
            if (dol_strlen(getDolGlobalString($confName)) > 0 && strpos($key, getDolGlobalString($confName)) !== false) {
                $modelselected = $newKey;
            }
        }

        $out .= '<i class="fas fa-file-word saturne-documents__model-icon hideonsmartphone" aria-hidden="true"></i>';
        $out .= $form::selectarray('model', $modellists, $modelselected, 0, 0, 0, '', 0, 0, 0, '', ($conf->browser->layout == 'phone' ? 'maxwidth100' : 'maxwidth200'));
        if ($conf->use_javascript_ajax) {
            $out .= ajax_combobox('model');
        }

        if (empty($modellists)) {
            if ($allowgenifempty) {
                $langs->load('errors');
                $out .= img_warning($langs->transnoentitiesnoconv('WarningNoDocumentModelActivated'));
            }
        } elseif ($active) {
            $out .= '<button class="wpeo-button button-square-40 button-blue wpeo-tooltip-event" id="' . $forname . '_generatebutton" name="' . $forname . '_generatebutton" type="submit" aria-label="' . dol_escape_htmltag($langs->trans('Generate')) . '"><i class="fas fa-print button-icon"></i></button>';
        } else {
            $out .= '<i class="fas fa-exclamation-triangle pictowarning wpeo-tooltip-event" aria-label="' . dol_escape_htmltag($langs->trans($tooltiptext)) . '"></i>';
            $out .= '<button type="button" class="wpeo-button button-square-40 button-disable" name="' . $forname . '_generatebutton"><i class="fas fa-print button-icon"></i></button>';
        }
    }

    if (!$active) {
        $out .= $form->textwithpicto($langs->trans('Help'), $tooltiptext, 1, 'info');
    }

    $out .= '</div>';

    return $out;
}

/**
 * Return the type a file is filtered by in the documents box : its extension, the images grouped together
 *
 * @param  string $fileName File name
 * @return string           'odt', 'pdf', 'zip', 'image'...
 */
function saturne_get_document_file_type(string $fileName): string
{
    $extension = dol_strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    return (in_array($extension, ['jpg', 'jpeg', 'png']) ? 'image' : $extension);
}

/**
 * Return the label of a file type of the documents box
 *
 * @param  string $fileType Type returned by saturne_get_document_file_type(), or 'link'
 * @return string           Label
 */
function saturne_get_document_file_type_label(string $fileType): string
{
    global $langs;

    if ($fileType == 'image') {
        return $langs->trans('DocumentsTypeImage');
    }
    if ($fileType == 'link') {
        return $langs->trans('Links');
    }

    return dol_escape_htmltag(dol_strtoupper($fileType));
}

/**
 * Exclude index.php files from list of models for document generation
 *
 * @param  string $model Model name
 * @return string        '' or $model
 */
function saturne_remove_index(string $model): string
{
    if (preg_match('/index.php/', $model)) {
        return '';
    } else {
        return $model;
    }
}

/**
 * Make the file name of a generated document safe for the ODT to PDF conversion
 *
 * Odf::exportAsAttachedPDF() quotes the file name with escapeshellarg(), then passes the whole
 * command through escapeshellcmd(), which escapes some characters even between quotes. LibreOffice
 * then receives a path that does not exist, prints "source file could not be loaded" and still
 * exits with code 0. dol_sanitizeFileName() removes the quote ($includequotes = 1, #4860) and the
 * tilde; the other characters escapeshellcmd() escapes are replaced here, with the separators around
 * them, so "Head_office_&_lab" gives "Head_office_lab". Parentheses are protected by the core
 *
 * @param  string $fileName File name, without its directory
 * @return string           File name the conversion can read
 */
function saturne_sanitize_document_file_name(string $fileName): string
{
    $fileName = dol_sanitizeFileName($fileName, '_', 1, 1);

    return preg_replace('/_*(?:[&#^\[\]{}]_*)+/', '_', $fileName);
}

/**
 * Resolve a directory of document templates read from a *_ADDON_ODT_PATH constant
 *
 * The constants are written at the module activation, mostly as DOL_DOCUMENT_ROOT/custom/<module>/...
 * A module directory missing at that place is looked up in every module root, so the templates are
 * still found when the module lives in an alternative root
 *
 * @param  string $dir Directory as written in the constant
 * @return string      Absolute directory, '' if $dir is empty
 */
function saturne_resolve_template_dir(string $dir): string
{
    $dir = trim($dir);
    if (!dol_strlen($dir)) {
        return '';
    }

    $resolvedDir = str_replace(['DOL_DATA_ROOT', 'DOL_DOCUMENT_ROOT'], [DOL_DATA_ROOT, DOL_DOCUMENT_ROOT], $dir);
    if (!is_dir($resolvedDir) && preg_match('#^DOL_DOCUMENT_ROOT/[^/]+(/.+)$#', $dir, $matches)) {
        $moduleDir = dol_buildpath($matches[1]);
        if (is_dir($moduleDir)) {
            $resolvedDir = $moduleDir;
        }
    }

    return $resolvedDir;
}

/**
 * Return list of activated modules usable for document generation
 *
 * @param  DoliDB     $db                Database handler
 * @param  string     $type              Type of models (object->type)
 * @param  int        $maxfilenamelength Max length of value to show
 * @return array|int                     0 if no module is activated, or array(key=>label). For modules that need directory scan, key is completed with ":filename".
 * @throws Exception
 */
function saturne_get_list_of_models(DoliDB $db, string $type, int $maxfilenamelength = 0)
{
    global $conf, $langs;
    $liste = [];
    $found = 0;
    $dirtoscan = '';

    $sql = 'SELECT nom as id, nom as doc_template_name, libelle as label, description as description';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'document_model';
    $sql .= " WHERE type = '" . $db->escape($type) . "'";
    $sql .= ' AND entity IN (0,' . $conf->entity . ')';
    $sql .= ' ORDER BY description DESC';

    dol_syslog('/saturne/lib/documents.lib.php::saturne_get_list_of_models', LOG_DEBUG);
    $resql = $db->query($sql);
    if ($resql) {
        $num = $db->num_rows($resql);
        $i = 0;
        while ($i < $num) {
            $found = 1;

            $obj = $db->fetch_object($resql);

            // If this generation module needs to scan a directory, then description field is filled
            // with the constant that contains list of directories to scan (COMPANY_ADDON_PDF_ODT_PATH, ...).
            if (!empty($obj->description)) {    // A list of directories to scan is defined
                include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

                $const = $obj->description;
                //irtoscan.=($dirtoscan?',':'').preg_replace('/[\r\n]+/',',',trim($conf->global->$const));
                $dirtoscan = preg_replace('/[\r\n]+/', ',', trim($conf->global->$const));

                $listoffiles = [];

                // Now we add models found in directories scanned
                $listofdir = explode(',', $dirtoscan);
                foreach ($listofdir as $key => $tmpdir) {
                    $tmpdir = saturne_resolve_template_dir($tmpdir);
                    if (!$tmpdir) {
                        unset($listofdir[$key]);
                        continue;
                    }
                    if (is_dir($tmpdir)) {
                        // all type of template is allowed
                        $tmpfiles = dol_dir_list($tmpdir, 'files', 0, '', '');
                        if (count($tmpfiles)) {
                            $listoffiles = array_merge($listoffiles, $tmpfiles);
                        }
                    }
                }

                if (count($listoffiles)) {
                    foreach ($listoffiles as $record) {
                        $max = ($maxfilenamelength ?: 50);
                        $liste[$obj->id . ':' . $record['fullname']] = dol_trunc($record['name'], $max, 'middle');
                    }
                } else {
                    $liste[0] = $obj->label . ': ' . $langs->trans('None');
                }
            } else {
                // Common usage
                $liste[$obj->id] = $obj->label ?: $obj->doc_template_name;
            }
            $i++;
        }
    } else {
        dol_print_error($db);
        return -1;
    }

    if ($found) {
        return $liste;
    } else {
        return 0;
    }
}

/**
 * Return the document model set as default for a document type, ready to be passed to generateDocument()
 *
 * The default model constant only holds a model name : the ODT template path it must be built with is
 * known by the model list only. A custom ODT model shares the generation class of the standard one, so
 * once its template path is resolved its name is normalized back to the standard one
 *
 * @param  DoliDB $db           Database handler
 * @param  string $moduleName   Module name
 * @param  string $documentType Document type, the object element suffixed by 'document'
 * @return string               Model to generate the document with, '' if no model is available
 * @throws Exception
 */
function saturne_get_default_model(DoliDB $db, string $moduleName, string $documentType): string
{
    $documentType = dol_strtolower($documentType);
    $confName     = dol_strtoupper($moduleName . '_' . $documentType) . '_DEFAULT_MODEL';
    $defaultModel = getDolGlobalString($confName);

    $modelList = saturne_get_list_of_models($db, $documentType);
    if (!is_array($modelList) || empty($modelList)) {
        return '';
    }

    asort($modelList);
    $modelList = array_filter($modelList, 'saturne_remove_index');

    $model = '';
    if (dol_strlen($defaultModel) > 0) {
        foreach ($modelList as $key => $label) {
            // An ODT model key carries its template path after the model name, a native PDF model key does not :
            // comparing the name alone keeps a PDF model from matching the ODT models built on the same document
            $modelName = explode(':', (string) $key)[0];
            if ($modelName == $defaultModel) {
                $model = (string) $key;
            }
        }
    }
    if (!dol_strlen($model)) {
        $model = (string) key($modelList);
    }

    return str_replace($documentType . '_custom_odt', $documentType . '_odt', $model);
}
