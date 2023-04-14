<?php
$parameters = [];
$reshook    = $hookmanager->executeHooks('SaturneAdminObjectConst', $parameters); // Note that $action and $object may have been modified by some hooks
if (empty($reshook)) {
    $constArray = $hookmanager->resArray;
}

if (is_array($constArray) && !empty($constArray)) {
    // Config Data
    print load_fiche_titre($langs->transnoentities('ConfigData'), '', '');

    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>' . $langs->transnoentities('Parameters') . '</td>';
    print '<td>' . $langs->transnoentities('Description') . '</td>';
    print '<td class="center">' . $langs->transnoentities('Status') . '</td>';
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