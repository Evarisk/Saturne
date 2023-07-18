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

	$objectFields = $object->getFieldList('t');
	if (strstr($objectFields, 't.fk_prospectlevel')) {
		$objectFields = preg_replace('/t.fk_prospectlevel,/','', $objectFields);
	}
    $sql = 'SELECT ';
    $sql .= $objectFields;
    $sql .= ' FROM `' . MAIN_DB_PREFIX . $object->table_element . '` as t';
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
 * @param  array        $head              Tab menu entry.
 * @param  array        $moreparam         More parameters.
 * @param  bool         $showAttendantsTab Show attendants tab.
 * @param  bool         $showNoteTab       Show note tab.
 * @param  bool         $showDocumentTab   Show document tab.
 * @param  bool         $showAgendaTab     Show agenda tab.
 * @return array                           Array of tabs.
 * @throws Exception
 */
function saturne_object_prepare_head(CommonObject $object, $head = [], array $moreparam = [], bool $showAttendantsTab = false, bool $showNoteTab = true, bool $showDocumentTab = true, bool $showAgendaTab = true): array
{
    // Global variables definitions.
    global $conf, $db, $moduleName, $moduleNameLowerCase, $langs, $user;

    // Load translation files required by the page.
    saturne_load_langs();

    // Initialize values.
    $h          = 0;
    $objectType = $object->element;

    if ($user->rights->$moduleNameLowerCase->$objectType->read) {
        $head[$h][0] = dol_buildpath('/' . $moduleNameLowerCase . '/view/' . $objectType . '/' . $objectType . '_card.php', 1) . '?id=' . $object->id;
        $head[$h][1] = '<i class="fas fa-info-circle pictofixedwidth"></i>' . $langs->trans(ucfirst($objectType));
        $head[$h][2] = 'card';
        $h = $h + 10;

        if ($showAttendantsTab) {
            // Libraries
            require_once __DIR__ . '/../class/saturnesignature.class.php';

            // Initialize technical objects
            $signatory = new SaturneSignature($db, $moduleNameLowerCase);

            $signatoriesArray = $signatory->fetchSignatories($object->id, $objectType);
            if (is_array($signatoriesArray) && !empty($signatoriesArray)) {
                $nbAttendants = count($signatoriesArray);
            } else {
                $nbAttendants = 0;
            }

            $head[$h][0] = dol_buildpath('/saturne/view/saturne_attendants.php', 1) . '?id=' . $object->id . '&module_name=' . $moduleName . '&object_type=' . $objectType . '&document_type=' . (!empty($moreparam['documentType']) ? $moreparam['documentType'] : '') . '&attendant_table_mode=' . (empty($moreparam['attendantTableMode']) ? 'advanced' : $moreparam['attendantTableMode']);
            $head[$h][1] = '<i class="fas fa-file-signature pictofixedwidth"></i>' . $langs->trans((empty($moreparam['attendantTabName']) ? 'Attendants' : $moreparam['attendantTabName']));
            if ($nbAttendants > 0) {
                $head[$h][1] .= '<span class="badge marginleftonlyshort">' . $nbAttendants . '</span>';
            }
            $head[$h][2] = 'attendants';
            $h = $h + 10;
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
            $h = $h + 10;
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
            $h = $h + 10;
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
            ksort($head);
        }

        complete_head_from_modules($conf, $langs, $object, $head, $h, $objectType . '@' . $moduleNameLowerCase);

        complete_head_from_modules($conf, $langs, $object, $head, $h, $objectType . '@' . $moduleNameLowerCase, 'remove');
    }

    return $head;
}

/**
 * Get list of objects and their linked class and other infos
 *
 * @param  string    $type Object type to get the metadata from
 * @return array           Array of objects with infos
 * @throws Exception
 */
