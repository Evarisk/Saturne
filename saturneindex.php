<?php
/* Copyright (C) 2022 EVARISK <dev@evarisk.com>
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
 *	\file       Saturneindex.php
 *	\ingroup    saturne
 *	\brief      Home page of saturne top menu
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER['CONTEXT_DOCUMENT_ROOT'])) {
	$res = @include $_SERVER['CONTEXT_DOCUMENT_ROOT']. '/main.inc.php';
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)). '/main.inc.php')) {
	$res = @include substr($tmp, 0, ($i + 1)). '/main.inc.php';
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))). '/main.inc.php')) {
	$res = @include dirname(substr($tmp, 0, ($i + 1))). '/main.inc.php';
}
// Try main.inc.php using relative path
if (!$res && file_exists('../main.inc.php')) {
	$res = @include '../main.inc.php';
}
if (!$res && file_exists('../../main.inc.php')) {
	$res = @include '../../main.inc.php';
}
if (!$res && file_exists('../../../main.inc.php')) {
	$res = @include '../../../main.inc.php';
}
if (!$res) {
	die('Include of main fails');
}

// Libraries
require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';

// Global variables definitions
global $db, $langs, $user;

// Load translation files required by the page
$langs->loadLangs(['saturne@saturne', 'admin', 'errors']);

// Get parameters
$action = GETPOST('action', 'aZ09');

$perms       = fileperms($dolibarr_main_document_root . '/' .$conffile);
$installlock = DOL_DATA_ROOT.'/install.lock';

// Security check
$permissiontoread = $user->rights->saturne->lire;
if (empty($conf->saturne->enabled)) accessforbidden();
if (!$permissiontoread) accessforbidden();

/*
 *  Actions
*/

//Check if there is a security problem
if (($perms & 0x0004) || ($perms & 0x0002) || !file_exists($installlock)) {
	$need_repair = 1;
}

if ($action == 'check') {
	if (($perms & 0x0004) || ($perms & 0x0002)) {
		chmod($dolibarr_main_document_root . '/' . $conffile, 0444);
		setEventMessage($langs->trans('ConfFileSetPermissions'));
	}
	if (!file_exists($installlock)) {
		fopen(DOL_DATA_ROOT . '/install.lock', 'w');
		setEventMessage($langs->trans('InstallLockFileCreated'));
	}
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

/*
 * View
 */

$help_url = 'FR:Module_Saturne';
$title    = $langs->trans('SaturneArea');

llxHeader('', $title, $help_url);

print load_fiche_titre($title, '', 'Saturne_color.png@saturne');

print load_fiche_titre($langs->trans('SecurityProblem'), '', '');

// $conffile is defined into filefunc.inc.php
print '<strong>' . $langs->trans('PermissionsOnFile', $conffile) . '</strong> : ';
if ($perms) {
	if (($perms & 0x0004) || ($perms & 0x0002)) {
		print img_warning() . ' ' .$langs->trans('ConfFileIsReadableOrWritableByAnyUsers');
		// Web user group by default
		$labeluser = dol_getwebuser('user');
		$labelgroup = dol_getwebuser('group');
		print ' ' . $langs->trans('User') . ' : ' . $labeluser . ' : ' . $labelgroup;
		if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
			$arrayofinfoofuser = posix_getpwuid(posix_geteuid());
			print ' <span class="opacitymedium">(POSIX ' . $arrayofinfoofuser['name'] . ' : ' . $arrayofinfoofuser['gecos'] . ' : ' . $arrayofinfoofuser['dir'] . ' : ' . $arrayofinfoofuser['shell'] . ')</span>';
		}
	} else {
		print img_picto('', 'tick') . ' ' . $langs->trans('ConfFileHasGoodPermissions');
	}
} else {
	print img_warning() . ' ' . $langs->trans('FailedToReadFile', $conffile);
}

print '<br><br>';

print '<strong>' . $langs->trans('DolibarrSetup') . '</strong> : ';
if (file_exists($installlock)) {
	print img_picto('', 'tick') . ' ' . $langs->trans('InstallAndUpgradeLockedBy', $installlock);
} else {
	print img_warning() . ' ' . $langs->trans('WarningLockFileDoesNotExists', DOL_DATA_ROOT);
}

print '<div class="tabsAction">';
// Repair security problem
if ($need_repair) {
    print '<a class="butAction" id="actionButtonCheck" href="' . $_SERVER['PHP_SELF'] . '?action=check' . '">' . $langs->trans('RepairSecurityProblem') . '</a>';
} else {
    print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('NoSecurityProblem')) . '">' . $langs->trans('RepairSecurityProblem') . '</span>';
}
print '</div>';

// End of page
llxFooter();
$db->close();
