<?php
$parameters = [];
$reshook    = $hookmanager->executeHooks('SaturneAdminObjectConst', $parameters); // Note that $action and $object may have been modified by some hooks
if ($reshook > 0) {
    $constArray = $hookmanager->resArray;
}

if ($action == 'add_all_conf') {
	if (is_array($constArray) && !empty($constArray)) {
		foreach ($constArray[$object->element] as $const) {
			if (empty($conf->global->$const['code'])) {
				dolibarr_set_const($db, $const['code'], 1, 'integer', 0, '', $conf->entity);
			}
		}
	}
}

if ($action == 'delete_all_conf') {
	if (is_array($constArray) && !empty($constArray)) {
		foreach ($constArray[$object->element] as $const) {
			if (empty($conf->global->$const['code'])) {
				dolibarr_set_const($db, $const['code'], 0, 'integer', 0, '', $conf->entity);
			}
		}
	}
}

if (is_array($constArray) && !empty($constArray)) {
    // Config Data
    print load_fiche_titre($langs->transnoentities('Config'), '', '');

    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>' . $langs->transnoentities('Parameters') . '</td>';
    print '<td>' . $langs->transnoentities('Description') . '</td>';
	print '<td class="center nowrap">';
	print $langs->transnoentities('Status') . '</br>';
	print '<a class="reposition commonlink" title="' . dol_escape_htmltag($langs->trans("All")) . '" href="' . $_SERVER["PHP_SELF"].'?action=add_all_conf&token=' . newToken() . '"> <u>' . $langs->trans("All") . "</u> </a>";
	print ' / ';
	print '<a class="reposition commonlink" title="' . dol_escape_htmltag($langs->trans("None")) . '" href="' . $_SERVER["PHP_SELF"] . '?&action=delete_all_conf&token=' . newToken() . '"> <u>' . $langs->trans("None")."</u> </a>";
	print '</td>';
    print '</tr>';

    foreach ($constArray[$object->element] as $const) {
        print '<tr class="oddeven"><td>';
        print $langs->trans($const['name']);
        print '</td><td>';
        print $langs->trans($const['description']);
        print '</td>';
        print '<td class="center">';
        print ajax_constantonoff($const['code']);
        print '</td>';
        print '</tr>';
    }

    print '</table>';
}
