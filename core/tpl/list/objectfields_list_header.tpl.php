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
            if (is_array($val) ? !empty($val) : ($val !== '' && $val !== '-1')) {
                $filterCount++;
            }
        }
    }
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
