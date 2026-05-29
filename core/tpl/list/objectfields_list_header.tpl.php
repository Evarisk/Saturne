<?php

/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/list/objectfields_list_header.tpl.php
 * \ingroup saturne
 * \brief   Template page for object fields list header
 */

/**
 * The following vars must be defined :
 * Globals    : $conf, $db, $hookmanager, $langs, $user
 * Parameters : $action, $limit, $contextpage, $massaction, $mode, $optioncss, $page, $searchAll, $sortfield, $sortorder, $toselect
 * Objects    : $categorie, $extrafields (extrafields_list_search_param.tpl), $form, $object
 * Variables  : $arrayfields, $createUrl (optional), $fieldsToSearchAll, $formMoreParams (optional), $helpText (optional),
 *              $nbTotalOfRecords, $num, $permissiontoadd, $resql, $search, $search_array_options (extrafields_list_search_param.tpl),
 *              $searchCategoriesFilter, $sql, $title
 */

// Output page
// --------------------------------------------------------------------
$arrayofselected = is_array($toselect) ? $toselect : [];

$param = '';
if (!empty($mode)) {
    $param .= '&mode=' . urlencode($mode);
}
if (!empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) {
    $param .= '&contextpage=' . urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
    $param .= '&limit=' . ((int) $limit);
}
if ($optioncss != '') {
    $param .= '&optioncss=' . urlencode($optioncss);
}
if (!empty($objectType)) {
    $param .= '&object_type=' . urlencode($objectType);
}
if (!empty($formMoreParams)) {
    foreach ($formMoreParams as $formMoreParamKey => $formMoreParamVal) {
        $param .= '&' . $formMoreParamKey . '=' . urlencode($formMoreParamVal);
    }
}
foreach ($search as $key => $val) {
    if (is_array($val)) {
        foreach ($val as $skey) {
            if ($skey != '') {
                $param .= '&search_' . $key . '[]=' . urlencode($skey);
            }
        }
    } elseif (preg_match('/(_dtstart|_dtend)$/', $key) && !empty($val)) {
        $param .= '&search_' . $key . 'min=' . GETPOSTINT('search_' . $key . 'min');
        $param .= '&search_' . $key . 'hour=' . GETPOSTINT('search_' . $key . 'hour');
        $param .= '&search_' . $key . 'month=' . GETPOSTINT('search_' . $key . 'month');
        $param .= '&search_' . $key . 'day=' . GETPOSTINT('search_' . $key . 'day');
        $param .= '&search_' . $key . 'year=' . GETPOSTINT('search_' . $key . 'year');
    } elseif ($val != '') {
        $param .= '&search_' . $key . '=' . urlencode($val);
    }
    // Propagate include/exclude mode for selectable fields
    if (array_key_exists($key, $object->fields) && $key !== 'status') {
        $fieldDef      = $object->fields[$key];
        $isSelectable  = !empty($fieldDef['arrayofkeyval'])
            || (isset($fieldDef['type']) && (strpos($fieldDef['type'], 'integer:') === 0 || strpos($fieldDef['type'], 'sellist:') === 0));
        if ($isSelectable && GETPOST('search_' . $key . '_mode', 'alpha') === 'exc') {
            $param .= '&search_' . $key . '_mode=exc';
        }
    }
}

// Preserve active category filters across sort / pagination / view-mode links
foreach (GETPOST('search_categories_filter', 'array') as $catFilterVal) {
    $catFilterVal = (int) $catFilterVal;
    if ($catFilterVal != 0) {
        $param .= '&search_categories_filter[]=' . urlencode((string) $catFilterVal);
    }
}

// Add $param from extra fields
require_once DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_param.tpl.php';

// Add $param from hooks
$parameters = ['param' => &$param];
$hookmanager->executeHooks('printFieldListSearchParam', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$param .= $hookmanager->resPrint;

// List of mass actions available
$arrayOfMassActions = [
    'prearchive' => '<span class="fas fa-archive paddingrightonly"></span>' . $langs->trans('Archive')
];

if (!empty($permissiontodelete)) {
    $arrayOfMassActions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"') . $langs->trans('Delete');
}
if (GETPOSTINT('nomassaction') || in_array($massaction, ['presend', 'predelete'])) {
    $arrayOfMassActions = [];
}
$massActionButton = $form->selectMassAction('', $arrayOfMassActions);

print '<form method="POST" id="searchFormList" action="' . $_SERVER['PHP_SELF'] . '">';
if ($optioncss != '') {
    print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
}
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
print '<input type="hidden" name="page" value="' . $page . '">';
print '<input type="hidden" name="contextpage" value="' . $contextpage . '">';
print '<input type="hidden" name="page_y" value="">';
print '<input type="hidden" name="mode" value="' . $mode . '">';
if (!empty($objectType)) {
    print '<input type="hidden" name="object_type" value="' . $objectType . '">';
}
if (!empty($formMoreParams)) {
    foreach ($formMoreParams as $formMoreParamKey => $formMoreParamVal) {
        print '<input type="hidden" name="' . $formMoreParamKey . '" value="' . $formMoreParamVal . '">';
    }
}

// Saturne lists place the action column (search/reset buttons, select-all
// checkbox) on the LEFT, like a native Dolibarr list with left checkboxes.
$useLeftActionColumn = true;

// Apply user column preferences to $arrayfields now, so all loops below use correct checked values
$selectedFields = '';
if ($mode != 'pwa' && $mode != 'kanban') {
    $varPage        = $contextpage ?: $_SERVER['PHP_SELF'];
    $selectedFields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varPage, ($useLeftActionColumn ? 'left' : ''));
}

