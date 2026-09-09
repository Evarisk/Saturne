<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    admin/entity_transfer.php
 * \ingroup saturne
 * \brief   Saturne entity transfer admin page
 *
 * Export the data of one entity, import a dump built by another install. The engine
 * lives in lib/entity_transfer.lib.php and is shared with scripts/export_entity.php
 * and scripts/import_entity.php.
 */

// Load Saturne environment
if (file_exists('../saturne.main.inc.php')) {
    require_once __DIR__ . '/../saturne.main.inc.php';
} elseif (file_exists('../../saturne.main.inc.php')) {
    require_once __DIR__ . '/../../saturne.main.inc.php';
} else {
    die('Include of saturne main fails');
}

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

// Load Saturne libraries
require_once __DIR__ . '/../lib/saturne.lib.php';
require_once __DIR__ . '/../lib/entity_transfer.lib.php';

// Global variables definitions
global $conf, $db, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['admin']);

// Get parameters
$action = GETPOST('action', 'aZ09');

// Security check
// An export is a full dump of the entity, users and their password hashes included, so it
// is reserved to administrators, and the archives are stored outside the document directories
// of the modules: everything under them is downloadable by anyone holding a module read right
$permissiontoread     = $user->rights->saturne->adminpage->read;
$permissiontotransfer = $user->admin;

saturne_check_access($permissiontoread);

$entityExportDir = $conf->admin->dir_output . '/saturne_entity_export';

// Only a super administrator may look at an entity other than the one he is connected to
$canChooseEntity = (isModEnabled('multicompany') && !empty($user->admin) && empty($user->entity) && $conf->entity == 1);

/*
 * Actions
 */

