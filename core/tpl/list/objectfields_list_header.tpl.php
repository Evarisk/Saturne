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
    $panelFilterBody .= '<div style="margin-bottom:16px;padding-bottom:16px;border-bottom:1px solid #f0f0f0">';
    $panelFilterBody .= '<input type="text" class="flat" name="search_all" id="panel_search_all" placeholder="' . dol_escape_htmltag($searchAllPlaceholder) . '" value="' . dol_escape_htmltag($searchAll ?? '') . '" style="width:100%;box-sizing:border-box;padding:8px 10px;border:1px solid #ddd;border-radius:6px;font-size:13px">';
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
    $catIcon     = img_picto('', 'category', 'style="width:12px;height:12px;vertical-align:middle;color:inherit;filter:brightness(10)"');
    $catIconJs   = json_encode($catIcon);

    $panelFilterBody .= '<div style="margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid #f0f0f0">';
    $panelFilterBody .= '<div style="font-size:11px;text-transform:uppercase;color:#888;font-weight:600;letter-spacing:.5px;margin-bottom:10px">'
        . img_picto($langs->trans('Categories'), 'category', 'class="pictofixedwidth"')
        . ' ' . $langs->trans('Categories') . '</div>';

    // Picker (full-width inside panel)
    $panelFilterBody .= '<select id="cat_filter_picker_' . $elementId . '" class="flat" style="width:100%;margin-bottom:8px" title="' . dol_escape_htmltag($langs->trans('AddCategory')) . '">';
    $panelFilterBody .= '<option value="">&nbsp;</option>';
    foreach ($categoryMap as $catId => $catData) {
        if (in_array($catId, $initialTagCatIds)) {
            continue;
        }
        $panelFilterBody .= '<option value="' . $catId . '" data-color="' . dol_escape_htmltag($catData['color']) . '">' . dol_escape_htmltag($catData['label']) . '</option>';
    }
    $panelFilterBody .= '</select>';

    // Tag list
    $panelFilterBody .= '<div id="cat_filter_tags_' . $elementId . '" style="display:flex;flex-wrap:wrap;gap:6px;min-height:4px">';
    foreach ($initialTags as $tag) {
        $isExcTag = $tag['mode'] === 'exc';
        $color    = $tag['color'];
        $sign     = $isExcTag ? '&minus;' : '+';
        $tagVal   = ($isExcTag ? '-' : '+') . $tag['id'];
        $panelFilterBody .= '<span style="display:inline-flex;align-items:stretch;border-radius:20px;overflow:hidden;border:2px solid ' . $color . ';box-shadow:0 1px 4px rgba(0,0,0,.15);cursor:default;user-select:none;font-size:12px;line-height:1"';
        $panelFilterBody .= ' data-catid="' . $tag['id'] . '" data-mode="' . $tag['mode'] . '" data-label="' . dol_escape_htmltag($tag['label']) . '" data-color="' . dol_escape_htmltag($color) . '">';
        $panelFilterBody .= '<span class="cat-sign" title="' . dol_escape_htmltag($langs->trans('ToggleIncludeExclude')) . '" style="display:flex;align-items:center;gap:3px;padding:4px 8px;background:' . $color . ';color:#fff;cursor:pointer;font-weight:bold">' . $catIcon . ' ' . $sign . '</span>';
        $panelFilterBody .= '<span style="display:flex;align-items:center;gap:6px;padding:4px 8px;background:#fff;color:#333"><span style="max-width:140px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis' . ($isExcTag ? ';text-decoration:line-through' : '') . '">' . dol_escape_htmltag($tag['label']) . '</span>';
        $panelFilterBody .= '<span class="cat-remove" title="' . dol_escape_htmltag($langs->trans('Remove')) . '" style="cursor:pointer;color:#aaa;font-size:14px;line-height:1;font-weight:bold" onmouseover="this.style.color=\'#333\'" onmouseout="this.style.color=\'#aaa\'">&times;</span></span>';
        $panelFilterBody .= '<input type="hidden" name="search_categories_filter[]" value="' . dol_escape_htmltag($tagVal) . '">';
        $panelFilterBody .= '</span>';
    }
    $panelFilterBody .= '</div>';

    $panelFilterBody .= '<script>(function(){var picker=document.getElementById("cat_filter_picker_' . $elementId . '");var tags=document.getElementById("cat_filter_tags_' . $elementId . '");var catIcon=' . $catIconJs . ';var catColors=' . $catColorsJs . ';var FALLBACK="#95a5a6";function esc(s){return s.replace(/[<>&"]/g,function(c){return{"<":"&lt;",">":"&gt;","&":"&amp;","\"":"&quot;"}[c];});}function getColor(id){return catColors[id]||FALLBACK;}if(typeof jQuery!=="undefined"&&jQuery.fn.select2){jQuery(picker).select2({width:"100%",dropdownParent:jQuery("body"),templateResult:function(o){if(!o.id)return o.text;var c=jQuery(o.element).data("color")||FALLBACK;return jQuery("<span>").append(jQuery("<span>").css({display:"inline-block",width:"10px",height:"10px",borderRadius:"50%",background:c,marginRight:"6px",verticalAlign:"middle"}),document.createTextNode(o.text));},templateSelection:function(o){if(!o.id)return o.text;var c=jQuery(o.element).data("color")||FALLBACK;return jQuery("<span>").append(jQuery("<span>").css({display:"inline-block",width:"10px",height:"10px",borderRadius:"50%",background:c,marginRight:"6px",verticalAlign:"middle"}),document.createTextNode(o.text));}}).on("select2:select",function(e){var o=e.params.data;buildTag(o.id,o.text,"inc");jQuery(picker).val("").trigger("change.select2");});}else{picker.addEventListener("change",function(){var o=picker.options[picker.selectedIndex];if(!o.value)return;buildTag(o.value,o.text,"inc");picker.selectedIndex=0;});}function removePO(id){var o=picker.querySelector("option[value=\""+id+"\"]");if(o)o.remove();if(typeof jQuery!=="undefined"&&jQuery.fn.select2)jQuery(picker).trigger("change.select2");}function restorePO(id,lbl,col){if(picker.querySelector("option[value=\""+id+"\"]"))return;var o=document.createElement("option");o.value=id;o.text=lbl;o.dataset.color=col;picker.appendChild(o);if(typeof jQuery!=="undefined"&&jQuery.fn.select2)jQuery(picker).trigger("change.select2");}function renderTag(id,lbl,col,mode){var exc=mode==="exc";var sign=exc?"\u2212":"+";var ls="max-width:140px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"+(exc?";text-decoration:line-through":"");return"<span class=\"cat-sign\" style=\"display:flex;align-items:center;gap:3px;padding:4px 8px;background:"+col+";color:#fff;cursor:pointer;font-weight:bold\">"+catIcon+" "+sign+"</span>"+"<span style=\"display:flex;align-items:center;gap:6px;padding:4px 8px;background:#fff;color:#333\">"+"<span style=\""+ls+"\">"+esc(lbl)+"</span>"+"<span class=\"cat-remove\" style=\"cursor:pointer;color:#aaa;font-size:14px;line-height:1;font-weight:bold\">\u00d7</span>"+"</span>"+"<input type=\"hidden\" name=\"search_categories_filter[]\" value=\""+(exc?"-":"+")+id+"\">";}function buildTag(id,lbl,mode){if(tags.querySelector("[data-catid=\""+id+"\"]"))return;var col=getColor(id);var s=document.createElement("span");s.dataset.catid=id;s.dataset.mode=mode;s.dataset.label=lbl;s.dataset.color=col;s.style.cssText="display:inline-flex;align-items:stretch;border-radius:20px;overflow:hidden;border:2px solid "+col+";box-shadow:0 1px 4px rgba(0,0,0,.15);cursor:default;user-select:none;font-size:12px;line-height:1";s.innerHTML=renderTag(id,lbl,col,mode);removePO(id);bindTag(s);tags.appendChild(s);}function bindTag(s){s.querySelector(".cat-sign").addEventListener("click",function(e){e.stopPropagation();var m=s.dataset.mode==="inc"?"exc":"inc";s.dataset.mode=m;s.innerHTML=renderTag(s.dataset.catid,s.dataset.label,s.dataset.color,m);bindTag(s);});var r=s.querySelector(".cat-remove");r.addEventListener("mouseover",function(){r.style.color="#333";});r.addEventListener("mouseout",function(){r.style.color="#aaa";});r.addEventListener("click",function(e){e.stopPropagation();restorePO(s.dataset.catid,s.dataset.label,s.dataset.color);s.remove();});}tags.querySelectorAll("[data-catid]").forEach(bindTag);}());</script>';

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

    $panelFilterBody .= '<div style="margin-bottom:16px">';
    $panelFilterBody .= '<div style="font-size:12px;color:#555;font-weight:500;margin-bottom:5px">' . dol_escape_htmltag($fieldLabelPanel) . '</div>';
    $panelFilterBody .= '<div style="display:flex;align-items:center;gap:4px;width:100%">';

    if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
        $showToggle = ($key !== 'status');
        if ($showToggle) {
            $fMode  = GETPOST('search_' . $key . '_mode', 'alpha') ?: 'inc';
            $isExc  = ($fMode === 'exc');
            $tAttr  = dol_escape_htmltag($fieldLabelPanel) . ' - ' . $toggleTitlePanel;
            $panelFilterBody .= '<input type="hidden" id="search_' . $key . '_mode" name="search_' . $key . '_mode" value="' . ($isExc ? 'exc' : 'inc') . '">';
            $panelFilterBody .= '<span id="search_mode_toggle_' . $key . '" title="' . $tAttr . '" onclick="var i=document.getElementById(\'search_' . $key . '_mode\'),exc=i.value!==\'exc\';i.value=exc?\'exc\':\'inc\';this.innerHTML=exc?\'<span class=\\\'far fa-eye-slash\\\'></span>\':\'<span class=\\\'far fa-eye\\\'></span>\';this.style.color=exc?\'#c0392b\':\'#666\';" style="cursor:pointer;font-size:13px;padding:0 4px;flex-shrink:0;user-select:none;color:' . ($isExc ? '#c0392b' : '#666') . '">' . ($isExc ? '<span class="far fa-eye-slash"></span>' : '<span class="far fa-eye"></span>') . '</span>';
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
        $panelFilterBody .= '<span id="search_mode_toggle_' . $key . '" title="' . $tAttr . '" onclick="var i=document.getElementById(\'search_' . $key . '_mode\'),exc=i.value!==\'exc\';i.value=exc?\'exc\':\'inc\';this.innerHTML=exc?\'<span class=\\\'far fa-eye-slash\\\'></span>\':\'<span class=\\\'far fa-eye\\\'></span>\';this.style.color=exc?\'#c0392b\':\'#666\';" style="cursor:pointer;font-size:13px;padding:0 4px;flex-shrink:0;user-select:none;color:' . ($isExc ? '#c0392b' : '#666') . '">' . ($isExc ? '<span class="far fa-eye-slash"></span>' : '<span class="far fa-eye"></span>') . '</span>';
        ob_start();
        $showInputHtml = $object->showInputField($val, $key, $search[$key] ?? '', '', '', 'search_', $cssForFieldPanel . ' maxwidth200 saturne-panel-select', 1);
        ob_end_clean(); // discard ajax_combobox JS printed directly — panel will init select2 on open
        $panelFilterBody .= $showInputHtml;
    } elseif (isset($val['type']) && in_array($val['type'], ['date', 'datetime', 'timestamp'])) {
        $panelFilterBody .= '<div style="flex:1">'
            . '<div class="nowrap">' . $form->selectDate($search[$key . '_dtstart'] ?? '', 'search_' . $key . '_dtstart', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From')) . '</div>'
            . '<div class="nowrap">' . $form->selectDate($search[$key . '_dtend'] ?? '', 'search_' . $key . '_dtend', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to')) . '</div>'
            . '</div>';
    } elseif (isset($val['type']) && $val['type'] == 'duration') {
        $panelFilterBody .= '<div style="flex:1">'
            . '<div class="nowrap">' . $form->select_duration('search_' . $key . '_dtstart', $search[$key . '_dtstart'] ?? '', 0, 'text', 0, 1) . '</div>'
            . '<div class="nowrap">' . $form->select_duration('search_' . $key . '_dtend', $search[$key . '_dtend'] ?? '', 0, 'text', 0, 1) . '</div>'
            . '</div>';
    } elseif ($key == 'lang') {
        require_once DOL_DOCUMENT_ROOT . '/core/class/html.formadmin.class.php';
        $formAdmin        = new FormAdmin($db);
        $panelFilterBody .= $formAdmin->select_language(($search[$key] ?? ''), 'search_lang', 0, [], 1, 0, 0, 'minwidth100imp maxwidth200', 2);
    } else {
        $panelFilterBody .= '<input type="text" class="flat" style="flex:1;min-width:0" name="search_' . $key . '" value="' . dol_escape_htmltag($search[$key] ?? '') . '">';
    }

    $panelFilterBody .= '</div>';
    $panelFilterBody .= '</div>';
}

// Count active filters for badge on the toggle button
$activeFilterCount = 0;
foreach ($search as $key => $val) {
    if (!array_key_exists($key, $object->fields)) {
        continue;
    }
    if ($key === 'status' && ($val == -1 || $val === '')) {
        continue;
    }
    if (is_array($val) ? !empty($val) : $val !== '') {
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
$filterButton = dolGetButtonTitle($filterBtnLabel, '', 'fas fa-sliders-h', '#', 'saturne-filter-toggle', $activeFilterCount > 0 ? 2 : 1, ['morecss' => 'reposition', 'attr' => ['onclick' => 'saturneOpenFilter(); return false;']]);
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
<<<<<<< Updated upstream
if (isModEnabled('categorie') && $user->hasRight('categorie', 'read') && isset($categorie->MAP_OBJ_CLASS[$object->element])) {
    require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcategory.class.php';
    $formCategory   = new FormCategory($db);
    $moreForFilter .= $formCategory->getFilterBox($object->element, $searchCategories);
}

$parameters = ['arrayfields' => &$arrayfields];
$reshook    = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object, $action);
=======
$parameters    = ['arrayfields' => &$arrayfields];
$reshook       = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
>>>>>>> Stashed changes
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

<<<<<<< Updated upstream
// You can use div-table-responsive-no-min if you don't need reserved height for your table
print '<div class="div-table-responsive">';
=======
// Side filter panel (fixed overlay, inside the form)
// --------------------------------------------------------------------
print '<style>
#saturne-filter-panel .select2-container { width: 100% !important; }
#saturne-filter-panel .select2-container--open { z-index: 10002; }
#saturne-filter-panel .select2-dropdown { z-index: 10002 !important; }
.saturne-filter-select2-drop { z-index: 10002 !important; }
.saturne-filter-btn-active .fa-sliders-h { color: #3498db; }
</style>';
print '<div id="saturne-filter-backdrop" onclick="saturneCloseFilter()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:10000"></div>';
print '<div id="saturne-filter-panel" style="position:fixed;top:0;right:-400px;width:380px;height:100vh;background:#fff;box-shadow:-3px 0 24px rgba(0,0,0,.18);z-index:10001;transition:right .28s cubic-bezier(.4,0,.2,1);display:flex;flex-direction:column">';

// Panel header
print '<div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px 12px;border-bottom:1px solid #e8e8e8;background:#fafafa;flex-shrink:0">';
print '<strong style="font-size:15px;color:#333"><span class="fa fa-sliders-h" style="margin-right:8px;color:#666"></span>' . dol_escape_htmltag($filterBtnLabel) . '</strong>';
print '<span onclick="saturneCloseFilter()" style="cursor:pointer;font-size:22px;color:#999;line-height:1;padding:2px 6px;border-radius:4px;transition:background .15s" onmouseover="this.style.background=\'#eee\';this.style.color=\'#333\'" onmouseout="this.style.background=\'transparent\';this.style.color=\'#999\'">&times;</span>';
print '</div>';

// Panel body
print '<div style="flex:1;overflow-y:auto;padding:20px 20px 8px">';
print $panelFilterBody;
print '</div>';

// Panel footer
print '<div style="padding:14px 20px;border-top:1px solid #e8e8e8;display:flex;gap:10px;flex-shrink:0;background:#fafafa">';
print '<button type="submit" class="butAction" style="flex:1;padding:10px 0;font-size:14px">' . dol_escape_htmltag($applyBtnLabel) . '</button>';
print '<a href="' . dol_escape_htmltag($_SERVER['PHP_SELF']) . '" class="butActionDelete" style="flex:1;padding:10px 0;font-size:14px;text-align:center;text-decoration:none;display:flex;align-items:center;justify-content:center">' . dol_escape_htmltag($resetBtnLabel) . '</a>';
print '</div>';

print '</div>'; // end #saturne-filter-panel

print '<script>
function saturneOpenFilter() {
    document.getElementById("saturne-filter-backdrop").style.display = "block";
    document.getElementById("saturne-filter-panel").style.right = "0";
    document.body.style.overflow = "hidden";
    // Re-init select2 after panel is visible to fix 0-width and z-index issues
    if (typeof jQuery !== "undefined" && jQuery.fn.select2) {
        setTimeout(function() {
            jQuery("#saturne-filter-panel select").each(function() {
                var jq = jQuery(this);
                if (jq.data("select2")) {
                    jq.select2("destroy");
                }
                jq.select2({
                    width: "100%",
                    dropdownParent: jQuery("body"),
                    dropdownCssClass: "saturne-filter-select2-drop"
                });
            });
        }, 50);
    }
}
function saturneCloseFilter() {
    document.getElementById("saturne-filter-backdrop").style.display = "none";
    document.getElementById("saturne-filter-panel").style.right = "-400px";
    document.body.style.overflow = "";
}
document.addEventListener("keydown", function(e) { if (e.key === "Escape") saturneCloseFilter(); });
</script>';

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
>>>>>>> Stashed changes
print '<table class="tagtable nobottomiftotal noborder liste' . ($moreForFilter ? ' listwithfilterbefore' : '') . '">';
print '<thead>';
