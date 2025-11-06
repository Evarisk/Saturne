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
//if ($groupby != '') {
//    $param .= '&groupby=' . urlencode($groupby);
//}
if (!empty($formMoreParams)) {
    foreach ($formMoreParams as $formMoreParamKey => $formMoreParamVal) {
        $param .= $param .= '&' . $formMoreParamKey . '=' . urlencode($formMoreParamVal);
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
//print '<input type="hidden" name="groupby" value="' . $groupby . '">';
if (!empty($formMoreParams)) {
    foreach ($formMoreParams as $formMoreParamKey => $formMoreParamVal) {
        print '<input type="hidden" name="' . $formMoreParamKey . '" value="' . $formMoreParamVal . '">';
    }
}

$newCardButton ?? '';
$newCardButton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', $_SERVER['PHP_SELF'] . '?mode=common' . preg_replace('/([&?])*mode=[^&]+/', '', $param), '', ((empty($mode) || $mode == 'common') ? 2 : 1), ['morecss' => 'reposition']);
$newCardButton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', $_SERVER['PHP_SELF'] . '?mode=kanban' . preg_replace('/([&?])*mode=[^&]+/', '', $param), '', ($mode == 'kanban' ? 2 : 1), ['morecss' => 'reposition']);
$newCardButton .= dolGetButtonTitle($langs->trans('ViewPwa'), '', 'fa fa-mobile imgforviewmode', $_SERVER['PHP_SELF'] . '?mode=pwa' . preg_replace('/([&?])*mode=[^&]+/', '', $param), '', ($mode == 'pwa' ? 2 : 1), ['morecss' => 'reposition']);
$newCardButton .= dolGetButtonTitleSeparator();
$newCardButton .= dolGetButtonTitle($langs->trans('New' . ucfirst($object->element)), $helpText ?? '', 'fa fa-plus-circle', ($createUrl ?? dol_buildpath('custom/' . $object->module . '/view/' . $object->element . '/' . $object->element . '_card.php', 1) . '?action=create' . ($moreUrlParameters ?? '')), '', $permissiontoadd);

print_barre_liste((($conf->browser->layout == 'classic' && $mode != 'pwa') ? $title : ' '), $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, $massActionButton, $num, $nbTotalOfRecords, $object->picto, 0, $newCardButton, '', $limit, 0, 0, 1);

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

$moreForFilter = '';
if (isModEnabled('categorie') && $user->hasRight('categorie', 'read') && isset($categorie->MAP_OBJ_CLASS[$object->element])) {
    require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcategory.class.php';
    $formCategory   = new FormCategory($db);
    $moreForFilter .= $formCategory->getFilterBox($object->element, $searchCategories);
}

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

$selectedFields = '';
if ($mode != 'pwa' && $mode != 'kanban') {
    $varPage        = $contextpage ?: $_SERVER['PHP_SELF'];
    $selectedFields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varPage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN'));
}
if (!empty($arrayOfMassActions)) {
    $selectedFields .= $form->showCheckAddButtons('checkforselect', 1);
}

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
print '<table class="tagtable nobottomiftotal noborder liste' . ($moreForFilter ? ' listwithfilterbefore' : '') . '">';