if ($action == 'exportEntity' && $permissiontotransfer) {
    $exportScope    = GETPOST('exportScope', 'aZ09');
    $sourceEntity   = ($canChooseEntity ? GETPOSTINT('exportEntity') : $conf->entity);
    $sourceEntities = [$sourceEntity > 0 ? $sourceEntity : $conf->entity];

    if ($canChooseEntity) {
        foreach (explode(',', GETPOST('extraEntities', 'alphanohtml')) as $extraEntity) {
            $extraEntity = (int) trim($extraEntity);
            if ($extraEntity > 0 && !in_array($extraEntity, $sourceEntities, true)) {
                $sourceEntities[] = $extraEntity;
            }
        }
    }

    $availableModules = saturne_entity_transfer_modules($db);
    $selectedModules  = array_values(array_intersect($availableModules, (array) GETPOST('exportModules', 'array')));

    $exportName = 'entity_' . $sourceEntities[0] . '_' . dol_print_date(dol_now(), '%Y%m%d%H%M%S');

    $export = saturne_entity_transfer_export($db, [
        'entities'          => $sourceEntities,
        'target_entity'     => 1,
        'scope'             => (in_array($exportScope, ['module', 'core', 'full'], true) ? $exportScope : 'module'),
        'modules'           => $selectedModules,
        'with_dictionaries' => (GETPOST('withDictionaries', 'alpha') ? 1 : 0),
        'with_files'        => (GETPOST('withFiles', 'alpha') ? 1 : 0),
        'purge'             => (GETPOST('withPurge', 'alpha') ? 1 : 0),
        'output_dir'        => $entityExportDir . '/' . $exportName
    ]);

    if (!empty($export['errors'])) {
        setEventMessages('', $export['errors'], 'errors');
    }

    if (!empty($export['sql_path'])) {
        // The archive is what the administrator downloads, the working directory is only an intermediate
        $zipResult = dol_compress_dir($export['dir'], $entityExportDir . '/' . $exportName . '.zip', 'zip');
        if ($zipResult > 0) {
            dol_delete_dir_recursive($export['dir']);
            setEventMessages($langs->trans('EntityExportDone', $export['rows'], count(array_filter($export['counts']))), []);
        } else {
            setEventMessages($langs->trans('EntityExportArchiveFailed', $export['dir']), [], 'warnings');
        }

        if (!empty($export['skipped'])) {
            setEventMessages($langs->trans('EntityExportSkippedTables', implode(', ', array_keys($export['skipped']))), [], 'warnings');
        }
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if ($action == 'downloadEntityExport' && $permissiontotransfer) {
    $exportFile = dol_sanitizeFileName(GETPOST('exportFile', 'alphanohtml'));
    $exportPath = $entityExportDir . '/' . $exportFile;

    // dol_sanitizeFileName() drops the directory separators, and the file must sit in the export directory
    if (!empty($exportFile) && is_file($exportPath)) {
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $exportFile . '"');
        header('Content-Length: ' . dol_filesize($exportPath));
        header('Cache-Control: private, must-revalidate');
        readfile($exportPath);
        exit;
    }

    setEventMessages($langs->trans('EntityExportArchiveNotFound', $exportFile), [], 'errors');

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if ($action == 'deleteEntityExport' && $permissiontotransfer) {
    $exportFile = dol_sanitizeFileName(GETPOST('exportFile', 'alphanohtml'));

    if (!empty($exportFile) && is_file($entityExportDir . '/' . $exportFile)) {
        dol_delete_file($entityExportDir . '/' . $exportFile);
        setEventMessages($langs->trans('EntityExportArchiveDeleted', $exportFile), []);
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if ($action == 'importEntity' && $permissiontotransfer && getDolGlobalInt('MAIN_UPLOAD_DOC')) {
    $importDir = $conf->admin->dir_temp . '/saturne_entity_import_' . dol_print_date(dol_now(), '%Y%m%d%H%M%S');
    $errors    = [];

    if (empty($_FILES['entityImportFile']['tmp_name'][0])) {
        $errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('File'));
    } elseif (!saturne_entity_transfer_mkdir($importDir)) {
        $errors[] = $langs->trans('ErrorFailedToCreateDir', $importDir);
    } elseif (dol_add_file_process($importDir, 0, 0, 'entityImportFile', '', null, '', 0, null) < 0) {
        $errors[] = $langs->trans('ErrorFileNotUploaded');
    }

    $sqlFile  = '';
    $manifest = [];

    if (empty($errors)) {
        $uploadedFiles = dol_dir_list($importDir, 'files', 0);
        $uploadedFile  = (!empty($uploadedFiles) ? $uploadedFiles[0]['fullname'] : '');

        if (preg_match('/\.zip$/i', $uploadedFile)) {
            $uncompressed = dol_uncompress($uploadedFile, $importDir);
            if (!empty($uncompressed['error'])) {
                $errors[] = $uncompressed['error'];
            }
        }

        // The dump may sit at the root of the archive or one level below, depending on how it was zipped
        $manifestFiles = dol_dir_list($importDir, 'files', 1, 'manifest\.json$');
        if (!empty($manifestFiles)) {
            $manifest = json_decode(file_get_contents($manifestFiles[0]['fullname']), true);
            if (is_array($manifest) && !empty($manifest['sql_file'])) {
                $sqlFile = dirname($manifestFiles[0]['fullname']) . '/' . $manifest['sql_file'];
            }
        }

        if (empty($sqlFile)) {
            $sqlFiles = dol_dir_list($importDir, 'files', 1, '\.sql$');
            $sqlFile  = (!empty($sqlFiles) ? $sqlFiles[0]['fullname'] : '');
            $manifest = [];
        }

        if (empty($sqlFile) || !is_file($sqlFile)) {
            $errors[] = $langs->trans('EntityImportNoDumpFound');
        }
    }

    // A module of the dump left disabled here has none of its tables, and every statement
    // touching them fails one by one: say so before writing anything
    if (empty($errors)) {
        $missingModules = [];
        foreach ((array) ($manifest['modules'] ?? []) as $module) {
            if (!isModEnabled($module)) {
                $missingModules[] = $module;
            }
        }

        if (!empty($missingModules)) {
            $errors[] = $langs->trans('EntityImportMissingModules', implode(', ', $missingModules));
        }
    }

    if (empty($errors)) {
        $documentsDir = dirname($sqlFile) . '/documents';

        $import = saturne_entity_transfer_import($db, $sqlFile, [
            'manifest'      => (is_array($manifest) ? $manifest : []),
            'purge'         => (GETPOST('importPurge', 'alpha') ? 1 : 0),
            'documents_dir' => (GETPOST('importWithFiles', 'alpha') && is_dir($documentsDir) ? $documentsDir : ''),
            'target_entity' => $conf->entity
        ]);

        if ($import['errors'] > 0) {
            setEventMessages($langs->trans('EntityImportFinishedWithErrors', $import['executed'], $import['errors']), array_slice($import['messages'], 0, 10), 'errors');
        } else {
            setEventMessages($langs->trans('EntityImportDone', $import['executed'], $import['documents']), []);
        }
    } else {
        setEventMessages('', $errors, 'errors');
    }

    dol_delete_dir_recursive($importDir);

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

/*
 * View
 */

$title    = $langs->trans('ModuleSetup', 'Saturne');
$help_url = 'FR:Module_Saturne#Configuration';

saturne_header(0, '', $title, $help_url);

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">' . $langs->trans('BackToModuleList') . '</a>';

print load_fiche_titre($title, $linkback, 'title_setup');

// Configuration header
$head = saturne_admin_prepare_head();
print dol_get_fiche_head($head, 'entitytransfer', $title, -1, 'saturne_color@saturne');

// Mode 1 is required, otherwise dol_dir_list() does not fill the size of the files
$entityExports    = ($permissiontotransfer ? dol_dir_list($entityExportDir, 'files', 0, '\.zip$', '', 'date', SORT_DESC, 1) : []);
$availableModules = ($permissiontotransfer ? saturne_entity_transfer_modules($db) : []);
$entityList       = [];

// The entity list is only useful to a super administrator, the others stay on their own entity
if ($canChooseEntity) {
    $resql = $db->query('SELECT rowid, label FROM ' . MAIN_DB_PREFIX . 'entity ORDER BY rowid');
    if ($resql) {
        while ($entityRow = $db->fetch_object($resql)) {
            $entityList[(int) $entityRow->rowid] = $entityRow->label;
        }
        $db->free($resql);
    }
}

require_once __DIR__ . '/../core/tpl/admin/entity_transfer_view.tpl.php';

print dol_get_fiche_end();

llxFooter();
$db->close();
