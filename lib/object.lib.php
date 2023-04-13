<?php
/* Copyright (C) 2022-2023 EVARISK <technique@evarisk.com>
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
 * \file    lib/object.lib.php
 * \ingroup saturne
 * \brief   Library files with common functions for Saturne Object
 */

/**
 * Load list of objects in memory from the database.
 *
 * @param  string      $className  Object className
 * @param  string      $sortorder  Sort Order
 * @param  string      $sortfield  Sort field
 * @param  int         $limit      Limit
 * @param  int         $offset     Offset
 * @param  array       $filter     Filter array. Example array('field'=>'value', 'customurl'=>...)
 * @param  string      $filtermode Filter mode (AND/OR)
 * @return int|array               0 < if KO, array of pages if OK
 * @throws Exception
 */
function saturne_fetch_all_object_type(string $className = '', string $sortorder = '', string $sortfield = '', int $limit = 0, int $offset = 0, array $filter = [], string $filtermode = 'AND')
{
    dol_syslog(__METHOD__, LOG_DEBUG);

    global $db;

    $object = new $className($db);

    $records = [];

    $sql = 'SELECT ';
    $sql .= $object->getFieldList('t');
    $sql .= ' FROM ' . MAIN_DB_PREFIX . $object->table_element . ' as t';
    if (isset($object->ismultientitymanaged) && $object->ismultientitymanaged == 1) {
        $sql .= ' WHERE entity IN (' . getEntity($object->table_element) . ')';
    } else {
        $sql .= ' WHERE 1 = 1';
    }
    // Manage filter
    $sqlwhere = [];
    if (count($filter) > 0) {
        foreach ($filter as $key => $value) {
            if ($key == 't.rowid') {
                $sqlwhere[] = $key . ' = ' . $value;
            } elseif (in_array($object->fields[$key]['type'], ['date', 'datetime', 'timestamp'])) {
                $sqlwhere[] = $key .' = \'' . $object->db->idate($value) . '\'';
            } elseif ($key == 'customsql') {
                $sqlwhere[] = $value;
            } elseif (strpos($value, '%') === false) {
                $sqlwhere[] = $key .' IN (' . $object->db->sanitize($object->db->escape($value)) . ')';
            } else {
                $sqlwhere[] = $key .' LIKE \'%' . $object->db->escape($value) . '%\'';
            }
        }
    }
    if (count($sqlwhere) > 0) {
        $sql .= ' AND (' . implode(' ' . $filtermode . ' ', $sqlwhere) . ')';
    }

    if (!empty($sortfield)) {
        $sql .= $object->db->order($sortfield, $sortorder);
    }
    if (!empty($limit)) {
        $sql .= ' ' . $object->db->plimit($limit, $offset);
    }

    $resql = $object->db->query($sql);
    if ($resql) {
        $num = $object->db->num_rows($resql);
        $i = 0;
        while ($i < ($limit ? min($limit, $num) : $num)) {
            $obj = $object->db->fetch_object($resql);

            $record = new $className($db);
            $record->setVarsFromFetchObj($obj);

            $records[$record->id] = $record;

            $i++;
        }
        $object->db->free($resql);

        return $records;
    } else {
        $object->errors[] = 'Error ' . $object->db->lasterror();
        dol_syslog(__METHOD__ . ' ' . join(',', $object->errors), LOG_ERR);

        return -1;
    }
}

/**
 * Prepare array of tabs for Object.
 *
 * @param  CommonObject $object            Object.
 * @param  bool         $showAttendantsTab Show attendants tab.
 * @param  bool         $showNoteTab       Show note tab.
 * @param  bool         $showDocumentTab   Show document tab.
 * @param  bool         $showAgendaTab     Show agenda tab.
 * @return array                           Array of tabs.
 * @throws Exception
 */
