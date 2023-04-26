<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
 * \file    admin/documents.php
 * \ingroup saturne
 * \brief   Saturne documents page.
 */

// Load Saturne environment.
if (file_exists('../saturne.main.inc.php')) {
    require_once __DIR__ . '/../saturne.main.inc.php';
} elseif (file_exists('../../saturne.main.inc.php')) {
    require_once __DIR__ . '/../../saturne.main.inc.php';
} else {
    die('Include of saturne main fails');
}

// Get module parameters.
$moduleName          = GETPOST('module_name', 'alpha');
$moduleNameLowerCase = strtolower($moduleName);

// Load Dolibarr libraries.
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

// Load Module libraries.
require_once __DIR__ . '/../../' . $moduleNameLowerCase . '/lib/' . $moduleNameLowerCase . '.lib.php';

// Global variables definitions.
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page.
saturne_load_langs(['admin']);

// Initialize view objects.
$form = new Form($db);

// Get parameters.
$action     = GETPOST('action', 'alpha');
$value      = GETPOST('value', 'alpha');
$type       = GETPOST('type', 'alpha');
$const      = GETPOST('const', 'alpha');
$label      = GETPOST('label', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09'); // Used by actions_setmoduleoptions.inc.php.

$hookmanager->initHooks([$moduleNameLowerCase . 'admindocuments']); // Note that conf->hooks_modules contains array.

// Security check - Protection if external user.
$permissiontoread = $user->rights->$moduleNameLowerCase->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * Actions
 */

// Activate a model.
if ($action == 'set') {
    addDocumentModel($value, $type, $label, $const);
    header('Location: ' . $_SERVER['PHP_SELF'] . '?module_name=' . $moduleName);
} elseif ($action == 'del') {
    delDocumentModel($value, $type);
    header('Location: ' . $_SERVER['PHP_SELF'] . '?module_name=' . $moduleName);
}

// Set default model.
if ($action == 'setdoc') {
    $constforval = strtoupper($moduleName) . '_' . strtoupper($type) . '_DEFAULT_MODEL';
    $label       = '';

    if (dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity)) {
        $conf->global->$constforval = $value;
    }

    // Active model.
    $ret = delDocumentModel($value, $type);

    if ($ret > 0) {
        $ret = addDocumentModel($value, $type, $label);
    }
} elseif ($action == 'setmod') {
    $constforval = strtoupper($moduleName) . '_' . strtoupper($type) . '_ADDON';
    dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity);
}

if ($action == 'deletefile' && $modulepart == 'ecm' && !empty($user->admin)) {
    include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
    $keyforuploaddir = GETPOST('keyforuploaddir', 'aZ09');

    $listofdir = explode(',', preg_replace('/[\r\n]+/', ',', trim(getDolGlobalString($keyforuploaddir))));
    foreach ($listofdir as $key => $tmpdir) {
        $tmpdir = preg_replace('/DOL_DATA_ROOT\/*/', '', $tmpdir);	// Clean string if we found a hardcoded DOL_DATA_ROOT
        if (!$tmpdir) {
            unset($listofdir[$key]);
            continue;
        }
        $tmpdir = DOL_DATA_ROOT.'/'.$tmpdir;	// Complete with DOL_DATA_ROOT. Only files into DOL_DATA_ROOT can be reach/set
        if (!is_dir($tmpdir)) {
            if (empty($nomessageinsetmoduleoptions)) {
                setEventMessages($langs->trans('ErrorDirNotFound', $tmpdir), null, 'warnings');
            }
        } else {
            $upload_dir = $tmpdir;
            break;	// So we take the first directory found into setup $conf->global->$keyforuploaddir
        }
    }

    $filetodelete = $tmpdir.'/'.GETPOST('file');
    $result = dol_delete_file($filetodelete);
    if ($result > 0) {
        setEventMessages($langs->trans('FileWasRemoved', GETPOST('file')), null);
        header('Location: ' . $_SERVER['PHP_SELF']);
    }
}

