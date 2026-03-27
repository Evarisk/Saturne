<?php

/* Copyright (C) 2024-2025 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/list/objectfields_list_search_input.tpl.php
 * \ingroup saturne
 * \brief   Template page for object fields list search input
 */

/**
 * The following vars must be defined :
 * Globals    : $conf (extrafields_list_search_input.tpl), $db, $hookmanager, $langs
 * Parameters : $action, $sortfield, $sortorder
 * Objects    : $extrafields (extrafields_list_search_input.tpl), $form, $object,
 * Variable   : $arrayfields, $search, $search_array_options (extrafields_list_search_input.tpl)
 */

// Fields title search
// --------------------------------------------------------------------
// When the side filter panel is active, inputs live inside the panel — skip the inline filter row.
if (!empty($useSideFilterPanel)) {
    return;
}

print '<tr class="liste_titre_filter">';

// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
    print '<td class="liste_titre center maxwidthsearch">';
    $searchPicto = $form->showFilterButtons('left');
    print $searchPicto;
    print '</td>';
}

$toggleTitleRaw = $langs->trans('ToggleIncludeExclude');
if ($toggleTitleRaw == 'ToggleIncludeExclude') {
    $toggleTitleRaw = 'Inverser le filtre (voir tout sauf la sélection)';
}
$toggleTitle = dol_escape_htmltag($toggleTitleRaw);

foreach ($object->fields as $key => $val) {
    $cssForField = saturne_css_for_field($val, $key);
    if (!empty($arrayfields['t.' . $key]['checked'])) {
        print '<td class="liste_titre' . ($cssForField ? ' ' . $cssForField : '') . ($key == 'status' ? ' parentonrightofpage' : '') . '">';

        $parameters = ['arrayfields' => $arrayfields, 'key' => $key, 'val' => $val, 'search' => $search];
        $hookmanager->executeHooks('saturnePrintFieldListSearch', $parameters, $object);
        if (!empty($hookmanager->resArray[$key])) {
            print $hookmanager->resArray[$key];
            continue;
        }

        if (empty($val['disablesearch'])) {
            $showModeToggle = false;
            $fieldLabel     = $langs->trans($val['label'] ?? $key);
            if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
                $showModeToggle = ($key !== 'status');
                if ($showModeToggle) {
                    $fieldMode = $search[$key . '_mode'] ?? GETPOST('search_' . $key . '_mode', 'alpha') ?: 'inc';
                    $isExc     = ($fieldMode === 'exc');
                    print '<span class="saturne-filter-inline-wrapper">';
                    print '<input type="hidden" id="search_' . $key . '_mode" name="search_' . $key . '_mode" value="' . ($isExc ? 'exc' : 'inc') . '">';
                    $titleAttr = dol_escape_htmltag($fieldLabel) . ' - ' . $toggleTitle;
                    print '<span id="search_mode_toggle_' . $key . '" title="' . $titleAttr . '" class="saturne-filter-mode-toggle ' . ($isExc ? 'saturne-filter-mode-exc' : 'saturne-filter-mode-inc') . '">' . ($isExc ? '<span class="far fa-eye-slash"></span>' : '<span class="far fa-eye"></span>') . '</span>';
                }
                if (empty($val['searchmulti'])) {
                    print $form->selectarray('search_' . $key, $val['arrayofkeyval'], $search[$key] ?? '', 1, 0, 0, '', 1, 0, 0, '', 'maxwidth125' . ($key == 'status' ? ' search_status onrightofpage' : ''));
                } else {
                    print $form->multiselectarray('search_' . $key, $val['arrayofkeyval'], $search[$key] ?? '', 0, 0, 'maxwidth125' . ($key == 'status' ? ' search_status onrightofpage' : ''), 1, '100%');
                }
                if ($showModeToggle) {
                    print '</span>';
                }
            } elseif (isset($val['type']) && ((strpos($val['type'], 'integer:') === 0) || (strpos($val['type'], 'sellist:') === 0))) {
                $showModeToggle = true;
                $fieldMode = $search[$key . '_mode'] ?? GETPOST('search_' . $key . '_mode', 'alpha') ?: 'inc';
                $isExc     = ($fieldMode === 'exc');
                print '<span class="saturne-filter-inline-wrapper">';
                print '<input type="hidden" id="search_' . $key . '_mode" name="search_' . $key . '_mode" value="' . ($isExc ? 'exc' : 'inc') . '">';
                $titleAttr = dol_escape_htmltag($fieldLabel) . ' - ' . $toggleTitle;
                print '<span id="search_mode_toggle_' . $key . '" title="' . $titleAttr . '" class="saturne-filter-mode-toggle ' . ($isExc ? 'saturne-filter-mode-exc' : 'saturne-filter-mode-inc') . '">' . ($isExc ? '<span class="far fa-eye-slash"></span>' : '<span class="far fa-eye"></span>') . '</span>';
                print $object->showInputField($val, $key, $search[$key] ?? '', '', '', 'search_', $cssForField . ' maxwidth250', 1);
            } elseif (isset($val['type']) && in_array($val['type'], ['date', 'datetime', 'timestamp'])) {
                print '<div class="nowrap">';
                print $form->selectDate($search[$key . '_dtstart'] ?? '', 'search_' . $key . '_dtstart', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
                print '</div>';
                print '<div class="nowrap">';
                print $form->selectDate($search[$key . '_dtend'] ?? '', 'search_' . $key . '_dtend', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
                print '</div>';
            } elseif (isset($val['type']) && $val['type'] == 'duration') {
                print '<div class="nowrap">';
                print $form->select_duration('search_' . $key . '_dtstart', $search[$key . '_dtstart'] ?? '', 0, 'text', 0, 1);
                print '</div>';
                print '<div class="nowrap">';
                print $form->select_duration('search_' . $key . '_dtend', $search[$key . '_dtend'] ?? '', 0, 'text', 0, 1);
                print '</div>';
            } elseif ($key == 'lang') {
                require_once DOL_DOCUMENT_ROOT . '/core/class/html.formadmin.class.php';
                $formAdmin = new FormAdmin($db);
                print $formAdmin->select_language(($search[$key] ?? ''), 'search_lang', 0, [], 1, 0, 0, 'minwidth100imp maxwidth125', 2);
            } else {
                print '<input type="text" class="flat maxwidth' . (isset($val['type']) && in_array($val['type'], ['integer', 'price']) ? '50' : '75') . '" name="search_' . $key . '" value="' . dol_escape_htmltag($search[$key] ?? '') . '">';
            }

            if (!empty($showModeToggle) && isset($val['type']) && ((strpos($val['type'], 'integer:') === 0) || (strpos($val['type'], 'sellist:') === 0))) {
                print '</span>'; // close inline-flex wrapper for integer:/sellist: fields
            }
        }

        print '</td>';
    }
}

// Extra fields
require_once DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters = ['arrayfields' => $arrayfields];
$hookmanager->executeHooks('printFieldListOption', $parameters, $object, $action);
print $hookmanager->resPrint;

// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
    print '<td class="liste_titre center maxwidthsearch">';
    $searchPicto = $form->showFilterButtons();
    print $searchPicto;
    print '</td>';
}

print '</tr>';
