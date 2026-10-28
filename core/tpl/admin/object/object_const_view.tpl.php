<?php

$constArray = $constArray ?? [];
// Pages that do not compute it fall back on the permission of the module of the page
$permissiontowrite = $permissiontowrite ?? saturne_check_admin_write_access();
$parameters = [];
$reshook    = $hookmanager->executeHooks('saturneAdminObjectConst', $parameters);
if ($reshook > 0) {
    $constArray = $hookmanager->resArray;
}

if ($action == 'add_all_conf' && $permissiontowrite) {
    if (is_array($constArray) && !empty($constArray)) {
        foreach ($constArray[$moduleNameLowerCase] as $const) {
            if (getDolGlobalInt($const['code']) == 0 && empty($const['disabled'])) {
                dolibarr_set_const($db, $const['code'], 1, 'integer', 0, '', $conf->entity);
            }
        }
    }
}

if ($action == 'delete_all_conf' && $permissiontowrite) {
    if (is_array($constArray) && !empty($constArray)) {
        foreach ($constArray[$moduleNameLowerCase] as $const) {
            if (getDolGlobalInt($const['code']) == 1 && empty($const['disabled'])) {
                dolibarr_set_const($db, $const['code'], 0, 'integer', 0, '', $conf->entity);
            }
        }
    }
}

if (is_array($constArray) && !empty($constArray)) {
    print load_fiche_titre($langs->transnoentities('Config'), '', '');

    // An entry may carry a 'tutoImage' key holding the thumbnail HTML built by the calling module.
    // The column only shows up when at least one setting provides one, so other modules are untouched.
    $showTutoImages = false;
    foreach ($constArray[$moduleNameLowerCase] as $const) {
        if (!empty($const['tutoImage'])) {
            $showTutoImages = true;
            break;
        }
    }

    print '<table class="noborder">';
    print '<tr class="liste_titre">';
    print '<td>' . $langs->transnoentities('Parameters') . '</td>';
    print '<td>' . $langs->transnoentities('Description') . '</td>';
    if ($showTutoImages) {
        print '<td class="center">' . $langs->transnoentities('TutoImage') . '</td>';
    }
    print '<td class="center nowrap">';
    print $langs->transnoentities('Status') . '<br>';
    if ($permissiontowrite) {
        print '<a class="reposition commonlink" title="' . dol_escape_htmltag($langs->trans("All")) . '" href="' . $_SERVER["PHP_SELF"] . '?action=add_all_conf&token=' . newToken() . '"> <u>' . $langs->trans("All") . "</u> </a>";
        print ' / ';
        print '<a class="reposition commonlink" title="' . dol_escape_htmltag($langs->trans("None")) . '" href="' . $_SERVER["PHP_SELF"] . '?&action=delete_all_conf&token=' . newToken() . '"> <u>' . $langs->trans("None") . "</u> </a>";
    }
    print '</td></tr>';

    foreach ($constArray[$moduleNameLowerCase] as $const) {
        print '<tr class="oddeven"><td>';
        print $langs->trans($const['name']);
        print '</td><td>';
        print $langs->trans($const['description']);
        print '</td>';
        if ($showTutoImages) {
            print '<td class="center">';
            print $const['tutoImage'] ?? '';
            print '</td>';
        }
        print '<td class="center">';
        // A user without the write permission reads the state of the setting on a disabled switch
        if (empty($const['disabled'])) {
            print saturne_constant_onoff($const['code'], $permissiontowrite);
        }
        print '</td></tr>';
    }
    print '</table>';
}
