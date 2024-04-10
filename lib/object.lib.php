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
 * Load list of objects in memory from the database
 *
 * @param  string     $className             Object className
 * @param  string     $sortorder             Sort Order
 * @param  string     $sortfield             Sort field
 * @param  int        $limit                 Limit
 * @param  int        $offset                Offset
 * @param  array      $filter                Filter array. Example array('field'=>'value', 'customurl'=>...)
 * @param  string     $filtermode            Filter mode (AND/OR)
 * @param  bool       $extraFieldManagement  Option for manage extrafields with LEFT JOIN SQL
 * @param  bool       $multiEntityManagement Option for manage multi entities with WHERE
 * @param  bool       $categoryManagement    Option for manage categories with LEFT JOIN SQL
 * @return int|array                         0 < if KO, array of pages if OK
 * @throws Exception
 */
function saturne_fetch_all_object_type(string $className = '', string $sortorder = '', string $sortfield = '', int $limit = 0, int $offset = 0, array $filter = [], string $filtermode = 'AND', bool $extraFieldManagement = false, bool $multiEntityManagement = true, bool $categoryManagement = false)
{
    dol_syslog(__METHOD__, LOG_DEBUG);

    global $db;

    $object = new $className($db);

    $records      = [];
    $optionsArray = [];

    if ($extraFieldManagement) {
        require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

        $extraFields = new ExtraFields($db);

        $extraFields->fetch_name_optionals_label($object->table_element);
        $optionsArray = (!empty($extraFields->attributes[$object->table_element]['label']) ? $extraFields->attributes[$object->table_element]['label'] : null);
    }

	$objectFields = $object->getFieldList('t');
	if (strstr($objectFields, 't.fk_prospectlevel')) {
		$objectFields = preg_replace('/t.fk_prospectlevel,/','', $objectFields);
	}
    if (is_array($optionsArray) && !empty($optionsArray) && $extraFieldManagement) {
        foreach ($optionsArray as $name => $label) {
            if (empty($extrafields->attributes[$object->table_element]['type'][$name]) || $extrafields->attributes[$object->table_element]['type'][$name] != 'separate') {
                $objectFields .= ", eft." . $name;
            }
        }
    }
    $sql = 'SELECT ';
    $sql .= $objectFields;
    $sql .= ' FROM `' . MAIN_DB_PREFIX . $object->table_element . '` as t';
    if ($extraFieldManagement) {
        $sql .= ' LEFT JOIN `' . MAIN_DB_PREFIX . $object->table_element . '_extrafields` as eft ON t.rowid = eft.fk_object';
    }
    if (isModEnabled('categorie') && $categoryManagement) {
        require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

        $sql .= Categorie::getFilterJoinQuery($object->element, 't.rowid');
    }
    if ($multiEntityManagement && isset($object->ismultientitymanaged) && $object->ismultientitymanaged == 1) {
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

            if (is_array($optionsArray) && !empty($optionsArray) && $extraFieldManagement) {
                foreach ($optionsArray as $key => $value) {
                    $record->array_options['options_' . $key] = $obj->$key;
                }
            }

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

    // This case will appear if module use saturne for manage head with hook each other
    if ($object->module !== $moduleNameLowerCase) {
        $moduleName          = $object->module;
        $moduleNameLowerCase = dol_strtolower($object->module);
    }

    if ($user->rights->$moduleNameLowerCase->$objectType->read) {
        $head[$h][0] = dol_buildpath('/' . $moduleNameLowerCase . '/view/' . (!empty($moreparam['parentType']) ? $moreparam['parentType'] : $objectType) . '/' . (!empty($moreparam['parentType']) ? $moreparam['parentType'] : $objectType) . '_card.php', 1) . '?id=' . $object->id . (!empty($moreparam['parentType']) ? '&object_type=' . $objectType : '');
        $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-info-circle pictofixedwidth"></i>' . $langs->trans((!empty($moreparam['specialName']) ? ucfirst($moreparam['specialName']) : ucfirst($objectType))) : '<i class="fas fa-info-circle"></i>';
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
            $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-file-signature pictofixedwidth"></i>' . $langs->trans((empty($moreparam['attendantTabName']) ? 'Attendants' : $moreparam['attendantTabName'])) : '<i class="fas fa-file-signature"></i>';
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
            $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-comment pictofixedwidth"></i>' . $langs->trans('Notes') : '<i class="fas fa-comment"></i>';
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
            $head[$h][0] = dol_buildpath('/saturne/view/saturne_document.php', 1) . '?id=' . $object->id . '&module_name=' . $moduleName . '&object_type=' . $objectType . (($moreparam['showNav'] >= 0) ? '&show_nav=' . $moreparam['showNav'] : 1) . ((dol_strlen($moreparam['handlePhoto']) > 0) ? '&handle_photo=' . $moreparam['handlePhoto'] : false);
            $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-file-alt pictofixedwidth"></i>' . $langs->trans('Documents') : '<i class="fas fa-file-alt"></i>';
            if (($nbFiles + $nbLinks) > 0) {
                $head[$h][1] .= '<span class="badge marginleftonlyshort">' . ($nbFiles + $nbLinks) . '</span>';
            }
            $head[$h][2] = 'document';
            $h = $h + 10;
        }

        if ($showAgendaTab) {
            $head[$h][0] = dol_buildpath('/saturne/view/saturne_agenda.php', 1) . '?id=' . $object->id . '&module_name=' . $moduleName . '&object_type=' . $objectType . (($moreparam['showNav'] >= 0) ? '&show_nav=' . $moreparam['showNav'] : 1) . ((dol_strlen($moreparam['handlePhoto']) > 0) ? '&handle_photo=' . $moreparam['handlePhoto'] : false);
            $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-calendar-alt pictofixedwidth"></i>' . $langs->trans('Events') . '/' . $langs->trans('Agenda') : '<i class="fas fa-calendar-alt"></i>';
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
 * @return array           Array of objects with metadata | empty array if type doesn't exist
 * @throws Exception
 */
function saturne_get_objects_metadata(string $type = ''): array
{
    global $db, $hookmanager, $langs;

    // To add an object :

    // 'mainmenu'       => Object main menu
    // 'leftmenu'       => Object left menu
    // 'langs'          => Object translation
    // 'langfile'       => File lang translation
    // 'picto'          => Object picto for img_picto() function (equals $this->picto)
    // 'color'          => Picto color
    // 'class_name'     => Class name
    // 'name_field'     => Object name to be shown (ref, label, firstname, etc.)
    // 'post_name'      => Name of post sent retrieved by GETPOST() function
    // 'link_name'      => Name of object sourcetype in llx_element_element
    // 'tab_type'       => Tab type element for prepare_head function
    // 'table_element'  => Object name in database
    // 'fk_parent'      => OPTIONAL : Name of parent for objects as productlot, contact, task
    // 'parent_post'    => OPTIONAL : Name of parent post (retrieved by GETPOST() function, it can be different from fk_parent
    // 'hook_name_card' => Hook name object card
    // 'hook_name_list' => Hook name object list
    // 'create_url'     => Path to creation card, no need to add "?action=create"
    // 'class_path'     => Path to object class
    // 'lib_path'       => Path to object lib

    $objectsMetadata = [];

    if (isModEnabled('product')) {
        $objectsMetadata['product'] = [
            'mainmenu'       => 'products',
            'leftmenu'       => 'product',
            'langs'          => 'ProductOrService',
            'langfile'       => 'products',
            'picto'          => 'product',
            'color'          => '#a69944',
            'class_name'     => 'Product',
            'post_name'      => 'fk_product',
            'link_name'      => 'product',
            'tab_type'       => 'product',
            'table_element'  => 'product',
            'name_field'     => 'ref',
            'hook_name_card' => 'productcard',
            'hook_name_list' => 'productservicelist',
            'create_url'     => 'product/card.php',
            'class_path'     => 'product/class/product.class.php',
            'lib_path'       => 'core/lib/product.lib.php',
        ];
    }

    if (isModEnabled('productbatch')) {
        $objectsMetadata['productlot'] = [
            'mainmenu'       => '',
            'leftmenu'       => '',
            'langs'          => 'Batch',
            'langfile'       => 'productbatch',
            'picto'          => 'lot',
            'color'          => '#a69944',
            'class_name'     => 'ProductLot',
            'post_name'      => 'fk_productlot',
            'link_name'      => 'productbatch',
            'tab_type'       => 'productlot',
            'table_element'  => 'product_lot',
            'name_field'     => 'batch',
            'fk_parent'      => 'fk_product',
            'parent_post'    => 'fk_product',
            'hook_name_card' => 'productlotcard',
            'hook_name_list' => 'product_lotlist',
            'create_url'     => 'product/stock/productlot_card.php',
            'class_path'     => 'product/stock/class/productlot.class.php',
            'lib_path'       => 'core/lib/product.lib.php',
        ];
    }

    if (isModEnabled('user')) {
        $objectsMetadata['user'] = [
            'mainmenu'       => '',
            'leftmenu'       => 'users',
            'langs'          => 'User',
            'picto'          => 'user',
            'color'          => '#79633f',
            'class_name'     => 'User',
            'post_name'      => 'fk_user',
            'link_name'      => 'user',
            'tab_type'       => 'user',
            'table_element'  => 'user',
            'name_field'     => 'lastname, firstname',
            'hook_name_card' => 'usercard',
            'hook_name_list' => 'userlist',
            'create_url'     => 'user/card.php',
            'class_path'     => 'user/class/user.class.php',
            'lib_path'       => 'core/lib/usergroups.lib.php',
        ];
    }

    if (isModEnabled('societe')) {
        $objectsMetadata['thirdparty'] = [
            'mainmenu'       => 'companies',
            'leftmenu'       => 'thirdparties',
            'langs'          => 'ThirdParty',
            'langfile'       => 'companies',
            'picto'          => 'building',
            'color'          => '#6c6aa8',
            'class_name'     => 'Societe',
            'post_name'      => 'fk_soc',
            'link_name'      => 'societe',
            'tab_type'       => 'thirdparty',
            'table_element'  => 'societe',
            'name_field'     => 'nom',
            'hook_name_card' => 'thirdpartycard',
            'hook_name_list' => 'thirdpartylist',
            'create_url'     => 'societe/card.php',
            'class_path'     => 'societe/class/societe.class.php',
            'lib_path'       => 'core/lib/company.lib.php',
        ];
        $objectsMetadata['contact'] = [
            'mainmenu'       => '',
            'leftmenu'       => 'contacts',
            'langs'          => 'Contact',
            'langfile'       => 'companies',
            'picto'          => 'address',
            'color'          => '#6c6aa8',
            'class_name'     => 'Contact',
            'post_name'      => 'fk_contact',
            'link_name'      => 'contact',
            'tab_type'       => 'contact',
            'table_element'  => 'socpeople',
            'name_field'     => 'lastname, firstname',
            'fk_parent'      => 'fk_soc',
            'parent_post'    => 'fk_soc',
            'hook_name_card' => 'contactcard',
            'hook_name_list' => 'contactlist',
            'create_url'     => 'contact/card.php',
            'class_path'     => 'contact/class/contact.class.php',
            'lib_path'       => 'core/lib/contact.lib.php',
        ];
    }

    if (isModEnabled('project')) {
        $objectsMetadata['project'] = [
            'mainmenu'       => 'project',
            'leftmenu'       => 'projects',
            'langs'          => 'Project',
            'langfile'       => 'projects',
            'picto'          => 'project',
            'color'          => '#6c6aa8',
            'class_name'     => 'Project',
            'post_name'      => 'fk_project',
            'link_name'      => 'project',
            'tab_type'       => 'project',
            'name_field'     => 'ref, title',
            'hook_name_card' => 'projectcard',
            'hook_name_list' => 'projectlist',
            'create_url'     => 'projet/card.php',
            'class_path'     => 'projet/class/project.class.php',
            'lib_path'       => 'core/lib/project.lib.php',
        ];
        $objectsMetadata['task'] = [
            'mainmenu'       => '',
            'leftmenu'       => 'tasks',
            'langs'          => 'Task',
            'langfile'       => 'projects',
            'picto'          => 'projecttask',
            'color'          => '#6c6aa8',
            'class_name'     => 'Task',
            'post_name'      => 'fk_task',
            'link_name'      => 'project_task',
            'tab_type'       => 'task',
            'table_element'  => 'projet_task',
            'name_field'     => 'label',
            'fk_parent'      => 'fk_projet',
            'parent_post'    => 'fk_project',
            'hook_name_card' => 'projecttaskcard',
            'hook_name_list' => 'tasklist',
            'create_url'     => 'projet/tasks.php',
            'class_path'     => 'projet/class/task.class.php',
            'lib_path'       => 'core/lib/project.lib.php',
        ];
    }

    if (isModEnabled('facture')) {
        $objectsMetadata['invoice'] = [
            'mainmenu'       => 'billing',
            'leftmenu'       => 'customers_bills',
            'langs'          => 'Invoice',
            'langfile'       => 'bills',
            'picto'          => 'bill',
            'color'          => '#65953d',
            'class_name'     => 'Facture',
            'post_name'      => 'fk_invoice',
            'link_name'      => 'facture',
            'tab_type'       => 'invoice',
            'table_element'  => 'facture',
            'name_field'     => 'ref',
            'hook_name_card' => 'invoicecard',
            'hook_name_list' => 'invoicelist',
            'create_url'     => 'compta/facture/card.php',
            'class_path'     => 'compta/facture/class/facture.class.php',
            'lib_path'       => 'core/lib/invoice.lib.php',
        ];
    }

    if (isModEnabled('order')) {
        $objectsMetadata['order'] = [
            'mainmenu'       => 'billing',
            'leftmenu'       => 'orders',
            'langs'          => 'Order',
            'langfile'       => 'orders',
            'picto'          => 'order',
            'color'          => '#65953d',
            'class_name'     => 'Commande',
            'post_name'      => 'fk_order',
            'link_name'      => 'commande',
            'tab_type'       => 'order',
            'table_element'  => 'commande',
            'name_field'     => 'ref',
            'hook_name_card' => 'ordercard',
            'hook_name_list' => 'orderlist',
            'create_url'     => 'commande/card.php',
            'class_path'     => 'commande/class/commande.class.php',
            'lib_path'       => 'core/lib/order.lib.php',
        ];
    }

    if (isModEnabled('contract')) {
        $objectsMetadata['contract'] = [
            'mainmenu'       => 'commercial',
            'leftmenu'       => 'contracts',
            'langs'          => 'Contract',
            'langfile'       => 'contracts',
            'picto'          => 'contract',
            'color'          => '#3bbfa8',
            'class_name'     => 'Contrat',
            'post_name'      => 'fk_contract',
            'link_name'      => 'contrat',
            'tab_type'       => 'contract',
            'table_element'  => 'contrat',
            'name_field'     => 'ref',
            'hook_name_card' => 'contractcard',
            'hook_name_list' => 'contractlist',
            'create_url'     => 'contrat/card.php',
            'class_path'     => 'contrat/class/contrat.class.php',
            'lib_path'       => 'core/lib/contract.lib.php',
        ];
    }

    if (isModEnabled('ticket')) {
        $objectsMetadata['ticket'] = [
            'mainmenu'       => 'ticket',
            'leftmenu'       => 'ticket',
            'langs'          => 'Ticket',
            'picto'          => 'ticket',
            'color'          => '#3bbfa8',
            'class_name'     => 'Ticket',
            'post_name'      => 'fk_ticket',
            'link_name'      => 'ticket',
            'tab_type'       => 'ticket',
            'table_element'  => 'ticket',
            'name_field'     => 'ref, subject',
            'hook_name_card' => 'ticketcard',
            'hook_name_list' => 'ticketlist',
            'create_url'     => 'ticket/card.php',
            'class_path'     => 'ticket/class/ticket.class.php',
            'lib_path'       => 'core/lib/ticket.lib.php',
        ];
    }

    if (isModEnabled('stock')) {
        $objectsMetadata['entrepot'] = [
            'mainmenu'       => '',
            'leftmenu'       => 'stock',
            'langs'          => 'Warehouse',
            'langfile'       => 'stocks',
            'picto'          => 'stock',
            'color'          => '#3bbfa8',
            'class_name'     => 'Entrepot',
            'post_name'      => 'fk_entrepot',
            'link_name'      => 'stock',
            'tab_type'       => 'stock',
            'table_element'  => 'entrepot',
            'name_field'     => 'ref',
            'hook_name_card' => 'warehousecard',
            'hook_name_list' => 'stocklist',
            'create_url'     => 'product/stock/card.php',
            'class_path'     => 'product/stock/class/entrepot.class.php',
            'lib_path'       => 'core/lib/stock.lib.php',
        ];
    }

    if (isModEnabled('expedition')) {
        $objectsMetadata['expedition'] = [
            'mainmenu'       => '',
            'leftmenu'       => 'sendings',
            'langs'          => 'Shipments',
            'langfile'       => 'sendings',
            'picto'          => 'dolly',
            'class_name'     => 'Expedition',
            'post_name'      => 'fk_expedition',
            'link_name'      => 'expedition',
            'tab_type'       => 'delivery',
            'table_element'  => 'expedition',
            'name_field'     => 'ref',
            'hook_name_card' => 'ordershipmentcard',
            'hook_name_list' => 'propallist',
            'class_path'     => 'expedition/class/expedition.class.php',
            'lib_path'       => 'core/lib/expedition.lib.php',
        ];
    }

    if (isModEnabled('propal')) {
        $objectsMetadata['propal'] = [
            'mainmenu'       => '',
            'leftmenu'       => 'propals',
            'langs'          => 'Proposal',
            'langfile'       => 'propal',
            'picto'          => 'propal',
            'color'          => '#65953d',
            'class_name'     => 'Propal',
            'post_name'      => 'fk_propal',
            'link_name'      => 'propal',
            'tab_type'       => 'propal',
            'table_element'  => 'propal',
            'name_field'     => 'ref',
            'hook_name_card' => 'propalcard',
            'hook_name_list' => 'propallist',
            'create_url'     => 'comm/propal/card.php',
            'class_path'     => 'comm/propal/class/propal.class.php',
            'lib_path'       => 'core/lib/propal.lib.php',
        ];
    }

    // Hook to add controllable objects from other modules
    if (!is_object($hookmanager)) {
        include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
        $hookmanager = new HookManager($db);
    }
    $hookmanager->initHooks(['saturnegetobjectsmetadata']);

    $resHook = $hookmanager->executeHooks('extendGetObjectsMetadata', $objectsMetadata);

    if ($resHook && (is_array($hookmanager->resArray) && !empty($hookmanager->resArray))) {
        $objectsMetadata = $hookmanager->resArray;
    }

    $objectsMetadataArray = [];
    $otherNameType        = '';
    if (is_array($objectsMetadata) && !empty($objectsMetadata)) {
        foreach($objectsMetadata as $objectType => $objectMetadata) {
            if ($objectType != 'context' && $objectType != 'currentcontext') {
                require_once DOL_DOCUMENT_ROOT . '/' . $objectMetadata['class_path'];
                require_once DOL_DOCUMENT_ROOT . '/' . $objectMetadata['lib_path'];
                $object       = new $objectMetadata['class_name']($db);
                $tableElement = $object->table_element;

                $objectsMetadataArray[$objectType] = [
                    'name'           => ucfirst($objectType),
                    'mainmenu'       => $objectMetadata['mainmenu'] ?? '',
                    'leftmenu'       => $objectMetadata['leftmenu'] ?? '',
                    'langs'          => $objectMetadata['langs'] ?? '',
                    'langfile'       => $objectMetadata['langfile'] ?? '',
                    'picto'          => $objectMetadata['picto'] ?? '',
                    'color'          => $objectMetadata['color'] ?? '',
                    'class_name'     => $objectMetadata['class_name'] ?? '',
                    'name_field'     => $objectMetadata['name_field'] ?? '',
                    'post_name'      => $objectMetadata['post_name'] ?? '',
                    'link_name'      => $objectMetadata['link_name'] ?? '',
                    'tab_type'       => $objectMetadata['tab_type'] ?? '',
                    'table_element'  => $tableElement ?? '',
                    'fk_parent'      => $objectMetadata['fk_parent'] ?? '',
                    'parent_post'    => $objectMetadata['parent_post'] ?? '',
                    'hook_name_card' => $objectMetadata['hook_name_card'] ?? '',
                    'hook_name_list' => $objectMetadata['hook_name_list'] ?? '',
                    'create_url'     => $objectMetadata['create_url'] ?? '',
                    'class_path'     => $objectMetadata['class_path'] ?? '',
                    'lib_path'       => $objectMetadata['lib_path'] ?? '',
                ];
                if (!empty($objectMetadata['langfile'])) {
                    $langs->load($objectMetadata['langfile']);
                }
                if (dol_strlen($type) > 0 && empty($otherNameType)) {
                    $otherNameType = (!empty(array_search($type, $objectMetadata)) ? $objectType : '');
                }
            }
        }
    }

    if (dol_strlen($type) > 0) {
        if (array_key_exists($type, $objectsMetadataArray)) {
            return $objectsMetadataArray[$type];
        } elseif (array_key_exists($otherNameType, $objectsMetadataArray)) {
            return $objectsMetadataArray[$otherNameType];
        } else {
            return [];
        }
    } else {
        return $objectsMetadataArray;
    }
}

/**
 * Require numbering modules of given objects
 *
 * @param  array      $numberingModulesNames Array of numbering modules names
 * @param  string     $moduleNameLowerCase   Module name in lower case
 * @return array      $variablesToReturn     Numbering modules classes
 */
function saturne_require_objects_mod(array $numberingModulesNames, string $moduleNameLowerCase = ''): array
{
    global $db;

    $variablesToReturn = [];
    if (!empty($numberingModulesNames)) {
        foreach($numberingModulesNames as $objectType => $numberingModulesName) {

            if (strstr($objectType, '_')) {
                $objectType = str_replace('_', '', $objectType);
            }

            $modPathCustom   = dirname(__FILE__) . '/../../' . $moduleNameLowerCase . '/core/modules/' . $moduleNameLowerCase . '/' . $objectType . '/' . $numberingModulesName . '.php';
            $modPathDolibarr = DOL_DOCUMENT_ROOT . '/core/modules/' . $objectType . '/'. $numberingModulesName . '.php';

            if (file_exists($modPathCustom)) {
                require_once $modPathCustom;
            } else if (file_exists($modPathDolibarr)) {
                require_once $modPathDolibarr;
            }

            $varName             = 'ref' . ucfirst($objectType) . 'Mod';
            $$varName            = new $numberingModulesName($db);
            $variablesToReturn[] = $$varName;
        }
    }

    return $variablesToReturn;
}

/**
 * Show object action for category
 *
 * @param  string    $moduleNameLowerCase Module name in lower case
 * @param  string    $objectType          Object type
 * @throws Exception
 */
function saturne_object_action_for_category(string $moduleNameLowerCase, string $objectType)
{
    // Global variables definitions
    global $user;

    $result = -1;
    if ($user->hasRight($moduleNameLowerCase, $objectType, 'write')) {
        // Global variables definitions
        global $action, $id, $langs, $object;

        $objects = saturne_fetch_all_object_type($objectType);
        if (is_array($objects) && !empty($objects)) {
            $objectID  = GETPOST('object_id');
            $newObject = $objects[$objectID];
            if ($action == 'add_object_into_category') {
                $result = $object->add_type($newObject, $objectType);
                if ($result >= 0) {
                    setEventMessages($langs->trans('WasAddedSuccessfully', $newObject->ref), []);
                } else {
                    if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
                        setEventMessages($langs->trans('ObjectAlreadyLinkedToCategory'), [], 'warnings');
                    } else {
                        setEventMessages($object->error, $object->errors, 'errors');
                    }
                }
            } elseif ($action == 'unlink_object_from_category') {
                $result = $object->del_type($newObject, $objectType);
                if ($result < 0) {
                    dol_print_error('', $object->error);
                }
            }
            if ($result > 0) {
                header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&type=' . $objectType);
                exit;
            }
        }
    }
}

/**
 * Show object list in category
 *
 * @param  string   $moduleNameLowerCase Module name in lower case
 * @param  string   $objectType          Object type
 * @return string   $out                 HTML table for show/add/delete object list in category
 * @throws Exception
 */
function saturne_show_object_list_in_category(string $moduleNameLowerCase, string $objectType): string
{
    // Global variables definitions
    global $user;

    $out = '';
    if ($user->hasRight($moduleNameLowerCase, $objectType, 'read')) {
        // Global variables definitions
        global $form, $id, $langs;

        $langs->load($moduleNameLowerCase . '@' . $moduleNameLowerCase);

        $objects      = saturne_fetch_all_object_type($objectType);
        $objectArrays = [];
        if (is_array($objects) && !empty($objects)) {
            foreach ($objects as $object) {
                $objectArrays[$object->id] = $object->ref;
            }

            $out .= '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&type=' . $objectType . '">';
            $out .= '<input type="hidden" name="token" value="' . newToken() . '">';
            $out .= '<input type="hidden" name="action" value="add_object_into_category">';

            $out .= '<table class="noborder centpercent">';
            $out .= '<tr class="liste_titre"><td>';
            $out .= $langs->trans('AddObjectIntoCategory') . ' ';
            $out .= $form::selectarray('object_id', $objectArrays, '', 1);
            $out .= '<input type="submit" class="button buttongen" value="' . $langs->trans('ClassifyInCategory') . '"></td>';
            $out .= '</tr>';
            $out .= '</table>';
            $out .= '</form>';

            $newCardButton = dolGetButtonTitle($langs->trans('New'), '', 'fa fa-plus-circle', dol_buildpath('/' . $moduleNameLowerCase . '/view/' . $objectType . '/' . $objectType . '_card.php', 1) . '?action=create&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?id=' . $id . '&type=' . $objectType), '', $user->hasRight($moduleNameLowerCase, $objectType, 'write'));
            $object        = array_shift($objects);
            $picto         = $object->picto;

            $out .= load_fiche_titre($langs->transnoentities(ucfirst($objectType)), $newCardButton, 'object_' . $picto);
            $out .= '<table class="noborder centpercent">';
            $out .= '<tr class="liste_titre"><td colspan="3">' . $langs->trans('Ref') . '</td></tr>';

            $objects = saturne_fetch_all_object_type($objectType, '', '', 0, 0, ['customsql' => 'cp.fk_categorie = ' . $id], 'AND', false, true, true);
            if (is_array($objects) && !empty($objects)) {
                foreach ($objects as $object) {
                    $out .= '<tr class="oddeven"><td class="nowrap">';
                    $out .= $object->getNomUrl(1);
                    $out .= '</td><td class="right">';
                    if ($user->hasRight($moduleNameLowerCase, $objectType, 'write')) {
                        $out .= '<a href="' . $_SERVER['PHP_SELF'] . '?action=unlink_object_from_category&id=' . $id . '&type=' . $objectType . '&object_id=' . $object->id . '&token=' . newToken() . '">';
                        $out .= $langs->trans('DeleteFromCat');
                        $out .= img_picto($langs->trans('DeleteFromCat'), 'unlink', 'class="paddingleft"');
                        $out .= '</a>';
                    }
                    $out .= '</td></tr>';
                }
            } else {
                $out .= '<tr class="oddeven"><td colspan="2" class="opacitymedium">' . $langs->trans('ThisCategoryHasNoItems') . '</td></tr>';
            }
            $out .= '</table>';
        }
    }

    return $out;
}
