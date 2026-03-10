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
 * \file    core/tpl/list/objectfields_list_loop_object.tpl.php
 * \ingroup saturne
 * \brief   Template page for object fields list loop on object
 */

/**
 * The following vars must be defined :
 * Globals    : $conf (extrafields_list_print_fields.tpl), $db, $hookmanager
 * Parameters : $action, $limit, $massaction, $massActionButton, $mode
 * Objects    : $extrafields (extrafields_list_print_fields.tpl), $object
 * Variables  : $arrayfields, $arrayofselected, $num, $resql, $statusMode (optional), $totalarray
 */

// Loop on record
// --------------------------------------------------------------------
$i                     = 0;
$savNbField            = $totalarray['nbfield']; // +1
$totalarray            = [];
$totalarray['nbfield'] = 0;
$iMaxInLoop            = ($limit ? min($num, $limit) : $num);
while ($i < $iMaxInLoop) {
    $obj = $db->fetch_object($resql);
    if (empty($obj)) {
        break; // Should not happen
    }

    // Store properties in $object
    $object->setVarsFromFetchObj($obj);

    /*
    $object->thirdparty = null;
    if ($obj->fk_soc > 0) {
        if (!empty($conf->cache['thirdparty'][$obj->fk_soc])) {
            $companyobj = $conf->cache['thirdparty'][$obj->fk_soc];
        } else {
            $companyobj = new Societe($db);
            $companyobj->fetch($obj->fk_soc);
            $conf->cache['thirdparty'][$obj->fk_soc] = $companyobj;
        }

        $object->thirdparty = $companyobj;
    }*/

    $parameters = [];
    $hookmanager->executeHooks('saturneSetVarsFromFetchObj', $parameters, $object);

    if ($mode == 'kanban') {
        if ($i == 0) {
            print '<tr class="trkanban"><td colspan="' . $savNbField . '">';
            print '<div class="box-flex-container kanban">';
        }
        // Output Kanban
        $selected = -1;
        if ($massActionButton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
            $selected = 0;
            if (in_array($object->id, $arrayofselected)) {
                $selected = 1;
            }
        }
        print $object->getKanbanView('', ['selected' => $selected]);
        if ($i == ($iMaxInLoop - 1)) {
            print '</div>';
            print '</td></tr>';
        }
    } else {
        // Show line of result
        print '<tr data-rowid="' . $object->id . '" class="oddeven">';

        // Action column
        if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
            print '<td class="nowrap center">';
            if ($massActionButton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
                $selected = 0;
                if (in_array($object->id, $arrayofselected)) {
                    $selected = 1;
                }
                print '<input id="cb' . $object->id . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $object->id . '"' . ($selected ? ' checked="checked"' : '') . '>';
            }
            print '</td>';
            if (!$i) {
                $totalarray['nbfield']++;
            }
        }

        // Fields
        foreach ($object->fields as $key => $val) {
            $cssForField = saturne_css_for_field($val, $key);
            if (!empty($arrayfields['t.' . $key]['checked'])) {
                print '<td' . ($cssForField ? ' class="' . $cssForField . ((preg_match('/tdoverflow/', $cssForField) && !in_array($val['type'], ['ip', 'url']) && !is_numeric($object->$key)) ? ' classfortooltip' : '') . '"' : '');
                if (preg_match('/tdoverflow/', $cssForField) && !in_array($val['type'], ['ip', 'url']) && !is_numeric($object->$key) && $key != 'ref') {
                    print ' title="' . dol_escape_htmltag($object->$key) . '"';
                }
                print '>';

                $parameters = ['arrayfields' => $arrayfields, 'key' => $key, 'val' => $val];
                $hookmanager->executeHooks('saturnePrintFieldListLoopObject', $parameters, $object);
                if (!empty($hookmanager->resArray[$key])) {
                    print $hookmanager->resArray[$key];
                    continue;
                }

                if ($key == 'status') {
                    print $object->getLibStatut($statusMode ?? 5);
                } elseif ($key == 'rowid') {
                    print $object->showOutputField($val, $key, $object->id);
                } else {
                    if (!empty($val['contenteditable']) && $val['contenteditable'] == 1) {
                        $ceType   = !empty($val['type']) && in_array($val['type'], ['date', 'datetime']) ? 'datepicker' : 'text';
                        $ceDataType = ' data-type="' . $ceType . '"';
                        print '<div class="contenteditable-wrap"><div class="contenteditable" contenteditable="true" data-field="' . $key . '" data-id="' . $object->id . '"' . $ceDataType . ' data-success="Enregistré" data-error="Format invalide">';
                    }
                    print $object->showOutputField($val, $key, $object->$key);
                    if (!empty($val['contenteditable']) && $val['contenteditable'] == 1) {
                        $isDateField = !empty($val['type']) && in_array($val['type'], ['date', 'datetime']);
                        $calBtn = $isDateField ? '
  <button class="contenteditable-cal-btn" type="button" title="Ouvrir le calendrier">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
      <rect x="3" y="4" width="18" height="18" rx="2"/>
      <line x1="16" y1="2" x2="16" y2="6"/>
      <line x1="8"  y1="2" x2="8"  y2="6"/>
      <line x1="3"  y1="10" x2="21" y2="10"/>
    </svg>
  </button>' : '';
                        print '</div>' . $calBtn . '
  <div class="contenteditable-icon">
    <svg class="icon-check" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
    <svg class="icon-x"     viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
  </div>
  <div class="contenteditable-message"></div>
</div>';
                    }
                }
                print '</td>';

                if (!$i) {
                    $totalarray['nbfield']++;
                }
                if (!empty($val['isameasure']) && $val['isameasure'] == 1) {
                    if (!$i) {
                        $totalarray['pos'][$totalarray['nbfield']]  = 't.' . $key;
                        $totalarray['type'][$totalarray['nbfield']] = $val['type'];
                    }
                    if (!isset($totalarray['val'])) {
                        $totalarray['val'] = [];
                    }
                    if (!isset($totalarray['val']['t.'.$key])) {
                        $totalarray['val']['t.' . $key] = 0;
                    }
                    $totalarray['val']['t.' . $key] += $object->$key;
                }
            }
        }

        // Extra fields
        require DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_print_fields.tpl.php';

        // Fields from hook
        $parameters = ['arrayfields' => $arrayfields, 'object' => $object, 'obj' => $obj, 'i' => $i, 'totalarray' => &$totalarray];
        $hookmanager->executeHooks('printFieldListValue', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;

        // Action column
        if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
            print '<td class="nowrap center">';
            if ($massActionButton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
                $selected = 0;
                if (in_array($object->id, $arrayofselected)) {
                    $selected = 1;
                }
                print '<input id="cb' . $object->id . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $object->id . '"' . ($selected ? ' checked="checked"' : '') . '>';
            }
            print '</td>';
            if (!$i) {
                $totalarray['nbfield']++;
            }
        }
        print '</tr>';
    }
    $i++;
}
