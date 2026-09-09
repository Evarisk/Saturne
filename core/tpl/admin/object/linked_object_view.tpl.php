<?php

/* Copyright (C) 2022-2026 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/admin/object/linked_object_view.tpl.php
 * \ingroup saturne
 * \brief   Template page to manage the objects a module may link to
 *
 * Expected variables, all prepared by the calling page :
 * $langs                      - Translate object
 * $user                       - Current user
 * $linkableObjects            - Result of saturne_filter_linkable_objects()
 * $enabledObjectTypes         - Result of saturne_get_enabled_linked_object_types()
 * $linkedObjectUsage          - Result of saturne_get_linked_object_usage()
 * $linkedObjectExtraFieldName - Extrafield name whose deletion must be confirmed
 */

print load_fiche_titre($langs->trans('LinkableElements'), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Element') . '</td>';
print '<td>' . $langs->trans('LinkedObjectUsage') . '</td>';
print '<td class="center nowrap">';
print $langs->trans('Status') . '<br>';
if ($user->admin) {
    $enableAllUrl  = $_SERVER['PHP_SELF'] . '?action=toggle_all_links&value=1&token=' . newToken();
    $disableAllUrl = $_SERVER['PHP_SELF'] . '?action=toggle_all_links&value=0&token=' . newToken();

    print '<a class="reposition commonlink linked-object-toggle" href="' . $enableAllUrl . '"';
    print ' data-confirm-message="' . dol_escape_htmltag($langs->trans('EnableAllLinksConfirm')) . '">';
    print ' <u>' . $langs->trans('All') . '</u> </a>';
    print ' / ';
    print '<a class="reposition commonlink linked-object-toggle" href="' . $disableAllUrl . '"';
    print ' data-confirm-message="' . dol_escape_htmltag($langs->trans('DisableAllLinksConfirm')) . '">';
    print ' <u>' . $langs->trans('None') . '</u> </a>';
}
print '</td></tr>';

foreach ($linkableObjects as $objectType => $objectMetadata) {
    $isEnabled   = in_array($objectType, $enabledObjectTypes, true);
    $objectLabel = $langs->trans($objectMetadata['langs']);
    $linkCount   = $linkedObjectUsage[$objectType]['links'];
    $valueCount  = $linkedObjectUsage[$objectType]['extrafields'][$linkedObjectExtraFieldName];

    $toggleUrl  = $_SERVER['PHP_SELF'] . '?action=toggle_link&objecttype=' . urlencode($objectType);
    $toggleUrl .= '&value=' . ($isEnabled ? 0 : 1) . '&token=' . newToken();

    $statusPicto = $isEnabled
        ? img_picto($langs->trans('Enabled'), 'switch_on')
        : img_picto($langs->trans('Disabled'), 'switch_off');

    print '<tr class="oddeven">';

    print '<td>';
    print img_picto('', $objectMetadata['picto'], 'class="pictofixedwidth"');
    print $objectLabel;
    print '</td>';

    print '<td>';
    if ($linkCount > 0 || $valueCount > 0) {
        print $langs->trans('LinkedObjectUsageDetail', $linkCount, $valueCount);
    } else {
        print '<span class="opacitymedium">' . $langs->trans('NoLinkedObjectUsage') . '</span>';
    }
    print '</td>';

    print '<td class="center">';
    if ($user->admin) {
        // The confirmation is only rendered when switching off would really destroy values.
        $confirmMessage = '';
        if ($isEnabled && $valueCount > 0) {
            $confirmMessage = $langs->trans('DisableLinkConfirm', $objectLabel, $valueCount);
        }

        print '<a class="linked-object-toggle" href="' . $toggleUrl . '"';
        print ' data-confirm-message="' . dol_escape_htmltag($confirmMessage) . '">';
        print $statusPicto;
        print '</a>';
    } else {
        print $statusPicto;
    }
    print '</td>';

    print '</tr>';
}

print '</table>';

if ($user->admin) {
    $cleanUrl = $_SERVER['PHP_SELF'] . '?action=clean_unused_links&token=' . newToken();

    print '<div class="tabsAction">';
    print '<div class="inline-block divButAction">';
    print '<a class="butAction linked-object-toggle" href="' . $cleanUrl . '"';
    print ' data-confirm-message="' . dol_escape_htmltag($langs->trans('CleanUnusedLinksConfirm')) . '">';
    print $langs->trans('CleanUnusedLinks');
    print '</a>';
    print '</div>';
    print '</div>';
}
