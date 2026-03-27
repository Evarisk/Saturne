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
 *              $searchCategories, $sql, $title
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
//if ($groupby != '') {
//    $param .= '&groupby=' . urlencode($groupby);
//}
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

// Add $param from extra fields
require_once DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_param.tpl.php';

// Add $param from hooks
$parameters = ['param' => &$param];
$hookmanager->executeHooks('printFieldListSearchParam', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$param .= $hookmanager->resPrint;

// List of mass actions available
$arrayOfMassActions = [
    //'validate'=>img_picto('', 'check', 'class="pictofixedwidth"').$langs->trans("Validate"),
    //'generate_doc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("ReGeneratePDF"),
    //'builddoc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
    //'presend'=>img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
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
//print '<input type="hidden" name="groupby" value="' . $groupby . '">';
if (!empty($formMoreParams)) {
    foreach ($formMoreParams as $formMoreParamKey => $formMoreParamVal) {
        print '<input type="hidden" name="' . $formMoreParamKey . '" value="' . $formMoreParamVal . '">';
    }
}

// Remove disabled fields from $arrayfields before any loop — dropdown, panel, headers and loop all use this array
foreach ($arrayfields as $afKey => $afField) {
    if (empty($afField['enabled'])) {
        unset($arrayfields[$afKey]);
    }
}

// Apply user column preferences to $arrayfields now, so $panelFilterBody and all loops below use correct checked values
$selectedFields = '';
if ($mode != 'pwa' && $mode != 'kanban') {
    $varPage        = $contextpage ?: $_SERVER['PHP_SELF'];
    $selectedFields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varPage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN'));
}

// Build side filter panel content
// --------------------------------------------------------------------
$panelFilterBody    = '';
$useSideFilterPanel = true;

// Panel i18n labels
$filterBtnLabel = $langs->trans('Filters');
if ($filterBtnLabel === 'Filters') {
    $filterBtnLabel = 'Filtres';
}
$applyBtnLabel = $langs->trans('Apply');
if ($applyBtnLabel === 'Apply') {
    $applyBtnLabel = 'Appliquer';
}
$resetBtnLabel = $langs->trans('Reset');
if ($resetBtnLabel === 'Reset') {
    $resetBtnLabel = 'Réinitialiser';
}

// 0. Global search_all field (if used by calling page)
if (!empty($fieldsToSearchAll)) {
    $searchAllPlaceholder = $langs->trans('Search');
    if ($searchAllPlaceholder === 'Search') {
        $searchAllPlaceholder = 'Rechercher dans tous les champs…';
    }
    $panelFilterBody .= '<div class="saturne-filter-search-all-wrapper">';
    $panelFilterBody .= '<input type="text" class="flat saturne-filter-search-all-input" name="search_all" id="panel_search_all" placeholder="' . dol_escape_htmltag($searchAllPlaceholder) . '" value="' . dol_escape_htmltag($searchAll ?? '') . '">';
    $panelFilterBody .= '</div>';
}

// 1. Category filter section inside panel
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
    $catIconJs   = json_encode($catIcon);

    $panelFilterBody .= '<div class="saturne-filter-section">';
    $panelFilterBody .= '<div class="saturne-filter-section-title">'
        . img_picto($langs->trans('Categories'), 'category', 'class="pictofixedwidth"')
        . ' ' . $langs->trans('Categories') . '</div>';

    // Picker (full-width inside panel)
    $panelFilterBody .= '<select id="cat_filter_picker_' . $elementId . '" class="flat saturne-filter-cat-picker" title="' . dol_escape_htmltag($langs->trans('AddCategory')) . '">';
    $panelFilterBody .= '<option value="">&nbsp;</option>';
    foreach ($categoryMap as $catId => $catData) {
        if (in_array($catId, $initialTagCatIds)) {
            continue;
        }
        $panelFilterBody .= '<option value="' . $catId . '" data-color="' . dol_escape_htmltag($catData['color']) . '">' . dol_escape_htmltag($catData['label']) . '</option>';
    }
    $panelFilterBody .= '</select>';

    // Tag list
    $panelFilterBody .= '<div id="cat_filter_tags_' . $elementId . '" class="saturne-cat-filter-tags" data-picker-id="cat_filter_picker_' . $elementId . '" data-cat-icon="' . dol_escape_htmltag($catIconJs) . '" data-cat-colors="' . dol_escape_htmltag($catColorsJs) . '">';
    foreach ($initialTags as $tag) {
        $isExcTag = $tag['mode'] === 'exc';
        $color    = $tag['color'];
        $sign     = $isExcTag ? '&minus;' : '+';
        $tagVal   = ($isExcTag ? '-' : '+') . $tag['id'];
        $panelFilterBody .= '<span class="saturne-cat-tag" style="border-color:' . $color . '"';
        $panelFilterBody .= ' data-catid="' . $tag['id'] . '" data-mode="' . $tag['mode'] . '" data-label="' . dol_escape_htmltag($tag['label']) . '" data-color="' . dol_escape_htmltag($color) . '">';
        $panelFilterBody .= '<span class="cat-sign saturne-cat-tag-sign" title="' . dol_escape_htmltag($langs->trans('ToggleIncludeExclude')) . '" style="background:' . $color . '">' . $catIcon . ' ' . $sign . '</span>';
        $panelFilterBody .= '<span class="saturne-cat-tag-body"><span class="saturne-cat-tag-label' . ($isExcTag ? ' is-exc' : '') . '">' . dol_escape_htmltag($tag['label']) . '</span>';
        $panelFilterBody .= '<span class="cat-remove saturne-cat-tag-remove" title="' . dol_escape_htmltag($langs->trans('Remove')) . '">&times;</span></span>';
        $panelFilterBody .= '<input type="hidden" name="search_categories_filter[]" value="' . dol_escape_htmltag($tagVal) . '">';
        $panelFilterBody .= '</span>';
    }
    $panelFilterBody .= '</div>';


    $panelFilterBody .= '</div>';
}

// 2. Field filters section inside panel
$toggleTitlePanelRaw = $langs->trans('ToggleIncludeExclude');
if ($toggleTitlePanelRaw === 'ToggleIncludeExclude') {
    $toggleTitlePanelRaw = 'Inverser le filtre (voir tout sauf la sélection)';
}
$toggleTitlePanel = dol_escape_htmltag($toggleTitlePanelRaw);

foreach ($object->fields as $key => $val) {
    if (empty($arrayfields['t.' . $key]['checked'])) {
        continue;
    }
    if (!empty($val['disablesearch'])) {
        continue;
    }
    if (isset($val['visible']) && (int) $val['visible'] === 0) {
        continue;
    }

    $fieldLabelPanel = $langs->trans($val['label'] ?? $key);
    $cssForFieldPanel = saturne_css_for_field($val, $key);

    $panelFilterBody .= '<div class="saturne-filter-field-row">';
    $panelFilterBody .= '<div class="saturne-filter-field-label">' . dol_escape_htmltag($fieldLabelPanel) . '</div>';
    $panelFilterBody .= '<div class="saturne-filter-field-input">';

    if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
        $showToggle = ($key !== 'status');
        if ($showToggle) {
            $fMode  = GETPOST('search_' . $key . '_mode', 'alpha') ?: 'inc';
            $isExc  = ($fMode === 'exc');
            $tAttr  = dol_escape_htmltag($fieldLabelPanel) . ' - ' . $toggleTitlePanel;
            $panelFilterBody .= '<input type="hidden" id="search_' . $key . '_mode" name="search_' . $key . '_mode" value="' . ($isExc ? 'exc' : 'inc') . '">';
            $panelFilterBody .= '<span id="search_mode_toggle_' . $key . '" title="' . $tAttr . '" class="saturne-filter-mode-toggle ' . ($isExc ? 'saturne-filter-mode-exc' : 'saturne-filter-mode-inc') . '">' . ($isExc ? '<span class="far fa-eye-slash"></span>' : '<span class="far fa-eye"></span>') . '</span>';
        }
        if (empty($val['searchmulti'])) {
            $panelFilterBody .= $form->selectarray('search_' . $key, $val['arrayofkeyval'], $search[$key] ?? '', 1, 0, 0, '', 1, 0, 0, '', 'maxwidth200' . ($key == 'status' ? ' search_status onrightofpage' : ''), 0);
        } else {
            $panelFilterBody .= $form->multiselectarray('search_' . $key, $val['arrayofkeyval'], $search[$key] ?? '', 0, 0, 'maxwidth200' . ($key == 'status' ? ' search_status onrightofpage' : ''), 1, '100%', '', 0);
        }
    } elseif (isset($val['type']) && ((strpos($val['type'], 'integer:') === 0) || (strpos($val['type'], 'sellist:') === 0))) {
        $fMode = GETPOST('search_' . $key . '_mode', 'alpha') ?: 'inc';
        $isExc = ($fMode === 'exc');
        $tAttr = dol_escape_htmltag($fieldLabelPanel) . ' - ' . $toggleTitlePanel;
        $panelFilterBody .= '<input type="hidden" id="search_' . $key . '_mode" name="search_' . $key . '_mode" value="' . ($isExc ? 'exc' : 'inc') . '">';
        $panelFilterBody .= '<span id="search_mode_toggle_' . $key . '" title="' . $tAttr . '" class="saturne-filter-mode-toggle ' . ($isExc ? 'saturne-filter-mode-exc' : 'saturne-filter-mode-inc') . '">' . ($isExc ? '<span class="far fa-eye-slash"></span>' : '<span class="far fa-eye"></span>') . '</span>';
        ob_start();
        $showInputHtml = $object->showInputField($val, $key, $search[$key] ?? '', '', '', 'search_', $cssForFieldPanel . ' maxwidth200 saturne-panel-select', 1);
        ob_end_clean(); // discard ajax_combobox JS printed directly — panel will init select2 on open
        $panelFilterBody .= $showInputHtml;
    } elseif (isset($val['type']) && in_array($val['type'], ['date', 'datetime', 'timestamp'])) {
        $panelFilterBody .= '<div class="saturne-filter-date-wrapper">'
            . '<div class="nowrap">' . $form->selectDate($search[$key . '_dtstart'] ?? '', 'search_' . $key . '_dtstart', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From')) . '</div>'
            . '<div class="nowrap">' . $form->selectDate($search[$key . '_dtend'] ?? '', 'search_' . $key . '_dtend', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to')) . '</div>'
            . '</div>';
    } elseif (isset($val['type']) && $val['type'] == 'duration') {
        $panelFilterBody .= '<div class="saturne-filter-date-wrapper">'
            . '<div class="nowrap">' . $form->select_duration('search_' . $key . '_dtstart', $search[$key . '_dtstart'] ?? '', 0, 'text', 0, 1) . '</div>'
            . '<div class="nowrap">' . $form->select_duration('search_' . $key . '_dtend', $search[$key . '_dtend'] ?? '', 0, 'text', 0, 1) . '</div>'
            . '</div>';
    } elseif ($key == 'lang') {
        require_once DOL_DOCUMENT_ROOT . '/core/class/html.formadmin.class.php';
        $formAdmin        = new FormAdmin($db);
        $panelFilterBody .= $formAdmin->select_language(($search[$key] ?? ''), 'search_lang', 0, [], 1, 0, 0, 'minwidth100imp maxwidth200', 2);
    } else {
        $panelFilterBody .= '<input type="text" class="flat saturne-filter-text-input" name="search_' . $key . '" value="' . dol_escape_htmltag($search[$key] ?? '') . '">';
    }

    $panelFilterBody .= '</div>';
    $panelFilterBody .= '</div>';
}

// Count active filters for badge — mirrors the panel field visibility rules
$activeFilterCount = 0;
foreach ($object->fields as $key => $val) {
    if (empty($arrayfields['t.' . $key]['checked'])) {
        continue;
    }
    if (!empty($val['disablesearch'])) {
        continue;
    }
    if (isset($val['visible']) && (int) $val['visible'] === 0) {
        continue;
    }
    $searchVal = $search[$key] ?? '';
    if (is_array($searchVal) ? !empty($searchVal) : ($searchVal !== '' && $searchVal != -1)) {
        $activeFilterCount++;
    }
}
$activeFilterCount += count($searchCategoriesFilter ?? []);

$newCardButton  = ($newCardButton ?? '');
$newCardButton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', $_SERVER['PHP_SELF'] . '?mode=common' . preg_replace('/([&?])*mode=[^&]+/', '', $param), '', ((empty($mode) || $mode == 'common') ? 2 : 1), ['morecss' => 'reposition']);
$newCardButton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', $_SERVER['PHP_SELF'] . '?mode=kanban' . preg_replace('/([&?])*mode=[^&]+/', '', $param), '', ($mode == 'kanban' ? 2 : 1), ['morecss' => 'reposition']);
$newCardButton .= dolGetButtonTitle($langs->trans('ViewPwa'), '', 'fa fa-mobile imgforviewmode', $_SERVER['PHP_SELF'] . '?mode=pwa' . preg_replace('/([&?])*mode=[^&]+/', '', $param), '', ($mode == 'pwa' ? 2 : 1), ['morecss' => 'reposition']);
$cardButton     = dolGetButtonTitle($langs->trans('New' . ucfirst($object->element)), $helpText ?? '', 'fa fa-plus-circle', ($createUrl ?? dol_buildpath('custom/' . $object->module . '/view/' . $object->element . '/' . $object->element . '_card.php', 1) . '?action=create' . ($moreUrlParameters ?? '')), '', $permissiontoadd);

// Filter panel toggle button — left side, in the title area
$filterButton = dolGetButtonTitle($filterBtnLabel, '', 'fas fa-sliders-h', '#', 'saturne-filter-toggle', $activeFilterCount > 0 ? 2 : 1, ['morecss' => 'reposition']);
if ($activeFilterCount > 0) {
    $filterButton = '<span class="saturne-filter-btn-wrapper">' . $filterButton . '<span class="saturne-filter-badge">' . $activeFilterCount . '</span></span>';
}
$listTitle    = (($conf->browser->layout == 'classic' && $mode != 'pwa') ? $title : '') . ' ' . $cardButton . ' ' . $filterButton;
print_barre_liste($listTitle, $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, $massActionButton, $num, $nbTotalOfRecords, $object->picto, 0, $newCardButton, '', $limit, 0, 0, 1);

// Add code for pre mass action (confirmation or email presend form)
//$topicmail = "SendMyObjectRef";
//$modelmail = "myobject";
//$objecttmp = new MyObject($db);
//$trackid = 'xxxx'.$object->id;

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

if (!empty($arrayOfMassActions)) {
    $selectedFields .= $form->showCheckAddButtons('checkforselect', 1);
}

// Side filter panel (fixed overlay, inside the form)
// --------------------------------------------------------------------
print '<div id="saturne-filter-backdrop"></div>';
print '<div id="saturne-filter-panel">';

// Panel header
print '<div class="saturne-filter-panel-header">';
print '<strong class="saturne-filter-panel-title"><span class="fa fa-sliders-h"></span>' . dol_escape_htmltag($filterBtnLabel) . '</strong>';
print '<span class="saturne-filter-panel-close">&times;</span>';
print '</div>';

// Panel body
print '<div class="saturne-filter-panel-body">';

// Legend notice explaining the eye/eye-slash toggle icons
print '<div class="saturne-filter-legend">';
print '<div class="saturne-filter-legend-items">';
print '<span class="saturne-filter-legend-include"><span class="far fa-eye"></span> Inclure</span>';
print '<span class="saturne-filter-legend-exclude"><span class="far fa-eye-slash"></span> Exclure</span>';
print '</div>';
print '</div>';

print $panelFilterBody;
print '</div>';

// Panel footer
print '<div class="saturne-filter-panel-footer">';
print '<button type="submit" class="butAction">' . dol_escape_htmltag($applyBtnLabel) . '</button>';
print '<a href="' . dol_escape_htmltag($_SERVER['PHP_SELF']) . '" class="butActionDelete">' . dol_escape_htmltag($resetBtnLabel) . '</a>';
print '</div>';

print '</div>'; // end #saturne-filter-panel

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
print '<table class="tagtable nobottomiftotal noborder liste' . ($moreForFilter ? ' listwithfilterbefore' : '') . '">';
print '<thead>';
