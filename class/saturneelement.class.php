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
 * \file    class/saturneelement.class.php
 * \ingroup saturne
 * \brief   This file is a CRUD class file for SaturneElement (Create/Read/Update/Delete)
 */

// Load Saturne libraries
require_once __DIR__ . '/../../saturne/class/saturneobject.class.php';

/**
 * Class for SaturneElement
 */
class SaturneElement extends SaturneObject
{
    /**
     * @var string Module name
     */
    public $module = 'saturne';

    /**
     * @var string Element type of object
     */
    public $element = 'saturneelement';

    /**
     * @var string String with name of icon for saturneelement
     */
    public string $picto = 'fontawesome_fa-network-wired_fas_#d35968';

    /**
     * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management
     */
    public $table_element = 'saturne_object_element';

    /**
     * @var int Does this object support multicompany module ?
     * 0 = No test on entity, 1 = Test with field entity, 'field@table' = Test with link by field@table
     */
    public $ismultientitymanaged = 1;

    /**
     * @var int Does object support extrafields ? 0 = No, 1 = Yes
     */
    public $isextrafieldmanaged = 1;

    /**
     * @var int Does object support category module ? 0 = No, 1 = Yes
     */
    public int $isCategoryManaged = 0;

    public const STATUS_TRASHED   = -2;
    public const STATUS_DELETED   = -1;
    public const STATUS_VALIDATED = 1;
    public const STATUS_ARCHIVED  = 3;

    /**
     * 'type' field format:
     *      'integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]',
     *      'select' (list of values are in 'options'. for integer list of values are in 'arrayofkeyval'),
     *      'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:CategoryIdType[:CategoryIdList[:SortField]]]]]]',
     *      'chkbxlst:...',
     *      'varchar(x)',
     *      'text', 'text:none', 'html',
     *      'double(24,8)', 'real', 'price', 'stock',
     *      'date', 'datetime', 'timestamp', 'duration',
     *      'boolean', 'checkbox', 'radio', 'array',
     *      'mail', 'phone', 'url', 'password', 'ip'
     *      Note: Filter must be a Dolibarr Universal Filter syntax string. Example: "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.status:!=:0) or (t.nature:is:NULL)"
     * 'length' the length of field. Example: 255, '24,8'
     * 'label' the translation key
     * 'langfile' the key of the language file for translation
     * 'alias' the alias used into some old hard coded SQL requests
     * 'picto' is code of a picto to show before value in forms
     * 'enabled' is a condition when the field must be managed (Example: 1 or 'getDolGlobalInt("MY_SETUP_PARAM")' or 'isModEnabled("multicurrency")' ...)
     * 'position' is the sort order of field
     * 'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0)
     * 'visible' says if field is visible in list (Examples: 0 = Not visible, 1 = Visible on list and create/update/view forms, 2 = Visible on list only, 3 = Visible on create/update/view form only (not list), 4 = Visible on list and update/view form only (not create). 5 = Visible on list and view only (not create/not update). 6=visible on list and create/view form (not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
     * 'noteditable' says if field is not editable (1 or 0)
     * 'alwayseditable' says if field can be modified also when status is not draft (1 or 0)
     * 'default' is a default value for creation (can still be overwritten by the setup of default values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created
     * 'index' if we want an index in database
     * 'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...)
     * 'searchall' is 1 if we want to search in this field when making a search from the quick search button
     * 'isameasure' must be set to 1 or 2 if field can be used for measure. Field type must be summable like integer or double(24,8). Use 1 in most cases, or 2 if you don't want to see the column total into list (for example for percentage)
     * 'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
     * 'placeholder' to set the placeholder of a varchar field
     * 'help' and 'helplist' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click
     * 'showoncombobox' if value of the field must be visible into the label of the combobox that list record
     * 'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code like the constructor of the class
     * 'arrayofkeyval' to set a list of values if type is a list of predefined values. For example: array(0 => 'Draft', 1 => 'Active', -1 => 'Cancel'). Note that type can be 'integer' or 'varchar'
     * 'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1
     * 'comment' is not used. You can store here any text of your choice. It is not used by application
     * 'validate' is 1 if you need to validate with $this->validateField() Need MAIN_ACTIVATE_VALIDATION_RESULT
     * 'copytoclipboard' is 1 or 2 to allow to add a picto to copy value into clipboard (1 = picto after label, 2 = picto after value)
     *
     * Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor
     */