function saturne_object_prepare_head(CommonObject $object, bool $showAttendantsTab = false, bool $showNoteTab = true, bool $showDocumentTab = true, bool $showAgendaTab = true): array
{
    // Global variables definitions.
    global $conf, $db, $moduleName, $moduleNameLowerCase, $langs, $user;

    // Load translation files required by the page.
    saturne_load_langs();

    // Initialize values.
    $h          = 0;
    $head       = [];
    $objectType = $object->element;

    if ($user->rights->$moduleNameLowerCase->$objectType->read) {
        $head[$h][0] = dol_buildpath('/' . $moduleNameLowerCase . '/view/' . $objectType . '/' . $objectType . '_card.php', 1) . '?id=' . $object->id;
        $head[$h][1] = '<i class="fas fa-info-circle pictofixedwidth"></i>' . $langs->trans(ucfirst($objectType));
        $head[$h][2] = 'card';
        $h++;

        if ($showAttendantsTab) {
            // Libraries
            require_once __DIR__ . '/../class/saturnesignature.class.php';

            // Initialize technical objects
            $signatory = new SaturneSignature($db);

            $signatoriesArray = $signatory->fetchSignatories($object->id, $objectType);
            if (is_array($signatoriesArray) && !empty($signatoriesArray)) {
                $nbAttendants = count($signatoriesArray);
            } else {
                $nbAttendants = 0;
            }

            $head[$h][0] = dol_buildpath('/' . $moduleNameLowerCase . '/view/saturne_attendants.php', 1) . '?id=' . $object->id . '&module_name=' . $moduleName . '&object_type=' . $objectType;
            $head[$h][1] = '<i class="fas fa-file-signature pictofixedwidth"></i>' . $langs->trans('Attendants');
            if ($nbAttendants > 0) {
                $head[$h][1] .= '<span class="badge marginleftonlyshort">' . $nbAttendants . '</span>';
            }
            $head[$h][2] = 'attendants';
            $h++;
        }

        if ((isset($object->fields['note_public']) || isset($object->fields['note_private'])) && $showNoteTab) {
            $nbNote = 0;
            if (!empty($object->note_private)) {
                $nbNote++;
            }
            if (!empty($object->note_public)) {
                $nbNote++;
            }
            $head[$h][0] = dol_buildpath('/saturne/view/saturne_note.php', 1) . '?id=' . $object->id . '&module_name=' . $moduleName . '&object_type=' . $objectType;
            $head[$h][1] = '<i class="fas fa-comment pictofixedwidth"></i>' . $langs->trans('Notes');
            if ($nbNote > 0) {
                $head[$h][1] .= (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '<span class="badge marginleftonlyshort">' . $nbNote . '</span>' : '');
            }
            $head[$h][2] = 'note';
            $h++;
        }

        if ($showDocumentTab) {
            require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
            require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';
            $upload_dir = $conf->$moduleNameLowerCase->dir_output . '/' . $objectType . '/' . dol_sanitizeFileName($object->ref);
            $nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
            $nbLinks = Link::count($db, $objectType, $object->id);
            $head[$h][0] = dol_buildpath('/saturne/view/saturne_document.php', 1) . '?id=' . $object->id . '&module_name=' . $moduleName . '&object_type=' . $objectType;
            $head[$h][1] = '<i class="fas fa-file-alt pictofixedwidth"></i>' . $langs->trans('Documents');
            if (($nbFiles + $nbLinks) > 0) {
                $head[$h][1] .= '<span class="badge marginleftonlyshort">' . ($nbFiles + $nbLinks) . '</span>';
            }
            $head[$h][2] = 'document';
            $h++;
        }

        if ($showAgendaTab) {
            $head[$h][0] = dol_buildpath('/saturne/view/saturne_agenda.php', 1) . '?id=' . $object->id . '&module_name=' . $moduleName . '&object_type=' . $objectType;
            $head[$h][1] = '<i class="fas fa-calendar-alt pictofixedwidth"></i>' . $langs->trans('Events');
            if (isModEnabled('agenda') && (!empty($user->rights->agenda->myactions->read) || !empty($user->rights->agenda->allactions->read))) {
                $nbEvent = 0;
                // Enable caching of object type count actioncomm
                require_once DOL_DOCUMENT_ROOT . '/core/lib/memory.lib.php';
                $cacheKey = 'count_events_' . $objectType . '_' . $object->id;
                $dataRetrieved = dol_getcache($cacheKey);
                if (!is_null($dataRetrieved)) {
                    $nbEvent = $dataRetrieved;
                } else {
                    $sql = 'SELECT COUNT(id) as nb';
                    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'actioncomm';
                    $sql .= ' WHERE fk_element = ' . $object->id;
                    $sql .= " AND elementtype = '" . $objectType . '@' . $moduleNameLowerCase . "'";
                    $resql = $db->query($sql);
                    if ($resql) {
                        $obj = $db->fetch_object($resql);
                        $nbEvent = $obj->nb;
                    } else {
                        dol_syslog('Failed to count actioncomm ' . $db->lasterror(), LOG_ERR);
                    }
                    dol_setcache($cacheKey, $nbEvent, 120); // If setting cache fails, this is not a problem, so we do not test result.
                }
                $head[$h][1] .= '/';
                $head[$h][1] .= $langs->trans('Agenda');
                if ($nbEvent > 0) {
                    $head[$h][1] .= '<span class="badge marginleftonlyshort">' . $nbEvent . '</span>';
                }
            }
            $head[$h][2] = 'agenda';
            $h++;
        }
    }

    complete_head_from_modules($conf, $langs, $object, $head, $h, $objectType . '@' . $moduleNameLowerCase);

    complete_head_from_modules($conf, $langs, $object, $head, $h, $objectType . '@' . $moduleNameLowerCase, 'remove');

    return $head;
}
