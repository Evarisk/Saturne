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
 * \file    class/saturnemail.class.php
 * \ingroup saturne
 * \brief   This file is a CRUD class file for SaturneMail (Create/Read/Update/Delete)
 */

// Load Saturne libraries
require_once __DIR__ . '/saturneobject.class.php';

/**
 * Class for SaturneMail
 */
class SaturneMail extends SaturneObject
{
    /**
     * @var DoliDB Database handler
     */
    public $db;

    /**
     * @var string Element type of object
     */
    public $element = 'saturne_mail';

    /**
     * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management
     */
    public $table_element = 'c_email_templates';

    /**
     * @var int Does this object support multicompany module ?
     * 0 = No test on entity, 1 = Test with field entity, 'field@table' = Test with link by field@table
     */
    public $ismultientitymanaged = 1;

    /**
     * @var int Does object support extrafields ? 0 = No, 1 = Yes
     */
    public $isextrafieldmanaged = 0;

    /**
     * 'type' field format:
     *      'integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]',
     *      'select' (list of values are in 'options'),
     *      'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:Sortfield]]]]',
     *      'chkbxlst:...',
     *      'varchar(x)',
     *      'text', 'text:none', 'html',
     *      'double(24,8)', 'real', 'price',
     *      'date', 'datetime', 'timestamp', 'duration',
     *      'boolean', 'checkbox', 'radio', 'array',
     *      'mail', 'phone', 'url', 'password', 'ip'
     *      Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
     * 'label' the translation key.
     * 'picto' is code of a picto to show before value in forms
     * 'enabled' is a condition when the field must be managed (Example: 1 or '$conf->global->MY_SETUP_PARAM' or '!empty($conf->multicurrency->enabled)' ...)
     * 'position' is the sort order of field.
     * 'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty '' or 0.
     * 'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
     * 'noteditable' says if field is not editable (1 or 0)
     * 'default' is a default value for creation (can still be overwroted by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
     * 'index' if we want an index in database.
     * 'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
     * 'searchall' is 1 if we want to search in this field when making a search from the quick search button.
     * 'isameasure' must be set to 1 or 2 if field can be used for measure. Field type must be summable like integer or double(24,8). Use 1 in most cases, or 2 if you don't want to see the column total into list (for example for percentage)
     * 'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
     * 'help' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
     * 'showoncombobox' if value of the field must be visible into the label of the combobox that list record
     * 'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
     * 'arrayofkeyval' to set a list of values if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel"). Note that type can be 'integer' or 'varchar'
     * 'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
     * 'comment' is not used. You can store here any text of your choice. It is not used by application.
     * 'validate' is 1 if you need to validate with $this->validateField()
     * 'copytoclipboard' is 1 or 2 to allow to add a picto to copy value into clipboard (1=picto after label, 2=picto after value)
     *
     * Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor
     */