function get_objects_metadata(string $type = ''): array
{
    global $db, $hookmanager, $langs;

    //To add an object :

    //	'mainmenu'      => Object main menu
    //	'leftmenu'      => Object left menu
    //	'langs'         => Object translation
    //	'langfile'      => File lang translation
    //	'picto'         => Object picto for img_picto() function (equals $this->picto)
    //	'class_name'    => Class name
    //	'name_field'    => Object name to be shown (ref, label, firstname, etc.)
    //	'post_name'     => Name of post sent retrieved by GETPOST() function
    //	'link_name'     => Name of object sourcetype in llx_element_element
    //	'tab_type'      => Tab type element for prepare_head function
    //	'fk_parent'     => OPTIONAL : Name of parent for objects as productlot, contact, task
    //	'parent_post'   => OPTIONAL : Name of parent post (retrieved by GETPOST() function, it can be different from fk_parent
    //	'create_url'    => Path to creation card, no need to add "?action=create"
    //	'class_path'    => Path to object class
    //	'lib_path'      => Path to object lib

    $objectsMetadataType = [];

    if (isModEnabled('product')) {
        $objectsMetadataType['product'] = [
            'mainmenu'      => 'products',
            'leftmenu'      => 'product',
            'langs'         => 'ProductOrService',
            'langfile'      => 'products',
            'picto'         => 'product',
            'class_name'    => 'Product',
            'post_name'     => 'fk_product',
            'link_name'     => 'product',
            'tab_type'      => 'product',
            'table_element' => 'product',
            'name_field'    => 'ref',
            'create_url'    => 'product/card.php',
            'class_path'    => 'product/class/product.class.php',
            'lib_path'      => 'core/lib/product.lib.php',
        ];
    }

    if (isModEnabled('productbatch')) {
        $objectsMetadataType['productlot'] = [
            'mainmenu'      => '',
            'leftmenu'      => '',
            'langs'         => 'Batch',
            'langfile'      => 'products',
            'picto'         => 'lot',
            'class_name'    => 'ProductLot',
            'post_name'     => 'fk_productlot',
            'link_name'     => 'productbatch',
            'tab_type'      => 'productlot',
            'table_element' => 'product_batch',
            'name_field'    => 'batch',
            'fk_parent'     => 'fk_product',
            'parent_post'   => 'fk_product',
            'create_url'    => 'product/stock/productlot_card.php',
            'class_path'    => 'product/stock/class/productlot.class.php',
            'lib_path'      => 'core/lib/product.lib.php',
        ];
    }

    if (isModEnabled('user')) {
        $objectsMetadataType['user'] = [
            'mainmenu'      => 'user',
            'leftmenu'      => 'users',
            'langs'         => 'User',
            'picto'         => 'user',
            'class_name'    => 'User',
            'post_name'     => 'fk_user',
            'link_name'     => 'user',
            'tab_type'      => 'user',
            'table_element' => 'user',
            'name_field'    => 'lastname, firstname',
            'create_url'    => 'user/card.php',
            'class_path'    => 'user/class/user.class.php',
            'lib_path'      => 'core/lib/usergroups.lib.php',
        ];
    }

    if (isModEnabled('societe')) {
        $objectsMetadataType['thirdparty'] = [
            'mainmenu'      => 'companies',
            'leftmenu'      => 'thirdparties',
            'langs'         => 'ThirdParty',
            'langfile'      => 'companies',
            'picto'         => 'building',
            'class_name'    => 'Societe',
            'post_name'     => 'fk_soc',
            'link_name'     => 'societe',
            'tab_type'      => 'thirdparty',
            'table_element' => 'societe',
            'name_field'    => 'nom',
            'create_url'    => 'societe/card.php',
            'class_path'    => 'societe/class/societe.class.php',
            'lib_path'      => 'core/lib/company.lib.php',
        ];
        $objectsMetadataType['contact'] = [
            'mainmenu'      => 'companies',
            'leftmenu'      => 'contacts',
            'langs'         => 'Contact',
            'langfile'      => 'companies',
            'picto'         => 'address',
            'class_name'    => 'Contact',
            'post_name'     => 'fk_contact',
            'link_name'     => 'contact',
            'tab_type'      => 'contact',
            'table_element' => 'socpeople',
            'name_field'    => 'lastname, firstname',
            'fk_parent'     => 'fk_soc',
            'parent_post'   => 'fk_soc',
            'create_url'    => 'contact/card.php',
            'class_path'    => 'contact/class/contact.class.php',
            'lib_path'      => 'core/lib/contact.lib.php',
        ];
    }

    if (isModEnabled('project')) {
        $objectsMetadataType['project'] = [
            'mainmenu'      => 'project',
            'leftmenu'      => 'projects',
            'langs'         => 'Project',
            'langfile'      => 'projects',
            'picto'         => 'project',
            'class_name'    => 'Project',
            'post_name'     => 'fk_project',
            'link_name'     => 'project',
            'tab_type'      => 'project',
            'table_element' => 'projet',
            'name_field'    => 'ref, title',
            'create_url'    => 'projet/card.php',
            'class_path'    => 'projet/class/project.class.php',
            'lib_path'      => 'core/lib/project.lib.php',
        ];
        $objectsMetadataType['task'] = [
            'mainmenu'      => 'project',
            'leftmenu'      => 'tasks',
            'langs'         => 'Task',
            'langfile'      => 'projects',
            'picto'         => 'projecttask',
            'class_name'    => 'Task',
            'post_name'     => 'fk_task',
            'link_name'     => 'project_task',
            'tab_type'      => 'task',
            'table_element' => 'projet_task',
            'name_field'    => 'label',
            'fk_parent'     => 'fk_projet',
            'parent_post'   => 'fk_project',
            'create_url'    => 'projet/tasks.php',
            'class_path'    => 'projet/class/task.class.php',
            'lib_path'      => 'core/lib/project.lib.php',
        ];
    }

    if (isModEnabled('facture')) {
        $objectsMetadataType['invoice'] = [
            'mainmenu'      => 'billing',
            'leftmenu'      => 'customers_bills',
            'langs'         => 'Invoice',
            'langfile'      => 'bills',
            'picto'         => 'bill',
            'class_name'    => 'Facture',
            'post_name'     => 'fk_invoice',
            'link_name'     => 'facture',
            'tab_type'      => 'invoice',
            'table_element' => 'facture',
            'name_field'    => 'ref',
            'create_url'    => 'compta/facture/card.php',
            'class_path'    => 'compta/facture/class/facture.class.php',
            'lib_path'      => 'core/lib/invoice.lib.php',
        ];
    }

    if (isModEnabled('order')) {
        $objectsMetadataType['order'] = [
            'mainmenu'      => 'billing',
            'leftmenu'      => 'orders',
            'langs'         => 'Order',
            'langfile'      => 'orders',
            'picto'         => 'order',
            'class_name'    => 'Commande',
            'post_name'     => 'fk_order',
            'link_name'     => 'commande',
            'tab_type'      => 'order',
            'table_element' => 'commande',
            'name_field'    => 'ref',
            'create_url'    => 'commande/card.php',
            'class_path'    => 'commande/class/commande.class.php',
            'lib_path'      => 'core/lib/order.lib.php',
        ];
    }

    if (isModEnabled('contract')) {
        $objectsMetadataType['contract'] = [
            'mainmenu'      => 'commercial',
            'leftmenu'      => 'contracts',
            'langs'         => 'Contract',
            'langfile'      => 'contracts',
            'picto'         => 'contract',
            'class_name'    => 'Contrat',
            'post_name'     => 'fk_contract',
            'link_name'     => 'contrat',
            'tab_type'      => 'contract',
            'table_element' => 'contrat',
            'name_field'    => 'ref',
            'create_url'    => 'contrat/card.php',
            'class_path'    => 'contrat/class/contrat.class.php',
            'lib_path'      => 'core/lib/contract.lib.php',
        ];
    }

    if (isModEnabled('ticket')) {
        $objectsMetadataType['ticket'] = [
            'mainmenu'      => 'ticket',
            'leftmenu'      => 'ticket',
            'langs'         => 'Ticket',
            'picto'         => 'ticket',
            'class_name'    => 'Ticket',
            'post_name'     => 'fk_ticket',
            'link_name'     => 'ticket',
            'tab_type'      => 'ticket',
            'table_element' => 'ticket',
            'name_field'    => 'ref, subject',
            'create_url'    => 'ticket/card.php',
            'class_path'    => 'ticket/class/ticket.class.php',
            'lib_path'      => 'core/lib/contact.lib.php',
        ];
    }

    if (isModEnabled('stock')) {
        $objectsMetadataType['entrepot'] = [
            'mainmenu'      => 'products',
            'leftmenu'      => 'stock',
            'langs'         => 'Warehouse',
            'langfile'      => 'stocks',
            'picto'         => 'stock',
            'class_name'    => 'Entrepot',
            'post_name'     => 'fk_entrepot',
            'link_name'     => 'stock',
            'tab_type'      => 'stock',
            'table_element' => 'entrepot',
            'name_field'    => 'ref',
            'create_url'    => 'product/stock/entrepot/card.php',
            'class_path'    => 'product/stock/class/entrepot.class.php',
            'lib_path'      => 'core/lib/stock.lib.php',
        ];
    }

    if (isModEnabled('expedition')) {
        $objectsMetadataType['expedition'] = [
            'mainmenu'      => 'products',
            'leftmenu'      => 'sendings',
            'langs'         => 'Shipments',
            'langfile'      => 'sendings',
            'picto'         => 'dolly',
            'class_name'    => 'Expedition',
            'post_name'     => 'fk_expedition',
            'link_name'     => 'expedition',
            'tab_type'      => 'delivery',
            'table_element' => 'expedition',
            'name_field'    => 'ref',
            'class_path'    => 'expedition/class/expedition.class.php',
            'lib_path'      => 'core/lib/expedition.lib.php',
        ];
    }

    if (isModEnabled('propal')) {
        $objectsMetadataType['propal'] = [
            'mainmenu'      => 'commercial',
            'leftmenu'      => 'propals',
            'langs'         => 'Proposal',
            'langfile'      => 'propal',
            'picto'         => 'propal',
            'class_name'    => 'Propal',
            'post_name'     => 'fk_propal',
            'link_name'     => 'propal',
            'tab_type'      => 'propal',
            'table_element' => 'propal',
            'name_field'    => 'ref',
            'create_url'    => 'comm/propal/card.php',
            'class_path'    => 'comm/propal/class/propal.class.php',
            'lib_path'      => 'core/lib/propal.lib.php',
        ];
    }

    // Hook to add controllable objects from other modules
    if ( ! is_object($hookmanager)) {
        include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
        $hookmanager = new HookManager($db);
    }
    $hookmanager->initHooks(['get_objects_metadata']);

    $reshook = $hookmanager->executeHooks('extendGetObjectsMetadata', $objectsMetadataType);

    if ($reshook && (is_array($hookmanager->resArray) && !empty($hookmanager->resArray))) {
        $objectsMetadataType = $hookmanager->resArray;
    }

    $objectsMetadata = [];
    if (is_array($objectsMetadataType) && !empty($objectsMetadataType)) {
        foreach($objectsMetadataType as $objectMetadata => $objectMetadataInformations) {
            if ($objectMetadata != 'context' && $objectMetadata != 'currentcontext') {
                require_once DOL_DOCUMENT_ROOT . '/' . $objectMetadataInformations['class_path'];
                require_once DOL_DOCUMENT_ROOT . '/' . $objectMetadataInformations['lib_path'];

                $objectsMetadata[$objectMetadata] = [
                    'name'          => ucfirst($objectMetadata),
                    'mainmenu'      => $objectMetadataInformations['mainmenu'] ?? '',
                    'leftmenu'      => $objectMetadataInformations['leftmenu'] ?? '',
                    'langs'         => $objectMetadataInformations['langs'] ?? '',
                    'langfile'      => $objectMetadataInformations['langfile'] ?? '',
                    'picto'         => $objectMetadataInformations['picto'] ?? '',
                    'class_name'    => $objectMetadataInformations['class_name'] ?? '',
                    'name_field'    => $objectMetadataInformations['name_field'] ?? '',
                    'post_name'     => $objectMetadataInformations['post_name'] ?? '',
                    'link_name'     => $objectMetadataInformations['link_name'] ?? '',
                    'tab_type'      => $objectMetadataInformations['tab_type'] ?? '',
                    'table_element' => $objectMetadataInformations['table_element'] ?? '',
                    'fk_parent'     => $objectMetadataInformations['fk_parent'] ?? '',
                    'parent_post'   => $objectMetadataInformations['parent_post'] ?? '',
                    'create_url'    => $objectMetadataInformations['create_url'] ?? '',
                    'class_path'    => $objectMetadataInformations['class_path'] ?? '',
                    'lib_path'      => $objectMetadataInformations['lib_path'] ?? '',
                ];
                if (!empty($objectMetadataInformations['langfile'])) {
                    $langs->load($objectMetadataInformations['langfile']);
                }
            }
        }
    }

    return dol_strlen($type) > 0 ? $objectsMetadata[$type] : $objectsMetadata;
}
