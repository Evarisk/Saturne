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
 * Variables  : $arrayfields, $createUrl (optional), $enableMassSignature (optional), $enableMassValidate (optional),
 *              $fieldsToSearchAll, $formMoreParams (optional), $helpText (optional), $nbTotalOfRecords, $num,
 *              $permissiontoadd, $resql, $search, $search_array_options (extrafields_list_search_param.tpl),
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
}

// Category criteria lives outside $search : the SQL TPL normalized it, add it to $param so pagination and sort
// links keep the filter. It is empty when the category module is off or the list TPL was included on its own
$searchCategories = (isset($searchCategories) && is_array($searchCategories)) ? $searchCategories : [];
foreach ($searchCategories as $searchCategory) {
    $param .= '&search_categories_filter[]=' . urlencode($searchCategory);
}

// Add $param from extra fields
require_once DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_param.tpl.php';

// Add $param from hooks
$parameters = ['param' => &$param];
$hookmanager->executeHooks('printFieldListSearchParam', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$param .= $hookmanager->resPrint;

// List of mass actions available
$arrayOfMassActions = [];

// Mass validation is opt-in: the list page must set $enableMassValidate, every object is not meant to be validated in bulk
if (!empty($enableMassValidate) && !empty($permissiontoadd)) {
    $validatePicto = '<span class="fas fa-check paddingrightonly"></span>';
    $arrayOfMassActions['prevalidate'] = $validatePicto . $langs->trans('Validate');
}

// Mass signature is opt-in: the list page must set $enableMassSignature, only objects carrying signatories are signed
if (!empty($enableMassSignature) && !empty($permissiontoadd)) {
    $arrayOfMassActions['presign']       = '<span class="fas fa-file-signature paddingrightonly"></span>' . $langs->trans('Sign');
    $arrayOfMassActions['signattendant'] = '<span class="fas fa-user-edit paddingrightonly"></span>' . $langs->trans('SignForAnAttendant');
}

$arrayOfMassActions += [
    //'generate_doc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("ReGeneratePDF"),
    //'builddoc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
    //'presend'=>img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
    'prearchive' => '<span class="fas fa-archive paddingrightonly"></span>' . $langs->trans('Archive'),
    'preunarchive' => '<span class="fas fa-box-open paddingrightonly"></span>' . $langs->trans('Unarchive')
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

// Apply the per-user column layout (order + widths) + filter mode saved for this list.
// Per-module list controllers (control_list.php, question_list.php, ...) reuse these TPLs without
// running saturne_list.php's layout logic, so applying it here makes the saved layout effective
// everywhere. saturne_list.php applies it before including this TPL, hence the isset() guards.
$listLayoutId = $listLayoutId ?? ($object->element ?? '');
if (!isset($listColumnWidths)) {
    $listColumnWidths = saturne_apply_list_layout($object, $arrayfields, $listLayoutId);
}
if (!isset($useSideFilterPanel)) {
    $useSideFilterPanel = (saturne_get_list_filter_mode($listLayoutId) === 'panel');
}

// Apply user column preferences to $arrayfields now, so $panelFilterBody and all loops below use correct checked values
$selectedFields = '';
if ($mode != 'pwa' && $mode != 'kanban') {
    $varPage        = $contextpage ?: $_SERVER['PHP_SELF'];
    $selectedFields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varPage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN'));
}

// Build side filter panel content has been removed

$listLayoutId = $listLayoutId ?? ($object->element ?? '');
if ($mode != 'kanban' && $mode != 'pwa' && !empty($listLayoutId)) {
    // Count active filters on table columns
    $filterCount = 0;
    if (isset($search) && is_array($search)) {
        foreach ($search as $key => $val) {
            if (is_array($val)) {
                continue; // Skip array values like status (which has defaults)
            }
            if ($val !== '' && $val !== '-1') {
                $filterCount++;
            }
        }
    }
    if (isset($search_array_options) && is_array($search_array_options)) {
        foreach ($search_array_options as $val) {
            if (is_array($val) ? !empty($val) : ($val !== null && $val !== '' && $val !== '-1')) {
                $filterCount++;
            }
        }
    }
    $filterCount += count($searchCategories);
    if (!empty($searchAll)) $filterCount++;
    $hasFilter = ($filterCount > 0);

    // Funnel SVG icon (16×16 Feather-style, GPL-compatible)
    $svgIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="' . ($hasFilter ? '#e67e22' : '#666') . '" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;">';
    $svgIcon .= '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>';
    if ($hasFilter) {
        $svgIcon .= '<line x1="16" y1="16" x2="22" y2="22" stroke="#e74c3c" stroke-width="2"></line><line x1="22" y1="16" x2="16" y2="22" stroke="#e74c3c" stroke-width="2"></line>';
    }
    $svgIcon .= '</svg>';

    // Build the filter indicator: funnel icon (clickable when filters active) + count badge
    $filterIndicator = '<span class="saturne-filter-indicator" style="margin-left: 10px; display: inline-flex; align-items: center; gap: 4px; vertical-align: middle;">';
    if ($hasFilter) {
        // Build reset URL — direct GET navigation
        $clearUrl = $_SERVER['PHP_SELF'] . '?button_removefilter_x=1&button_removefilter=1&token=' . newToken();
        if (!empty($formMoreParams)) {
            foreach ($formMoreParams as $k => $v) {
                $clearUrl .= '&' . urlencode($k) . '=' . urlencode($v);
            }
        }
        // Wrap the SVG icon in a clickable link
        $filterIndicator .= '<a href="' . dol_escape_htmltag($clearUrl) . '" onclick="window.location.href=this.href;return false;" title="' . dol_escape_htmltag($langs->trans('RemoveFilter')) . '" style="text-decoration: none; display: inline-flex; align-items: center;">' . $svgIcon . '</a>';
        // Counter only — no text
        $filterIndicator .= ' <span style="color: #e67e22; font-weight: 600; font-size: 0.85em;">(' . $filterCount . ')</span>';
    } else {
        $filterIndicator .= $svgIcon;
    }
    $filterIndicator .= '</span>';

    $cardButton = ($cardButton ?? '') . ' ' . $filterIndicator;
}

// "New" button to create a new object
$cardButton = ($cardButton ?? '') . ' ' . dolGetButtonTitle($langs->trans('New' . ucfirst($object->element)), $helpText ?? '', 'fa fa-plus-circle', ($createUrl ?? dol_buildpath('custom/' . $object->module . '/view/' . $object->element . '/' . $object->element . '_card.php', 1) . '?action=create' . ($moreUrlParameters ?? '')), '', $permissiontoadd);

// Format the title text — record count BEFORE filter indicator
$titleText = ($conf->browser->layout == 'classic' && $mode != 'pwa') ? ($title ?? '') : '';
$displayRecordCount = (isset($nbTotalOfRecords) && $nbTotalOfRecords !== '' && is_numeric($nbTotalOfRecords)) ? $nbTotalOfRecords : ($num ?? null);
if ($displayRecordCount !== null && is_numeric($displayRecordCount)) {
    $titleText .= ' <span class="opacitymedium colorblack marginleftonly">(' . $displayRecordCount . ')</span>';
    $nbTotalOfRecords = -1; // Prevent print_barre_liste from appending it again
}
$listTitle = $titleText . ' ' . ($cardButton ?? '');

// Add selectedFields back to the title bar on the right, or hidden if we don't want the gear icon.
// Since Saturne removed it to put it in the side panel, we must put it back so checkboxes exist!
$newCardButton = ($newCardButton ?? '') . ' <div style="display:none;" id="saturne-hidden-column-selector">' . $selectedFields . '</div>';

// Hook: full-width banner above the list title bar (KPI cards, view presets, ...), rendered outside the title/filter header
$parameters = ['arrayfields' => &$arrayfields];
$hookmanager->executeHooks('saturneListTopBanner', $parameters, $object, $action);
if (!empty($hookmanager->resPrint)) {
    print '<div class="saturne-list-top-banner">' . $hookmanager->resPrint . '</div>';
}

print_barre_liste($listTitle, ($page ?? 0), $_SERVER['PHP_SELF'], ($param ?? ''), ($sortfield ?? ''), ($sortorder ?? ''), ($massActionButton ?? ''), ($num ?? 0), ($nbTotalOfRecords ?? 0), ($object->picto ?? ''), 0, ($newCardButton ?? ''), '', ($limit ?? 0), 0, 0, 1);

// Add code for pre mass action (confirmation or email presend form)
//$topicmail = "SendMyObjectRef";
//$modelmail = "myobject";
//$objecttmp = new MyObject($db);
//$trackid = 'xxxx'.$object->id;

require_once DOL_DOCUMENT_ROOT . '/core/tpl/massactions_pre.tpl.php';

if ($massaction == 'prevalidate') {
    print $form->formconfirm($_SERVER['PHP_SELF'], $langs->trans('ConfirmMassValidate'), $langs->trans('ConfirmMassValidatingQuestion', count($toselect)), 'validate', null, '', 0, 200, 500, 1);
}

if ($massaction == 'presign') {
    print $form->formconfirm($_SERVER['PHP_SELF'], $langs->trans('ConfirmMassSign'), $langs->trans('ConfirmMassSigningQuestion', count($toselect)), 'sign', null, '', 0, 200, 500, 1);
}

if ($massaction == 'prearchive') {
    print $form->formconfirm($_SERVER['PHP_SELF'], $langs->trans('ConfirmMassArchive'), $langs->trans('ConfirmMassArchivingQuestion', count($toselect)), 'archive', null, '', 0, 200, 500, 1);
}

if ($massaction == 'preunarchive') {
    print $form->formconfirm($_SERVER['PHP_SELF'], $langs->trans('ConfirmMassUnarchive'), $langs->trans('ConfirmMassUnarchivingQuestion', count($toselect)), 'unarchive', null, '', 0, 200, 500, 1);
}

if ($searchAll) {
    foreach ($fieldsToSearchAll as $key => $val) {
        $fieldsToSearchAll[$key] = $langs->trans($val);
    }
    print '<div class="divsearchfieldfilter">' . $langs->trans('FilterOnInto', $searchAll) . implode(', ', $fieldsToSearchAll) . '</div>';
}

$moreForFilter = '';

// Filter on categories, for every object registered in the category map by a constructCategory hook.
// Selected categories are rendered as tags whose sign toggles include/exclude, driven by
// window.saturne.filter.initCategoryPicker() which binds on the [data-cat-colors] container below
if (isModEnabled('categorie') && $user->hasRight('categorie', 'read') && isset($categorie) && isset($categorie->MAP_OBJ_CLASS[$object->element]) && $mode != 'kanban' && $mode != 'pwa') {
    require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcategory.class.php';

    // Defaults mirror the SQL TPL, which owns these tokens and normally runs first
    $categoryNotCategorizedToken = $categoryNotCategorizedToken ?? 'NOTCATEGORIZED';
    $categoryFallbackColor       = $categoryFallbackColor ?? '#95a5a6';

    $langs->load('categories');

    $formCategory = new FormCategory($db);
    // outputmode 2 returns the full tree with the category colors
    $rawCategories = $formCategory->select_all_categories($object->element, '', '', 64, 0, 2);

    $categoryMap = [];
    if (is_array($rawCategories)) {
        foreach ($rawCategories as $rawCategory) {
            $categoryColor                         = !empty($rawCategory['color']) ? '#' . ltrim($rawCategory['color'], '#') : $categoryFallbackColor;
            $categoryMap[(int) $rawCategory['id']] = ['label' => $rawCategory['fulllabel'], 'color' => $categoryColor];
        }
    }

    // Tags already selected, kept in the order they were added
    $categoryTags   = [];
    $categoryTagIds = [];
    foreach ($searchCategories as $searchCategory) {
        if (ltrim((string) $searchCategory, '+-') === $categoryNotCategorizedToken) {
            $categoryTags[]   = [
                'id'    => $categoryNotCategorizedToken,
                'label' => $langs->trans('NotCategorized'),
                'color' => $categoryFallbackColor,
                'mode'  => (strpos($searchCategory, '-') === 0) ? 'exc' : 'inc'
            ];
            $categoryTagIds[] = $categoryNotCategorizedToken;
            continue;
        }

        $categoryId = abs((int) $searchCategory);
        if ($categoryId > 0 && isset($categoryMap[$categoryId])) {
            $categoryTags[]   = [
                'id'    => $categoryId,
                'label' => $categoryMap[$categoryId]['label'],
                'color' => $categoryMap[$categoryId]['color'],
                'mode'  => ((int) $searchCategory < 0) ? 'exc' : 'inc'
            ];
            $categoryTagIds[] = $categoryId;
        }
    }

    $categoryElementId = dol_escape_htmltag($object->element);
    $categoryColorsJs  = json_encode(array_map(function ($categoryData) {
        return $categoryData['color'];
    }, $categoryMap));
    $categoryIcon      = img_picto('', 'category', 'class="saturne-cat-icon"');

    $moreForFilter .= '<div class="divsearchfield saturne-cat-filter">';
    $moreForFilter .= '<a class="unsetcolor" href="' . DOL_URL_ROOT . '/categories/categorie_list.php?mode=hierarchy&type=' . urlencode($object->element) . '&backtopage=' . urlencode($_SERVER['PHP_SELF']) . '">';
    $moreForFilter .= img_picto($langs->trans('Categories'), 'category', 'class="pictofixedwidth"');
    $moreForFilter .= '</a>';

    // Picker : picking an entry hands it over to the tag list, the JS then removes the option from here
    $moreForFilter .= '<span class="saturne-cat-filter-picker-wrapper">';
    $moreForFilter .= '<select id="cat_filter_picker_' . $categoryElementId . '" class="flat saturne-filter-cat-picker" title="' . dol_escape_htmltag($langs->trans('Categories')) . '">';
    $moreForFilter .= '<option value="">' . dol_escape_htmltag($langs->transnoentitiesnoconv('Category')) . '</option>';
    if (!in_array($categoryNotCategorizedToken, $categoryTagIds)) {
        $moreForFilter .= '<option value="' . $categoryNotCategorizedToken . '" data-color="' . $categoryFallbackColor . '">' . dol_escape_htmltag($langs->trans('NotCategorized')) . '</option>';
    }
    foreach ($categoryMap as $categoryId => $categoryData) {
        if (in_array($categoryId, $categoryTagIds)) {
            continue;
        }
        $moreForFilter .= '<option value="' . $categoryId . '" data-color="' . dol_escape_htmltag($categoryData['color']) . '">' . dol_escape_htmltag($categoryData['label']) . '</option>';
    }
    $moreForFilter .= '</select>';
    $moreForFilter .= '</span>';

    $moreForFilter .= '<div id="cat_filter_tags_' . $categoryElementId . '" class="saturne-cat-filter-tags" data-picker-id="cat_filter_picker_' . $categoryElementId . '" data-cat-icon="' . dol_escape_htmltag($categoryIcon) . '" data-cat-colors="' . dol_escape_htmltag($categoryColorsJs) . '">';
    foreach ($categoryTags as $categoryTag) {
        $isExcludedTag = $categoryTag['mode'] == 'exc';
        $tagSign       = $isExcludedTag ? '&minus;' : '+';
        $tagValue      = ($isExcludedTag ? '-' : '+') . $categoryTag['id'];

        $moreForFilter .= '<span class="saturne-cat-tag" style="border-color:' . $categoryTag['color'] . '" data-catid="' . dol_escape_htmltag($categoryTag['id']) . '" data-mode="' . $categoryTag['mode'] . '" data-label="' . dol_escape_htmltag($categoryTag['label']) . '" data-color="' . dol_escape_htmltag($categoryTag['color']) . '">';
        $moreForFilter .= '<span class="cat-sign saturne-cat-tag-sign" title="' . dol_escape_htmltag($langs->trans('Categories')) . '" style="background:' . $categoryTag['color'] . '">' . $categoryIcon . ' ' . $tagSign . '</span>';
        $moreForFilter .= '<span class="saturne-cat-tag-body">';
        $moreForFilter .= '<span class="saturne-cat-tag-label' . ($isExcludedTag ? ' is-exc' : '') . '">' . dol_escape_htmltag($categoryTag['label']) . '</span>';
        $moreForFilter .= '<span class="cat-remove saturne-cat-tag-remove" title="' . dol_escape_htmltag($langs->trans('Remove')) . '">&times;</span>';
        $moreForFilter .= '</span>';
        $moreForFilter .= '<input type="hidden" name="search_categories_filter[]" value="' . dol_escape_htmltag($tagValue) . '">';
        $moreForFilter .= '</span>';
    }
    $moreForFilter .= '</div>';
    $moreForFilter .= '</div>';
}

// Hook: extra content above the list (moreForFilter)
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

// Side filter panel removed

// Preserve non-visible search parameters as hidden inputs so they survive form submissions
foreach ($search as $key => $val) {
    if (array_key_exists($key, $object->fields) && empty($arrayfields['t.' . $key]['checked']) && $val !== '' && !is_array($val)) {
        print '<input type="hidden" name="search_' . $key . '" value="' . dol_escape_htmltag($val) . '">';
    }
}

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
print '<table class="tagtable nobottomiftotal noborder liste' . ($moreForFilter ? ' listwithfilterbefore' : '') . '" data-list-layout-id="' . dol_escape_htmltag($listLayoutId) . '">';
print '<thead>';