// Build top filter toolbar content (global search + categories)
// --------------------------------------------------------------------
$filterToolbarBody = '';

// 0. Global search_all field (if used by calling page)
if (!empty($fieldsToSearchAll)) {
    $searchAllPlaceholder = $langs->trans('SearchInAllFields');
    $filterToolbarBody .= '<div class="saturne-filter-search-all-wrapper">';
    $filterToolbarBody .= '<input type="text" class="flat saturne-filter-search-all-input" name="search_all" id="filterbar_search_all" placeholder="' . dol_escape_htmltag($searchAllPlaceholder) . '" value="' . dol_escape_htmltag($searchAll ?? '') . '">';
    $filterToolbarBody .= '</div>';
}

// 1. Category filter section (picker + colored tags)
if (isModEnabled('categorie') && $user->hasRight('categorie', 'read') && isset($categorie->MAP_OBJ_CLASS[$object->element])) {
    require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcategory.class.php';
    $formCategory  = new FormCategory($db);
    $rawCategories = $formCategory->select_all_categories($object->element, '', '', 64, 0, 2); // outputmode=2 → full arbo with color
    $langs->load('categories');

    $categoryMap = [];
    if (is_array($rawCategories)) {
        foreach ($rawCategories as $cat) {
            $hex                           = !empty($cat['color']) ? '#' . ltrim($cat['color'], '#') : '#95a5a6';
            $categoryMap[(int) $cat['id']] = ['label' => $cat['fulllabel'], 'color' => $hex];
        }
    }

    if (!isset($searchCategoriesFilter)) {
        $searchCategoriesFilter = array_values(array_filter(array_map('intval', GETPOST('search_categories_filter', 'array'))));
    }

    $initialTags      = [];
    $initialTagCatIds = [];
    foreach (($searchCategoriesFilter ?? []) as $filterVal) {
        $id      = abs((int) $filterVal);
        $catMode = ((int) $filterVal < 0) ? 'exc' : 'inc';
        if ($id > 0 && isset($categoryMap[$id])) {
            $initialTags[]      = ['id' => $id, 'label' => $categoryMap[$id]['label'], 'color' => $categoryMap[$id]['color'], 'mode' => $catMode];
            $initialTagCatIds[] = $id;
        }
    }

    $elementId   = dol_escape_htmltag($object->element);
    $catColorsJs = json_encode(array_map(fn($v) => $v['color'], $categoryMap));
    $catIcon     = img_picto('', 'category', 'class="saturne-cat-icon"');

    $filterToolbarBody .= '<div class="saturne-filterbar-cat">';

    $filterToolbarBody .= '<select id="cat_filter_picker_' . $elementId . '" class="flat saturne-filter-cat-picker" title="' . dol_escape_htmltag($langs->trans('AddCategory')) . '">';
    $filterToolbarBody .= '<option value="">&nbsp;</option>';
    foreach ($categoryMap as $catId => $catData) {
        if (in_array($catId, $initialTagCatIds)) {
            continue;
        }
        $filterToolbarBody .= '<option value="' . $catId . '" data-color="' . dol_escape_htmltag($catData['color']) . '">' . dol_escape_htmltag($catData['label']) . '</option>';
    }
    $filterToolbarBody .= '</select>';

    $filterToolbarBody .= '<div id="cat_filter_tags_' . $elementId . '" class="saturne-cat-filter-tags" data-picker-id="cat_filter_picker_' . $elementId . '" data-cat-icon="' . dol_escape_htmltag($catIcon) . '" data-cat-colors="' . dol_escape_htmltag($catColorsJs) . '">';
    foreach ($initialTags as $tag) {
        $isExcTag = $tag['mode'] === 'exc';
        $color    = $tag['color'];
        $sign     = $isExcTag ? '&minus;' : '+';
        $tagVal   = ($isExcTag ? '-' : '+') . $tag['id'];
        $filterToolbarBody .= '<span class="saturne-cat-tag" style="border-color:' . $color . '"';
        $filterToolbarBody .= ' data-catid="' . $tag['id'] . '" data-mode="' . $tag['mode'] . '" data-label="' . dol_escape_htmltag($tag['label']) . '" data-color="' . dol_escape_htmltag($color) . '">';
        $filterToolbarBody .= '<span class="cat-sign saturne-cat-tag-sign" title="' . dol_escape_htmltag($langs->trans('ToggleIncludeExclude')) . '" style="background:' . $color . '">' . $catIcon . ' ' . $sign . '</span>';
        $filterToolbarBody .= '<span class="saturne-cat-tag-body"><span class="saturne-cat-tag-label' . ($isExcTag ? ' is-exc' : '') . '">' . dol_escape_htmltag($tag['label']) . '</span>';
        $filterToolbarBody .= '<span class="cat-remove saturne-cat-tag-remove" title="' . dol_escape_htmltag($langs->trans('Remove')) . '">&times;</span></span>';
        $filterToolbarBody .= '<input type="hidden" name="search_categories_filter[]" value="' . dol_escape_htmltag($tagVal) . '">';
        $filterToolbarBody .= '</span>';
    }
    $filterToolbarBody .= '</div>';

    $filterToolbarBody .= '</div>';
}

