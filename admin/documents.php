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

if ($action == 'update_documents_config') {
    $vignette = GETPOST('vignette', 'alpha');
    $result   = dolibarr_set_const($db, strtoupper($moduleName) . '_DOCUMENT_MEDIA_VIGNETTE_USED', $vignette, 'chaine', 0, '', $conf->entity);

    if ($result > 0) {
        setEventMessage($langs->trans('SavedConfig'));
    } else {
        setEventMessage($langs->trans('ErrorSavedConfig'), 'errors');
    }
}

if ($action == 'specimen') {

    $modele = GETPOST('module', 'alpha');
    $documentType = preg_split('/_/', $modele)[1];

    require_once __DIR__ . '/../../' . $moduleNameLowerCase . '/class/' . $moduleNameLowerCase . 'documents/' . $documentType . '.class.php';

    $objectDocument = new $documentType($db);
    $objectDocument->initAsSpecimen();

    // Search template files
    $dir = __DIR__ . "/../../". $moduleNameLowerCase . "/core/modules/" . $moduleNameLowerCase . "/" . $moduleNameLowerCase . "documents/" . $documentType . '/';
    $file = 'pdf_' .  $modele . ".modules.php";
    if (file_exists($dir . $file)) {
        $classname = 'pdf_' . $modele;
        require_once $dir . $file;

        $obj = new $classname($db);

        $modulePart = str_replace('document', '', $documentType);

        if ($obj->write_file($objectDocument, $langs, ['object' => $objectDocument]) > 0) {
            header("Location: " . DOL_URL_ROOT . "/document.php?modulepart=". $modulePart ."&file=SPECIMEN.pdf");
            return;
        } else {
            setEventMessages($obj->error, $obj->errors, 'errors');
            dol_syslog($obj->error, LOG_ERR);
        }
    } else {
        setEventMessages($langs->trans("ErrorModuleNotFound"), null, 'errors');
        dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
    }
}

/*
 * View
 */

$title    = $langs->trans('ModuleSetup', $moduleName);
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

print load_fiche_titre($title, $selectorAnchor, 'title_setup');

// Configuration header
$preHead = $moduleNameLowerCase . '_admin_prepare_head';
$head    = $preHead();
print dol_get_fiche_head($head, 'documents', $title, -1, $moduleNameLowerCase . '_color@' . $moduleNameLowerCase);

print load_fiche_titre($langs->trans('Configs', $langs->trans('DocumentsMin')), '', '');

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?module_name=' . $moduleName . '" name="documents_form">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update_documents_config">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td class="center">' . $langs->trans('Status') . '</td>';
print '<td class="center">' . $langs->trans('Action') . '</td>';
print '</tr>';

// Automatic PDF generation
print '<tr class="oddeven"><td>';
print $langs->trans('AutomaticPdfGeneration');
print '</td><td>';
print $langs->trans('AutomaticPdfGenerationDescription');
print '</td>';
print '<td class="center">';
print ajax_constantonoff(strtoupper($moduleName) . '_AUTOMATIC_PDF_GENERATION');
print '</td></td><td></tr>';

// Manual PDF generation
print '<tr class="oddeven"><td>';
print $langs->trans('ManualPdfGeneration');
print '</td><td>';
print $langs->trans('ManualPdfGenerationDescription');
print '</td>';
print '<td class="center">';
print ajax_constantonoff(strtoupper($moduleName) . '_MANUAL_PDF_GENERATION');
print '</td></td><td></tr>';

// Show signature specimen
print '<tr class="oddeven"><td>';
print $langs->trans('ShowSignatureSpecimen');
print '</td><td>';
print $langs->trans('ShowSignatureSpecimenDescription');
print '</td>';
print '<td class="center">';
print ajax_constantonoff(strtoupper($moduleName) . '_SHOW_SIGNATURE_SPECIMEN');
print '</td></td><td></tr>';

$vignetteType = ['mini' => 'Mini', 'small' => 'Small', 'medium' => 'Medium', 'large' => 'Large'];
$vignetteConf = strtoupper($moduleName) . '_DOCUMENT_MEDIA_VIGNETTE_USED';
print '<tr class="oddeven"><td>';
print $langs->trans('MediaSizeDocument');
print '</td><td>';
print $langs->trans('MediaSizeDocumentDescription');
print '<td class="center">';
print $form::selectarray('vignette', $vignetteType, (!empty($conf->global->$vignetteConf) ? $conf->global->$vignetteConf : 'small'), 0, 0, 0, '', 1);
print '</td><td class="center">';
print '<input type="submit" class="button" name="save" value="' . $langs->trans('Save') . '">';
print '</td></tr>';

$reshook = $hookmanager->executeHooks('saturneAdminAdditionalConfig', $parameters); // Note that $action and $object may have been modified by some hooks
if (empty($reshook)) {
    $additionalConfig = $hookmanager->resArray;
}
if (is_array($additionalConfig) && !empty($additionalConfig)) {
    foreach($additionalConfig as $configName => $configCode) {
        print '<tr class="oddeven"><td>';
        print $langs->trans($configName);
        print '</td><td>';
        print $langs->trans($configName . 'Description');
        print '</td>';
        print '<td class="center">';
        print ajax_constantonoff($configCode);
        print '</td></td><td></tr>';
    }
}

print '</form>';
print '</table>';

foreach ($types as $type => $documentData) {
    $filelist = [];
    if (preg_match('/_/', $documentData['documentType'])) {
        $documentType       = preg_split('/_/', $documentData['documentType']);
        $documentParentType = $documentType[0];
        $documentType       = $documentType[1];
    } else {
        $documentParentType = ($documentData['className'] ?? $documentData['documentType']);
        $documentType       = $documentData['documentType'];
    }

    require_once __DIR__ . '/../../' . $moduleNameLowerCase . '/class/' . $moduleNameLowerCase . 'documents/' . ($documentData['className'] ?? $documentData['documentType']) . '.class.php';

    $object = new $type($db);

    print load_fiche_titre($langs->trans($type), '', $documentData['picto'], 0, $langs->trans($type));

    $documentPath = true;

    require __DIR__ . '/../core/tpl/admin/object/object_const_view.tpl.php';

    require __DIR__ . '/../core/tpl/admin/object/object_numbering_module_view.tpl.php';

    require __DIR__ . '/../core/tpl/admin/object/object_document_model_view.tpl.php';
}

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