    /**
     * @var array Array with all fields and their property. Do not use it as a static var. It may be modified by constructor
     */
    public $fields = [
        'rowid'         => ['type' => 'integer',      'label' => 'TechnicalID',      'enabled' => 1, 'position' => 1,   'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1, 'comment' => 'Id'],
        'entity'        => ['type' => 'integer',      'label' => 'Entity',           'enabled' => 1, 'position' => 10,  'notnull' => 1, 'visible' => 0, 'default' => 1, 'index' => 1],
        'module'        => ['type' => 'varchar(32)',  'label' => 'Module',           'enabled' => 1, 'position' => 20,  'notnull' => 0, 'visible' => 0],
        'type_template' => ['type' => 'varchar(32)',  'label' => 'TypeTemplate',     'enabled' => 1, 'position' => 30,  'notnull' => 0, 'visible' => 0, 'index' => 1],
        'lang'          => ['type' => 'varchar(6)',   'label' => 'Lang',             'enabled' => 1, 'position' => 40,  'notnull' => 0, 'visible' => 0, 'default' => '', 'index' => 1],
        'private'       => ['type' => 'smallint',     'label' => 'Private',          'enabled' => 1, 'position' => 50,  'notnull' => 1, 'visible' => 0, 'default' => 0],
        'datec'         => ['type' => 'datetime',     'label' => 'DateCreation',     'enabled' => 1, 'position' => 60,  'notnull' => 0, 'visible' => 0],
        'tms'           => ['type' => 'timestamp',    'label' => 'DateModification', 'enabled' => 1, 'position' => 70,  'notnull' => 0, 'visible' => 0],
        'label'         => ['type' => 'varchar(180)', 'label' => 'Label',            'enabled' => 1, 'position' => 80,  'notnull' => 0, 'visible' => 0],
        'position'      => ['type' => 'smallint',     'label' => 'Position',         'enabled' => 1, 'position' => 90,  'notnull' => 0, 'visible' => 0],
        'enabled'       => ['type' => 'varchar(255)', 'label' => 'Enabled',          'enabled' => 1, 'position' => 100, 'notnull' => 0, 'visible' => 0, 'default' => 1],
        'active'        => ['type' => 'tinyint',      'label' => 'Active',           'enabled' => 1, 'position' => 110, 'notnull' => 1, 'visible' => 0, 'default' => 1],
        'email_from'    => ['type' => 'varchar(255)', 'label' => 'EmailFrom',        'enabled' => 1, 'position' => 120, 'notnull' => 0, 'visible' => 0],
        'email_to'      => ['type' => 'varchar(255)', 'label' => 'EmailTo',          'enabled' => 1, 'position' => 130, 'notnull' => 0, 'visible' => 0],
        'email_tocc'    => ['type' => 'varchar(255)', 'label' => 'EmailToCC',        'enabled' => 1, 'position' => 140, 'notnull' => 0, 'visible' => 0],
        'email_tobcc'   => ['type' => 'varchar(255)', 'label' => 'EmailToBCC',       'enabled' => 1, 'position' => 150, 'notnull' => 0, 'visible' => 0],
        'topic'         => ['type' => 'text',         'label' => 'Topic',            'enabled' => 1, 'position' => 160, 'notnull' => 0, 'visible' => 0],
        'joinfiles'     => ['type' => 'text',         'label' => 'JoinFiles',        'enabled' => 1, 'position' => 170, 'notnull' => 0, 'visible' => 0],
        'content'       => ['type' => 'mediumtext',   'label' => 'Content',          'enabled' => 1, 'position' => 180, 'notnull' => 0, 'visible' => 0],
        'content_lines' => ['type' => 'text',         'label' => 'ContentLines',     'enabled' => 1, 'position' => 190, 'notnull' => 0, 'visible' => 0],
        'fk_user'       => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'picto' => 'user', 'enabled' => 1, 'position' => 200, 'notnull' => 0, 'visible' => 0, 'foreignkey' => 'user.rowid']
    ];

    /**
     * @var int ID
     */
    public int $rowid;

    /**
     * @var int Entity
     */
    public $entity;

    /**
     * @var string Module name
     */
    public $module;

    /**
     * @var string Template type
     */
    public string $type_template;

    /**
     * @var string|null Lang
     */
    public ?string $lang = '';

    /**
     * @var int private
     */
    public int $private = 0;

    /**
     * @var int|string|null Creation date
     */
    public $datec;

    /**
     * @var int|string Timestamp
     */
    public $tms;

    /**
     * @var string|null Label
     */
    public ?string $label;

    /**
     * @var int Position
     */
    public int $position;

    /**
     * @var int|string Enabled
     */
    public $enabled;

    /**
     * @var int Active
     */
    public int $active = 1;

    /**
     * @var string|null Email from
     */
    public ?string $email_from = null;

    /**
     * @var string|null Email to
     */
    public ?string $email_to = null;

    /**
     * @var string|null Email CC
     */
    public ?string $email_tocc = null;

    /**
     * @var string|null Email BCC
     */
    public ?string $email_tobcc = null;

    /**
     * @var string|null Topic
     */
    public ?string $topic;

    /**
     * @var int|string|null Join files
     */
    public $joinfiles;

    /**
     * @var string|null Content
     */
    public ?string $content;

    /**
     * @var string|null Content lines
     */
    public ?string $content_lines = null;

    /**
     * @var int|null User ID
     */
    public ?int $fk_user = null;

    /**
     * Constructor
     *
     * @param DoliDb $db                  Database handler
     * @param string $moduleNameLowerCase Module name
     * @param string $objectType          Object element type
     */
    public function __construct(DoliDB $db, string $moduleNameLowerCase = 'saturne', string $objectType = 'saturne_mail')
    {
        parent::__construct($db, $moduleNameLowerCase, $objectType);
    }
}