if ($action == 'setModuleOptions') {
    $error = 0;
    include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
    $keyforuploaddir = GETPOST('keyforuploaddir', 'aZ09');

    $listofdir = explode(',', preg_replace('/[\r\n]+/', ',', trim(getDolGlobalString($keyforuploaddir))));
    foreach ($listofdir as $key => $tmpdir) {
        $tmpdir = preg_replace('/DOL_DATA_ROOT\/*/', '', $tmpdir);	// Clean string if we found a hardcoded DOL_DATA_ROOT
        if (!$tmpdir) {
            unset($listofdir[$key]);
            continue;
        }
        $tmpdir = DOL_DATA_ROOT.'/'.$tmpdir;	// Complete with DOL_DATA_ROOT. Only files into DOL_DATA_ROOT can be reach/set
        if (!is_dir($tmpdir)) {
            if (empty($nomessageinsetmoduleoptions)) {
                setEventMessages($langs->trans('ErrorDirNotFound', $tmpdir), null, 'warnings');
            }
        } else {
            $upload_dir = $tmpdir;
            break;	// So we take the first directory found into setup $conf->global->$keyforuploaddir
        }
    }

    if (!empty($_FILES)) {
        if (is_array($_FILES['userfile']['tmp_name'])) {
            $userfiles = $_FILES['userfile']['tmp_name'];
        } else {
            $userfiles = array($_FILES['userfile']['tmp_name']);
        }

        foreach ($userfiles as $key => $userfile) {
            if (empty($_FILES['userfile']['tmp_name'][$key])) {
                $error++;
                if ($_FILES['userfile']['error'][$key] == 1 || $_FILES['userfile']['error'][$key] == 2) {
                    setEventMessages($langs->trans('ErrorFileSizeTooLarge'), null, 'errors');
                } else {
                    setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('File')), null, 'errors');
                }
            }
            if (preg_match('/__.*__/', $_FILES['userfile']['name'][$key])) {
                $error++;
                setEventMessages($langs->trans('ErrorWrongFileName'), null, 'errors');
            }
        }

        if (!$error) {
            $allowoverwrite = (GETPOST('overwritefile', 'int') ? 1 : 0);
            if (!empty($tmpdir)) {
                $result = dol_add_file_process($tmpdir, $allowoverwrite, 1, 'userfile', GETPOST('savingdocmask', 'alpha'));
            }
        }
    }

}

/*
 * View
 */

$title    = $langs->trans('YourDocuments');
$help_url = 'FR:Module_' . $moduleName;

saturne_header(0, '', $title, $help_url);

$parameters = [];
$reshook    = $hookmanager->executeHooks('SaturneAdminDocumentData', $parameters); // Note that $action and $object may have been modified by some hooks
if (empty($reshook)) {
    $types = $hookmanager->resArray;
}

// Subheader
$selectorAnchor = '<select onchange="location = this.value;">';
foreach ($types as $type => $documentType) {
    $selectorAnchor .= '<option value="#' . $langs->trans($type) . '">' . $langs->trans($type) . '</option>';
}
$selectorAnchor .= '</select>';

print load_fiche_titre($title, $selectorAnchor, $moduleNameLowerCase . '_color.png@' . $moduleNameLowerCase);

// Configuration header
$head = dolisirh_admin_prepare_head();
print dol_get_fiche_head($head, 'documents', $title, -1, $moduleNameLowerCase . '_color@' . $moduleNameLowerCase);

print load_fiche_titre($langs->trans('Configs', $langs->trans('DocumentsMin')), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td class="center">' . $langs->trans('Status') . '</td>';
print '</tr>';

// Automatic PDF generation
print '<tr class="oddeven"><td>';
print  $langs->trans('AutomaticPdfGeneration');
print '</td><td>';
print $langs->trans('AutomaticPdfGenerationDescription');
print '</td>';
print '<td class="center">';
print ajax_constantonoff(strtoupper($moduleName) . '_AUTOMATIC_PDF_GENERATION');
print '</td>';
print '</tr>';

// Manual PDF generation
print '<tr class="oddeven"><td>';
print  $langs->trans('ManualPdfGeneration');
print '</td><td>';
print $langs->trans('ManualPdfGenerationDescription');
print '</td>';
print '<td class="center">';
print ajax_constantonoff(strtoupper($moduleName) . '_MANUAL_PDF_GENERATION');
print '</td>';
print '</tr>';

// Show signature specimen
print '<tr class="oddeven"><td>';
print  $langs->trans('ShowSignatureSpecimen');
print '</td><td>';
print $langs->trans('ShowSignatureSpecimenDescription');
print '</td>';
print '<td class="center">';
print ajax_constantonoff(strtoupper($moduleName) . '_SHOW_SIGNATURE_SPECIMEN');
print '</td>';
print '</tr>';

print '</table>';

foreach ($types as $type => $documentData) {
    $filelist = [];
    if (preg_match('/_/', $documentData['documentType'])) {
        $documentType       = preg_split('/_/', $documentData['documentType']);
        $documentParentType = $documentType[0];
        $documentType       = $documentType[1];
    } else {
        $documentParentType = $documentData['documentType'];
    }

    require_once __DIR__ . '/../../' . $moduleNameLowerCase . '/class/' . $moduleNameLowerCase . 'documents/' . $documentData['documentType'] . '.class.php';

    $object = new $type($db);

    print load_fiche_titre($langs->trans($type), '', $documentData['picto'], 0, $langs->trans($type));

    $documentPath = true;

    require __DIR__ . '/../core/tpl/admin/object/object_numbering_module_view.tpl.php';

    require __DIR__ . '/../core/tpl/admin/object/object_document_model_view.tpl.php';
}

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();