// Header buttons (view modes + create) — no more "Filters" toggle button
$newCardButton  = ($newCardButton ?? '');
$newCardButton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', $_SERVER['PHP_SELF'] . '?mode=common' . preg_replace('/([&?])*mode=[^&]+/', '', $param), '', ((empty($mode) || $mode == 'common') ? 2 : 1), ['morecss' => 'reposition']);
$newCardButton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', $_SERVER['PHP_SELF'] . '?mode=kanban' . preg_replace('/([&?])*mode=[^&]+/', '', $param), '', ($mode == 'kanban' ? 2 : 1), ['morecss' => 'reposition']);
$newCardButton .= dolGetButtonTitle($langs->trans('ViewPwa'), '', 'fa fa-mobile imgforviewmode', $_SERVER['PHP_SELF'] . '?mode=pwa' . preg_replace('/([&?])*mode=[^&]+/', '', $param), '', ($mode == 'pwa' ? 2 : 1), ['morecss' => 'reposition']);
$cardButton     = dolGetButtonTitle($langs->trans('New' . ucfirst($object->element)), $helpText ?? '', 'fa fa-plus-circle', ($createUrl ?? dol_buildpath('custom/' . $object->module . '/view/' . $object->element . '/' . $object->element . '_card.php', 1) . '?action=create' . ($moreUrlParameters ?? '')), '', $permissiontoadd);

$listTitle = (($conf->browser->layout == 'classic' && $mode != 'pwa') ? $title : '') . ' ' . $cardButton;
print_barre_liste($listTitle, $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, $massActionButton, $num, $nbTotalOfRecords, $object->picto, 0, $newCardButton, '', $limit, 0, 0, 1);

require_once DOL_DOCUMENT_ROOT . '/core/tpl/massactions_pre.tpl.php';

if ($massaction == 'prearchive') {
    print $form->formconfirm($_SERVER['PHP_SELF'], $langs->trans('ConfirmMassArchive'), $langs->trans('ConfirmMassArchivingQuestion', count($toselect)), 'archive', null, '', 0, 200, 500, 1);
}

if ($searchAll) {
    foreach ($fieldsToSearchAll as $key => $val) {
        $fieldsToSearchAll[$key] = $langs->trans($val);
    }
    print '<div class="divsearchfieldfilter">' . $langs->trans('FilterOnInto', $searchAll) . implode(', ', $fieldsToSearchAll) . '</div>';
}

// Hook: extra content above the list (moreForFilter)
$moreForFilter = '';
$parameters = ['arrayfields' => &$arrayfields];
$reshook    = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if (empty($reshook)) {
    $moreForFilter .= $hookmanager->resPrint;
} else {
    $moreForFilter = $hookmanager->resPrint;
}
if (!empty($moreForFilter)) {
    print '<div class="liste_titre liste_titre_bydiv centpercent">';
    print $moreForFilter;
    print '</div>';
}

// Top filter toolbar (global search + categories) — replaces the former side panel.
// Hidden in PWA mode (its own mobile filter UX) and when there is nothing to show.
if ($mode != 'pwa' && $filterToolbarBody !== '') {
    print '<div class="saturne-list-filterbar">';
    print $filterToolbarBody;
    print '<span class="saturne-filter-legend-inline">';
    print '<span class="saturne-filter-legend-include"><span class="far fa-eye"></span> Inclure</span>';
    print '<span class="saturne-filter-legend-exclude"><span class="far fa-eye-slash"></span> Exclure</span>';
    print '</span>';
    print '</div>';
}

if (!empty($arrayOfMassActions)) {
    $selectedFields .= $form->showCheckAddButtons('checkforselect', 1);
}

// Preserve non-visible search parameters as hidden inputs so they survive form submissions
foreach ($search as $key => $val) {
    if (array_key_exists($key, $object->fields) && empty($arrayfields['t.' . $key]['checked']) && $val !== '' && !is_array($val)) {
        print '<input type="hidden" name="search_' . $key . '" value="' . dol_escape_htmltag($val) . '">';
    }
}

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
print '<table class="tagtable nobottomiftotal noborder liste' . (($moreForFilter || ($mode != 'pwa' && $filterToolbarBody !== '')) ? ' listwithfilterbefore' : '') . '">';
print '<thead>';
