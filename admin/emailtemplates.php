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
 * \file    admin/emailtemplates.php
 * \ingroup saturne
 * \brief   Overview of the email templates, a tab of the email setup
 *
 * The templates are still edited on the core page (admin/mails_templates.php): this one groups them by
 * element, makes them readable and tells which setting sends each of them.
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
require_once __DIR__ . '/../lib/emailtemplate.lib.php';

// Global variables definitions
global $conf, $db, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['admin', 'mails', 'other']);

// Initialize view objects
$form = new Form($db);

// Get parameters
$action = GETPOST('action', 'aZ09');
$rowid  = GETPOSTINT('rowid');

// Security check - The email setup is an administrator page, like the core email templates page in that context
$permissiontoread = $user->admin;

saturne_check_access($permissiontoread);

/*
 * Actions
 */

if (($action == 'activate' || $action == 'disable') && $rowid > 0) {
    if (saturne_email_template_set_active($db, $rowid, $action == 'activate' ? 1 : 0) < 0) {
        setEventMessages($db->lasterror(), [], 'errors');
    }

    header('Location: ' . $_SERVER['PHP_SELF'] . '#mailtpl-' . $rowid);
    exit;
}

/*
 * View
 */

$title    = $langs->trans('EMailsSetup');
$help_url = 'FR:Module_Saturne#Configuration';

$overview = saturne_email_template_overview($db, $user, $langs);

saturne_header(0, '', $title, $help_url);

print load_fiche_titre($title, '', 'title_setup');

$head = email_admin_prepare_head();
print dol_get_fiche_head($head, 'saturneemailtemplates', '', -1);

require_once __DIR__ . '/../core/tpl/admin/emailtemplates_view.tpl.php';

print dol_get_fiche_end();

llxFooter();
$db->close();
