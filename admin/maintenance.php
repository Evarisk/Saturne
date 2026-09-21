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
 * \file    admin/maintenance.php
 * \ingroup saturne
 * \brief   Saturne maintenance page
 *
 * The operations live in lib/maintenance.lib.php and are shared with the CLI scripts, so an
 * installation without shell access is not left out.
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

// Load Saturne libraries
require_once __DIR__ . '/../lib/saturne.lib.php';
require_once __DIR__ . '/../lib/maintenance.lib.php';

// Global variables definitions
global $conf, $db, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['admin', 'other']);

// Get parameters
$action = GETPOST('action', 'aZ09');

// Security check
// The operations delete rows across every entity, so they are reserved to administrators, like
// the entity transfer
$permissiontoread   = $user->rights->saturne->adminpage->read;
$permissiontoclean  = $user->admin;

saturne_check_access($permissiontoread);

/*
 * Actions
 */

if ($action == 'cleanOrphanDocuments' && $permissiontoclean) {
    $scope = saturne_maintenance_scope($user, $conf);

    $deleted = saturne_orphan_documents_delete($db, $scope['filters']);
    if ($deleted < 0) {
        setEventMessages($langs->trans('ErrorSQL'), [], 'errors');
    } elseif ($scope['all']) {
        setEventMessages($langs->trans('OrphanDocumentsDeletedAllEntities', $deleted), []);
    } else {
        setEventMessages($langs->trans('OrphanDocumentsDeletedEntity', $deleted, $scope['entity']), []);
    }

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
print dol_get_fiche_head($head, 'maintenance', $title, -1, 'saturne_color@saturne');

// Both the listing and the deletion read the same scope, so what is displayed is what is removed
$scope           = saturne_maintenance_scope($user, $conf);
$orphanDocuments = ($permissiontoclean ? saturne_orphan_documents_count($db, $scope['filters']) : []);

require_once __DIR__ . '/../core/tpl/admin/maintenance_view.tpl.php';

print dol_get_fiche_end();

llxFooter();
$db->close();
