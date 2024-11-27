<?php
/* Copyright (C) 2024 EVARISK <technique@evarisk.com>
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
 * Global   : $db, $hookmanager, $langs
 * Variable : $arrayfields, $form, $object, $search
 */

// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre_filter">';

// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
    print '<td class="liste_titre center maxwidthsearch">';
    $searchPicto = $form->showFilterButtons('left');
    print $searchPicto;
    print '</td>';
}

foreach ($object->fields as $key => $val) {
    $cssForField = (empty($val['csslist']) ? (empty($val['css']) ? '' : $val['css']) : $val['csslist']);
    if ($key == 'status') {
        $cssForField .= ' center';
    } elseif (isset($val['type']) && in_array($val['type'], ['date', 'datetime', 'timestamp'])) {
        $cssForField .= ' center';
    } elseif (isset($val['type']) && in_array($val['type'], ['double(24,8)', 'double(6,3)', 'integer', 'real', 'price']) && !in_array($key, ['id', 'rowid', 'ref', 'status']) && $val['label'] != 'TechnicalID' && empty($val['arrayofkeyval'])) {
        $cssForField .= ' right';
    }
    if (!empty($arrayfields['t.'.$key]['checked'])) {
        print '<td class="liste_titre' . ($cssForField ? ' ' . $cssForField : '') . ($key == 'status' ? ' parentonrightofpage' : '') . '">';
        if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
            print $form->selectarray('search_' . $key, $val['arrayofkeyval'], $search[$key] ?? '', 1, 0, 0, '', 1, 0, 0, '', 'maxwidth100' . ($key == 'status' ? ' search_status width100 onrightofpage' : ''));
        } elseif ((strpos($val['type'], 'integer:') === 0) || (strpos($val['type'], 'sellist:') === 0)) {
            print $object->showInputField($val, $key, $search[$key] ?? '', '', '', 'search_', $cssForField.' maxwidth250', 1);
        } elseif (preg_match('/^(date|timestamp|datetime)/', $val['type'])) {
            print '<div class="nowrap">';
            print $form->selectDate($search[$key . '_dtstart'] ?? '', 'search_' . $key . '_dtstart', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
            print '</div>';
            print '<div class="nowrap">';
            print $form->selectDate($search[$key . '_dtend'] ?? '', 'search_' . $key . '_dtend', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
            print '</div>';
        } elseif ($key == 'lang') {
            require_once DOL_DOCUMENT_ROOT . '/core/class/html.formadmin.class.php';
            $formAdmin = new FormAdmin($db);
            print $formAdmin->select_language(($search[$key] ?? ''), 'search_lang', 0, [], 1, 0, 0, 'minwidth100imp maxwidth125', 2);
        }  elseif ($key == 'fk_element') {
            print $digiriskelement->selectDigiriskElementList($search['fk_element'], 'search_fk_element', ['customsql' => 'rowid NOT IN (' . implode(',', $deletedElements) . ')'], 1, 0, [], 0, 0, 'minwidth100 maxwidth300', 0, false, 1);
        } elseif ($key == 'category') { ?>
            <div class="wpeo-dropdown dropdown-large dropdown-grid category-danger padding" style="position: inherit">
                <input class="input-hidden-danger" type="hidden" name="<?php echo 'search_' . $key ?>" value="<?php echo dol_escape_htmltag($search[$key]) ?>" />
                <?php if (dol_strlen(dol_escape_htmltag($search[$key])) == 0) : ?>
                    <div class="dropdown-toggle dropdown-add-button button-cotation">
                        <span class="wpeo-button button-square-50 button-grey"><i class="fas fa-exclamation-triangle button-icon"></i></span>
                        <img class="danger-category-pic wpeo-tooltip-event hidden" src="" aria-label=""/>
                    </div>
                <?php else : ?>
                    <div class="dropdown-toggle dropdown-add-button button-cotation wpeo-tooltip-event" aria-label="<?php echo (empty(dol_escape_htmltag($search[$key]))) ? $risk->getDangerCategoryName($risk) : $risk->getDangerCategoryNameByPosition($search[$key]); ?>">
                        <img class="danger-category-pic tooltip hover" src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . ((empty(dol_escape_htmltag($search[$key]))) ? $risk->getDangerCategory($risk) : $risk->getDangerCategoryByPosition($search[$key])) . '.png'?>" />
                    </div>
                <?php endif; ?>
                <ul class="saturne-dropdown-content wpeo-gridlayout grid-5 grid-gap-0">
                    <?php
                    if ( ! empty($dangerCategories) ) :
                        foreach ($dangerCategories as $dangerCategory) : ?>
                            <li class="item dropdown-item wpeo-tooltip-event classfortooltip" data-is-preset="<?php echo ''; ?>" data-id="<?php echo $dangerCategory['position'] ?>" aria-label="<?php echo $dangerCategory['name'] ?>">
                                <img src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $dangerCategory['thumbnail_name'] . '.png'?>" class="attachment-thumbail size-thumbnail photo photowithmargin" alt="" loading="lazy" width="48" height="48">
                            </li>
                        <?php endforeach;
                    endif; ?>
                </ul>
            </div>
        <?php } else {
            print '<input type="text" class="flat maxwidth' . (in_array($val['type'], ['integer', 'price']) ? '50' : '75') . '" name="search_' . $key . '" value="' . dol_escape_htmltag($search[$key] ?? '') . '">';
        }
        print '</td>';
    }
}

//@todo spec
//foreach ($evaluation->fields as $key => $val) {
//    $cssforfield                        = (empty($val['csslist']) ? (empty($val['css']) ? '' : $val['css']) : $val['csslist']);
//    if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
//    if ( ! empty($arrayfields['evaluation.' . $key]['checked'])) {
//        print '<td class="liste_titre' . '">';
//        print '</td>';
//    }
//}

// Extra fields
require_once DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters = ['arrayfields' => $arrayfields];
$hookmanager->executeHooks('printFieldListOption', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
    print '<td class="liste_titre center maxwidthsearch">';
    $searchPicto = $form->showFilterButtons('left');
    print $searchPicto;
    print '</td>';
}

print '</tr>';