    // BEGIN MODULEBUILDER PROPERTIES
    /**
     * @var array Array with all fields and their property. Do not use it as a static var. It may be modified by constructor
     */
    public $fields = [
        'rowid'            => ['type' => 'integer',      'label' => 'TechnicalID',      'enabled' => 1, 'position' => 1,   'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1, 'comment' => 'Id'],
        'ref'              => ['type' => 'varchar(128)', 'label' => 'Ref',              'enabled' => 1, 'position' => 10,  'notnull' => 1, 'visible' => 4, 'noteditable' => 1, 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => 1, 'comment' => 'Reference of object'],
        'ref_ext'          => ['type' => 'varchar(128)', 'label' => 'RefExt',           'enabled' => 1, 'position' => 20,  'notnull' => 0, 'visible' => -2],
        'entity'           => ['type' => 'integer',      'label' => 'Entity',           'enabled' => 1, 'position' => 30,  'notnull' => 1, 'visible' => -2, 'index' => 1],
        'date_creation'    => ['type' => 'datetime',     'label' => 'DateCreation',     'enabled' => 1, 'position' => 40,  'notnull' => 1, 'visible' => -2],
        'tms'              => ['type' => 'timestamp',    'label' => 'DateModification', 'enabled' => 1, 'position' => 50,  'notnull' => 0, 'visible' => -2],
        'import_key'       => ['type' => 'varchar(14)',  'label' => 'ImportId',         'enabled' => 1, 'position' => 60,  'notnull' => 0, 'visible' => -2],
        'status'           => ['type' => 'smallint',     'label' => 'Status',           'enabled' => 1, 'position' => 70,  'notnull' => 1, 'visible' => -2, 'default' => 1, 'index' => 1, 'arrayofkeyval' => [-2 => 'Trashed', 1 => 'Validated', 3 => 'Archived']],
        'label'            => ['type' => 'varchar(255)', 'label' => 'Label',            'enabled' => 1, 'position' => 110, 'notnull' => 1, 'visible' => 1, 'searchall' => 1, 'css' => 'maxwidth500 widthcentpercentminusxx'],
        'description'      => ['type' => 'html',         'label' => 'Description',      'enabled' => 1, 'position' => 120, 'notnull' => 0, 'visible' => 1],
        'element_type'     => ['type' => 'select',       'label' => 'ElementType',      'enabled' => 1, 'position' => 100, 'notnull' => 1, 'visible' => 1, 'noteditable' => 1],
        'photo'            => ['type' => 'varchar(255)', 'label' => 'Photo',            'enabled' => 1, 'position' => 130, 'notnull' => 0, 'visible' => -2],
        'position'         => ['type' => 'integer',      'label' => 'Position',         'enabled' => 1, 'position' => 140, 'notnull' => 1, 'visible' => -2],
        'fk_standard'      => ['type' => 'integer:SaturneStandard:saturne/class/saturnestandard.class.php', 'label' => 'Standard/Reference',             'enabled' => 1, 'position' => 80,  'notnull' => 1,  'visible' => 1,  'index' => 1, 'foreignkey' => 'saturne_standard.rowid',       'noteditable' => 1],
        'fk_parent'        => ['type' => 'integer:SaturneElement:saturne/class/saturneelement.class.php',   'label' => 'ParentElement',                  'enabled' => 1, 'position' => 90,  'notnull' => 1,  'visible' => 1,  'index' => 1, 'foreignkey' => 'saturne_object_element.rowid', 'noteditable' => 1],
        'fk_user_creat'    => ['type' => 'integer:User:user/class/user.class.php',                          'label' => 'UserAuthor', 'picto'  => 'user', 'enabled' => 1, 'position' => 150, 'notnull' => 1,  'visible' => -2, 'index' => 1, 'foreignkey' => 'user.rowid'],
        'fk_user_modif'    => ['type' => 'integer:User:user/class/user.class.php',                          'label' => 'UserModif',  'picto'  => 'user', 'enabled' => 1, 'position' => 160, 'notnull' => -1, 'visible' => -2, 'index' => 1, 'foreignkey' => 'user.rowid']
    ];
//        'show_in_selector' => ['type' => 'boolean',      'label' => 'ShowInSelectOnPublicTicketInterface', 'enabled' => 1, 'position' => 106, 'notnull' => 1, 'visible' => 1, 'default' => 1,],

    /**
     * @var int ID
     */
    public int $rowid;

    /**
     * @var string Ref
     */
    public $ref;

    /**
     * @var string Ref ext
     */
    public $ref_ext;

    /**
     * @var int Entity
     */
    public $entity;

    /**
     * @var int|string Creation date
     */
    public $date_creation;

    /**
     * @var int|string Timestamp
     */
    public $tms;

    /**
     * @var string Import key
     */
    public $import_key;

    /**
     * @var int Status
     */
    public $status;

    public $label;
    public $description;
    public $element_type;
    public $photo;
    public $show_in_selector;

    /**
     * @var int Object parent ID
     */
    public int $parent_id;

    /**
     * @var int User ID
     */
    public $fk_user_creat;
    public $fk_parent;
    public $fk_standard;
    public $position;

    // END MODULEBUILDER PROPERTIES

    /**
     * Constructor
     *
     * @param DoliDb $db                  Database handler
     * @param string $moduleNameLowerCase Module name
     * @param string $objectType          Object element type
     */
    public function __construct(DoliDB $db, string $moduleNameLowerCase = 'saturne', string $objectType = 'saturneelement')
    {
        parent::__construct($db, $moduleNameLowerCase, $objectType);
    }

    /**
     * Create object into database.
     *
     * @param User $user User that creates.
     * @param bool $notrigger false = launch triggers after, true = disable triggers.
     * @return int             0 < if KO, ID of created object if OK.
     */
    public function create(User $user, bool $notrigger = false): int
    {
        global $conf;
//        if (empty($this->ref)) {
//            $type = 'DIGIRISKDOLIBARR_' . dol_strtoupper($this->element_type) . '_ADDON';
//            $objectMod = $conf->global->$type;
//            $numberingModules = [
//                'digiriskelement/' . $this->element_type => $objectMod
//            ];
//            list($refDigiriskElementMod) = saturne_require_objects_mod($numberingModules, 'digiriskdolibarr');
//
//            $ref = $refDigiriskElementMod->getNextValue($this);
//            $this->ref = $ref;
//        }

        $this->status      = self::STATUS_VALIDATED;
        $this->fk_standard = getDolGlobalInt(dol_strtoupper($this->module) . '_ACTIVE_STANDARD');

        return $this->createCommon($user, $notrigger);
    }

    /**
     * Delete object in database
     *
     * @param  User $user       User that deletes
     * @param  bool $notrigger  false = launch triggers after, true = disable triggers
     * @param  bool $softDelete Don't delete object
     * @return int              0 < if KO, > 0 if OK
     */
    public function delete(User $user, bool $notrigger = false, bool $softDelete = true): int
    {
        $this->status    = self::STATUS_TRASHED;
        $this->fk_parent = getDolGlobalInt(dol_strtoupper($this->module) . '_' . dol_strtoupper($this->element) . '_TRASH');

        return $this->update($user, true);
    }

    /**
     * Return the status
     *
     * @param  int    $status ID status
     * @param  int    $mode   0 = long label, 1 = short label, 2 = Picto + short label, 3 = Picto, 4 = Picto + long label, 5 = Short label + Picto, 6 = Long label + Picto
     * @return string         Label of status
     */
    public function LibStatut(int $status, int $mode = 0): string
    {
        if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
            global $langs;

            $this->labelStatus[self::STATUS_TRASHED]        = $langs->transnoentitiesnoconv('Trashed');
            $this->labelStatus[self::STATUS_VALIDATED]      = $langs->transnoentitiesnoconv('Validated');
            $this->labelStatus[self::STATUS_ARCHIVED]       = $langs->transnoentitiesnoconv('Archived');
            $this->labelStatus[self::STATUS_DELETED]        = $langs->transnoentitiesnoconv('Deleted');

            $this->labelStatusShort[self::STATUS_TRASHED]   = $langs->transnoentitiesnoconv('Trashed');
            $this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Validated');
            $this->labelStatusShort[self::STATUS_ARCHIVED]  = $langs->transnoentitiesnoconv('Archived');
            $this->labelStatusShort[self::STATUS_DELETED]   = $langs->transnoentitiesnoconv('Deleted');
        }

        $statusType = 'status' . $status;
        if ($status == self::STATUS_VALIDATED) {
            $statusType = 'status4';
        }
        if ($status == self::STATUS_ARCHIVED) {
            $statusType = 'status8';
        }
        if ($status == self::STATUS_TRASHED || $status == self::STATUS_DELETED) {
            $statusType = 'status9';
        }

        return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
    }
}
