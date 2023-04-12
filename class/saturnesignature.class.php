<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
 * \file        class/saturnesignature.class.php
 * \ingroup     saturne
 * \brief       This file is a CRUD class file for SaturneSignature (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/ticket.lib.php';

/**
 * Class for SaturneSignature
 */
class SaturneSignature extends CommonObject
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;

    /**
     * @var string[] Array of error strings
     */
    public $errors = [];

    /**
     * @var string Module name.
     */
    public string $module = 'saturne';

    /**
     * @var string Element type of object.
     */
    public $element = 'object_signature';

    /**
     * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
     */
    public $table_element = 'saturne_object_signature';

    /**
     * @var int  Does this object support multicompany module ?
     * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
     */
    public int $ismultientitymanaged = 1;

    /**
     * @var int  Does object support extrafields ? 0=No, 1=Yes
     */
    public int $isextrafieldmanaged = 0;

    /**
     * @var string String with name of icon for signature. Must be the part after the 'object_' into object_signature.png
     */
    public string $picto = '';

    const STATUS_DELETED = -1;
    const STATUS_DRAFT = 0;
    const STATUS_REGISTERED = 1;
    const STATUS_PENDING_SIGNATURE = 3;
    const STATUS_SIGNED = 5;

    const ATTENDANCE_PRESENT = 0;
    const ATTENDANCE_DELAY   = 1;
    const ATTENDANCE_ABSENT  = 2;

    /**
     * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
     */
    public array $fields = [
        'rowid'                => ['type' => 'integer',      'label' => 'TechnicalID',       'enabled' => 1, 'position' => 1,   'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1, 'comment' => 'Id'],
        'entity'               => ['type' => 'integer',      'label' => 'Entity',            'enabled' => 1, 'position' => 10,  'notnull' => 1, 'visible' => 0, 'index' => 1],
        'date_creation'        => ['type' => 'datetime',     'label' => 'DateCreation',      'enabled' => 1, 'position' => 20,  'notnull' => 1, 'visible' => 0],
        'tms'                  => ['type' => 'timestamp',    'label' => 'DateModification',  'enabled' => 1, 'position' => 30,  'notnull' => 1, 'visible' => 0],
        'import_key'           => ['type' => 'varchar(14)',  'label' => 'ImportId',          'enabled' => 1, 'position' => 40,  'notnull' => 0, 'visible' => 0],
        'status'               => ['type' => 'smallint',     'label' => 'Status',            'enabled' => 1, 'position' => 50,  'notnull' => 0, 'visible' => 1, 'index' => 1],
        'role'                 => ['type' => 'varchar(255)', 'label' => 'Role',              'enabled' => 1, 'position' => 60,  'notnull' => 0, 'visible' => 3],
        'firstname'            => ['type' => 'varchar(255)', 'label' => 'Firstname',         'enabled' => 1, 'position' => 70,  'notnull' => 0, 'visible' => 3],
        'lastname'             => ['type' => 'varchar(255)', 'label' => 'Lastname',          'enabled' => 1, 'position' => 80,  'notnull' => 0, 'visible' => 3],
        'email'                => ['type' => 'varchar(255)', 'label' => 'Email',             'enabled' => 1, 'position' => 90,  'notnull' => 0, 'visible' => 3],
        'phone'                => ['type' => 'varchar(255)', 'label' => 'Phone',             'enabled' => 1, 'position' => 100, 'notnull' => 0, 'visible' => 3],
        'society_name'         => ['type' => 'varchar(255)', 'label' => 'SocietyName',       'enabled' => 1, 'position' => 110, 'notnull' => 0, 'visible' => 3],
        'signature_date'       => ['type' => 'datetime',     'label' => 'SignatureDate',     'enabled' => 1, 'position' => 120, 'notnull' => 0, 'visible' => 3],
        'signature_location'   => ['type' => 'varchar(255)', 'label' => 'SignatureLocation', 'enabled' => 1, 'position' => 125, 'notnull' => 0, 'visible' => 3],
        'signature_comment'    => ['type' => 'varchar(255)', 'label' => 'SignatureComment',  'enabled' => 1, 'position' => 130, 'notnull' => 0, 'visible' => 3],
        'element_id'           => ['type' => 'integer',      'label' => 'ElementType',       'enabled' => 1, 'position' => 140, 'notnull' => 1, 'visible' => 1, 'index' => 1],
        'element_type'         => ['type' => 'varchar(255)', 'label' => 'ElementType',       'enabled' => 1, 'position' => 150, 'notnull' => 0, 'visible' => 1],
        'module_name'          => ['type' => 'varchar(255)', 'label' => 'ModuleName',        'enabled' => 1, 'position' => 155, 'notnull' => 0, 'visible' => 1],
        'signature'            => ['type' => 'varchar(255)', 'label' => 'Signature',         'enabled' => 1, 'position' => 160, 'notnull' => 0, 'visible' => 3],
        'stamp'                => ['type' => 'varchar(255)', 'label' => 'Stamp',             'enabled' => 1, 'position' => 165, 'notnull' => 0, 'visible' => 3],
        'signature_url'        => ['type' => 'varchar(255)', 'label' => 'SignatureUrl',      'enabled' => 1, 'position' => 170, 'notnull' => 0, 'visible' => 1],
        'transaction_url'      => ['type' => 'varchar(255)', 'label' => 'TransactionUrl',    'enabled' => 1, 'position' => 180, 'notnull' => 0, 'visible' => 1],
        'last_email_sent_date' => ['type' => 'datetime',     'label' => 'SendMailDate',      'enabled' => 1, 'position' => 190, 'notnull' => 0, 'visible' => 3],
        'attendance'           => ['type' => 'smallint',     'label' => 'Attendance',        'enabled' => 1, 'position' => 194, 'notnull' => 0, 'visible' => 3],
        'object_type'          => ['type' => 'varchar(255)', 'label' => 'object_type',       'enabled' => 1, 'position' => 195, 'notnull' => 0, 'visible' => 0],
        'fk_object'            => ['type' => 'integer',      'label' => 'FKObject',          'enabled' => 1, 'position' => 200, 'notnull' => 1, 'visible' => 0, 'index' => 1],
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

    /**
     * @var string Role
     */
    public string $role;

    /**
     * @var string Firstname
     */
    public $firstname;

    /**
     * @var string Lastname
     */
    public $lastname;

    /**
     * @var string Email
     */
    public string $email;

    /**
     * @var string Phone
     */
    public string $phone;

    /**
     * @var string Society name
     */
    public string $society_name;

    /**
     * @var string Signature date
     */
    public string $signature_date;

    /**
     * @var string Signature location
     */
    public string $signature_location = '';

    /**
     * @var string Signature Comment
     */
    public string $signature_comment = '';

    /**
     * @var int Element id
     */
    public int $element_id;

    /**
     * @var string Element type
     */
    public string $element_type;

    /**
     * @var string Module name
     */
    public string $module_name;

    /**
     * @var string|null Signature
     */
    public ?string $signature = '';

    /**
     * @var string Stamp
     */
    public string $stamp = '';

    /**
     * @var string Signature url
     */
    public string $signature_url;

    /**
     * @var string Transaction url
     */
    public string $transaction_url = '';

    /**
     * @var string Last email sent date
     */
    public string $last_email_sent_date;

    /**
     * @var int|null Attendance
     */
    public ?int $attendance;

    /**
     * @var string Object type
     */
    public string $object_type = '';

    /**
     * @var int Object id
     */
    public int $fk_object;

    /**
     * Constructor
     *
     * @param DoliDb $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        global $conf, $langs;

        $this->db = $db;

        if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) {
            $this->fields['rowid']['visible'] = 0;
        }
        if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) {
            $this->fields['entity']['enabled'] = 0;
        }

        // Unset fields that are disabled
        foreach ($this->fields as $key => $val) {
            if (isset($val['enabled']) && empty($val['enabled'])) {
                unset($this->fields[$key]);
            }
        }

        // Translate some data of arrayofkeyval
        if (is_object($langs)) {
            foreach ($this->fields as $key => $val) {
                if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
                    foreach ($val['arrayofkeyval'] as $key2 => $val2) {
                        $this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
                    }
                }
            }
        }
    }

    /**
     * Create object into database
     *
     * @param  User $user      User that creates
     * @param  bool $notrigger false = launch triggers after, true = disable triggers
     * @return int             0 < if KO, ID of created object if OK
     */
    public function create(User $user, bool $notrigger = false): int
    {
        return $this->createCommon($user, $notrigger);
    }

    /**
     * Load object in memory from the database
     *
     * @param  int         $id        Id object
     * @param  string|null $ref       Ref
     * @param  string      $morewhere More SQL filters (' AND ...')
     * @return int                    0 < if KO, 0 if not found, > 0 if OK
     */
    public function fetch(int $id, string $ref = null, string $morewhere = ''): int
    {
        return $this->fetchCommon($id, $ref, $morewhere);
    }

    /**
     * Load list of objects in memory from the database.
     *
     * @param  string      $sortorder         Sort Order
     * @param  string      $sortfield         Sort field
     * @param  int         $limit             Limit
     * @param  int         $offset            Offset
     * @param  array       $filter            Filter array. Example array('field'=>'value', 'customurl'=>...)
     * @param  string      $filtermode        Filter mode (AND/OR)
     * @param  string      $old_table_element backward compatibility
     * @return int|array                      0 < if KO, array of pages if OK
     * @throws Exception
     */
    public function fetchAll(string $sortorder = '', string $sortfield = '', int $limit = 0, int $offset = 0, array $filter = [], string $filtermode = 'AND', string $old_table_element = '')
    {
        dol_syslog(__METHOD__, LOG_DEBUG);

        $records = [];

        $sql = 'SELECT ';
        if (dol_strlen($old_table_element) > 0) {
            unset($this->fields['signature_location']);
            unset($this->fields['object_type']);
        }
        $sql .= $this->getFieldList();

        if (dol_strlen($old_table_element)) {
            $sql .= ' FROM ' . MAIN_DB_PREFIX . $old_table_element . ' as t';
        } else {
            $sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
        }
        if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) {
            $sql .= ' WHERE t.entity IN (' . getEntity($this->table_element) . ')';
        } else {
            $sql .= ' WHERE 1 = 1';
        }
        // Manage filter
        $sqlwhere = [];
        if (count($filter) > 0) {
            foreach ($filter as $key => $value) {
                if ($key == 't.rowid') {
                    $sqlwhere[] = $key . '=' . $value;
                } elseif (in_array($this->fields[$key]['type'], ['date', 'datetime', 'timestamp'])) {
                    $sqlwhere[] = $key . ' = \'' . $this->db->idate($value) . '\'';
                } elseif ($key == 'customsql') {
                    $sqlwhere[] = $value;
                } elseif (strpos($value, '%') === false) {
                    $sqlwhere[] = $key . ' IN (' . $this->db->sanitize($this->db->escape($value)) . ')';
                } else {
                    $sqlwhere[] = $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
                }
            }
        }
        if (count($sqlwhere) > 0) {
            $sql .= ' AND (' . implode(' ' . $filtermode . ' ', $sqlwhere) . ')';
        }

        if (!empty($sortfield)) {
            $sql .= $this->db->order($sortfield, $sortorder);
        }
        if (!empty($limit)) {
            $sql .= ' ' . $this->db->plimit($limit, $offset);
        }
        $resql = $this->db->query($sql);

        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i   = 0;
            while ($i < ($limit ? min($limit, $num) : $num)) {
                $obj = $this->db->fetch_object($resql);

                $record = new self($this->db);
                $record->setVarsFromFetchObj($obj);

                $records[$record->id] = $record;

                $i++;
            }
            $this->db->free($resql);

            return $records;
        } else {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

            return -1;
        }
    }

    /**
     * Update object into database
     *
     * @param  User $user      User that modifies
     * @param  bool $notrigger false = launch triggers after, true = disable triggers
     * @return int             0 < if KO, > 0 if OK
     */
    public function update(User $user, bool $notrigger = false): int
    {
        return $this->updateCommon($user, $notrigger);
    }

    /**
     * Delete object in database
     *
     * @param  User $user      User that deletes
     * @param  bool $notrigger false = launch triggers after, true = disable triggers
     * @return int             0 < if KO, > 0 if OK
     */
    public function delete(User $user, bool $notrigger = false): int
    {
        return $this->deleteCommon($user, $notrigger);
    }

    /**
     * Set registered status
     *
     * @param  User $user      Object user that modify
     * @param  int  $notrigger 1 = Does not execute triggers, 0 = Execute triggers
     * @return int             0 < if KO, > 0 if OK
     */
    public function setRegistered(User $user, int $notrigger = 0): int
    {
        return $this->setStatusCommon($user, self::STATUS_REGISTERED, $notrigger, 'SATURNESIGNATURE_REGISTERED');
    }

    /**
     * Set pending status
     *
     * @param  User $user      Object user that modify
     * @param  int  $notrigger 1 = Does not execute triggers, 0 = Execute triggers
     * @return int             0 < if KO, > 0 if OK
     */
    public function setPending(User $user, int $notrigger = 0): int
    {
        return $this->setStatusCommon($user, self::STATUS_PENDING_SIGNATURE, $notrigger, 'SATURNESIGNATURE_PENDING_SIGNATURE');
    }

    /**
     * Set signed status
     *
     * @param  User   $user      Object user that modify
     * @param  int    $notrigger 1 = Does not execute triggers, 0 = Execute triggers
     * @param  string $zone      Zone (private or public)
     * @return int               0 < if KO, > 0 if OK
     */
    public function setSigned(User $user, int $notrigger = 0, string $zone = 'private'): int
    {
        return $this->setStatusCommon($user, self::STATUS_SIGNED, $notrigger, 'SATURNESIGNATURE_SIGNED' . (($zone == 'public') ? '_PUBLIC' : ''));
    }

    /**
     * Set deleted status
     *
     * @param  User $user      Object user that modify
     * @param  int  $notrigger 1 = Does not execute triggers, 0 = Execute triggers
     * @return int             0 < if KO, > 0 if OK
     */
    public function setDeleted(User $user, int $notrigger = 0): int
    {
        return $this->setStatusCommon($user, self::STATUS_DELETED, $notrigger, 'SATURNESIGNATURE_DELETED');
    }

    /**
     * Return the label of the status
     *
     * @param  int    $mode 0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
     * @return string       Label of status
     */
    public function getLibStatut(int $mode = 0): string
    {
        return $this->LibStatut($this->status, $mode);
    }

    /**
     * Return the status
     *
     * @param  int    $status Id status
     * @param  int    $mode   0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
     * @return string         Label of status
     */
    public function LibStatut(int $status, int $mode = 0): string
    {
        if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
            global $langs;
            $this->labelStatus[self::STATUS_DELETED]           = $langs->transnoentities('Deleted');
            $this->labelStatus[self::STATUS_REGISTERED]        = $langs->transnoentities('Registered');
            $this->labelStatus[self::STATUS_PENDING_SIGNATURE] = $langs->transnoentities('PendingSignature');
            $this->labelStatus[self::STATUS_SIGNED]            = $langs->transnoentities('Signed');

            $this->labelStatusShort[self::STATUS_DELETED]           = $langs->transnoentitiesnoconv('Deleted');
            $this->labelStatusShort[self::STATUS_REGISTERED]        = $langs->transnoentitiesnoconv('Registered');
            $this->labelStatusShort[self::STATUS_PENDING_SIGNATURE] = $langs->transnoentitiesnoconv('PendingSignature');
            $this->labelStatusShort[self::STATUS_SIGNED]            = $langs->transnoentitiesnoconv('Signed');
        }

        $statusType = 'status' . $status;
        if ($status == self::STATUS_SIGNED) {
            $statusType = 'status4';
        }

        return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
    }

    /**
     * Create signatory in database
     *
     * @param  int        $fk_object    ID of object linked
     * @param  string     $object_type  Type of object
     * @param  string     $element_type Type of resource
     * @param  array      $element_ids  ID of resource
     * @param  string     $role         Role of resource
     * @param  int        $noupdate     Update previous signatories
     * @return int
     * @throws Exception
     */
    public function setSignatory(int $fk_object, string $object_type, string $element_type, array $element_ids, string $role = '', int $noupdate = 0): int
    {
        global $conf, $user;

        $society = new Societe($this->db);
        $result  = 0;
        if (!empty($element_ids) && $element_ids > 0) {
            if (!$noupdate) {
                $this->deletePreviousSignatories($role, $fk_object, $object_type);
            }
            foreach ($element_ids as $element_id) {
                if ($element_id > 0) {
                    $signatory_data = '';
                    if ($element_type == 'user') {
                        $signatory_data = new User($this->db);

                        $signatory_data->fetch($element_id);

                        if ($signatory_data->socid > 0) {
                            $society->fetch($signatory_data->socid);
                            $this->society_name = $society->name;
                        } else {
                            $this->society_name = $conf->global->MAIN_INFO_SOCIETE_NOM;
                        }

                        $this->phone = $signatory_data->user_mobile;
                    } elseif ($element_type == 'socpeople') {
                        $signatory_data = new Contact($this->db);

                        $signatory_data->fetch($element_id);
                        if (!is_object($signatory_data)) {
                            $signatory_data = new stdClass();
                        }

                        $society->fetch($signatory_data->socid);

                        $this->society_name = $society->name;
                        $this->phone        = $signatory_data->phone_mobile;
                    }

                    $this->status = self::STATUS_REGISTERED;

                    $this->firstname = $signatory_data->firstname;
                    $this->lastname  = $signatory_data->lastname;
                    $this->email     = $signatory_data->email;
                    $this->role      = $role;

                    $this->element_type = $element_type;
                    $this->element_id   = $element_id;

                    $this->signature_url = generate_random_id();

                    $this->attendance = self::ATTENDANCE_PRESENT;

                    $this->object_type = $object_type;
                    $this->fk_object   = $fk_object;
                    $this->module_name = $this->module;

                    $result = $this->create($user);
                    if ($result > 0) {
                        $this->call_trigger(strtoupper(get_class($this)) . '_ADDATTENDANT', $user);
                    }
                }
            }
        }
        if ($result > 0 ) {
            return 1;
        } else {
            return -1;
        }
    }

    /**
     * Fetch signatory from database
     *
     * @param  string    $role        Role of resource
     * @param  int       $fk_object   ID of object linked
     * @param  string    $object_type ID of object linked
     * @return array|int
     * @throws Exception
     */
    public function fetchSignatory(string $role, int $fk_object, string $object_type)
    {
        $filter = ['customsql' => 'fk_object=' . $fk_object . ' AND status > 0 AND object_type="' . $object_type . '"'];
        if (strlen($role)) {
            $filter['customsql'] .= ' AND role = "' . $role . '"';
            return $this->fetchAll('', '', 0, 0, $filter);
        } else {
            $signatories = $this->fetchAll('', '', 0, 0, $filter);
            if (!empty($signatories) && $signatories > 0) {
                $signatoriesArray = [];
                foreach ($signatories as $signatory) {
                    $signatoriesArray[$signatory->role][$signatory->id] = $signatory;
                }
                return $signatoriesArray;
            } else {
                return 0;
            }
        }
    }

    /**
     * Fetch signatories in database with parent ID
     *
     * @param  int           $fk_object   ID of object linked
     * @param  string        $object_type Type of object
     * @param  string        $morefilter  Filter
     * @return array|integer
     * @throws Exception
     */
    public function fetchSignatories(int $fk_object, string $object_type, string $morefilter = '1 = 1')
    {
        $filter = ['customsql' => 'fk_object=' . $fk_object . ' AND ' . $morefilter . ' AND object_type="' . $object_type . '"' . ' AND status > 0'];
        return $this->fetchAll('', '', 0, 0, $filter);
    }

    /**
     * Check if signatories signed
     *
     * @param  int       $fk_object   ID of object linked
     * @param  string    $object_type Type of object
     * @return int
     * @throws Exception
     */
    public function checkSignatoriesSignatures(int $fk_object, string $object_type): int
    {
        $morefilter  = 'status != 0';
        $signatories = $this->fetchSignatories($fk_object, $object_type, $morefilter);
        if (!empty($signatories) && $signatories > 0) {
            foreach ($signatories as $signatory) {
                if ($signatory->status == self::STATUS_SIGNED || $signatory->attendance == self::ATTENDANCE_ABSENT) {
                    continue;
                } else {
                    return 0;
                }
            }
            return 1;
        } else {
            return -1;
        }
    }

    /**
     * Delete signatories signatures
     *
     * @param  int       $fk_object   ID of object linked
     * @param  string    $object_type Type of object
     * @return int
     * @throws Exception
     */
    public function deleteSignatoriesSignatures(int $fk_object, string $object_type): int
    {
        global $user;

        $signatories = $this->fetchSignatories($fk_object, $object_type);
        if (!empty($signatories) && $signatories > 0) {
            foreach ($signatories as $signatory) {
                if (dol_strlen($signatory->signature)) {
                    $signatory->signature      = '';
                    $signatory->signature_date = '';
                    $signatory->status         = self::STATUS_REGISTERED;
                    $signatory->attendance     = self::ATTENDANCE_PRESENT;
                    $signatory->update($user);
                }
            }
            return 1;
        } else {
            return -1;
        }
    }

    /**
     * Set previous signatories status to 0
     *
     * @param  string    $role        Role of resource
     * @param  int       $fk_object   ID of object linked
     * @param  string    $object_type Type of object linked
     * @return int
     * @throws Exception
     */
    public function deletePreviousSignatories(string $role, int $fk_object, string $object_type): int
    {
        global $user;

        $filter              = ['customsql' => ' role="' . $role . '" AND fk_object=' . $fk_object . ' AND status=1 AND object_type="' . $object_type . '"'];
        $signatoriesToDelete = $this->fetchAll('', '', 0, 0, $filter);
        if (!empty($signatoriesToDelete) && $signatoriesToDelete > 0) {
            foreach ($signatoriesToDelete as $signatoryToDelete) {
                $signatoryToDelete->setDeleted($user, true);
            }
            return 1;
        } else {
            return -1;
        }
    }

    /**
     * Clone an object into another one
     *
     * @param  User       $user     User that creates
     * @param  int        $fromID   ID of object to clone
     * @param  int        $objectID ID of object element to link with signatory
     * @return int                  New object created, <0 if KO
     * @throws Exception
     */
    public function createFromClone(User $user, int $fromID, int $objectID): int
    {
        dol_syslog(__METHOD__, LOG_DEBUG);

        $object = new self($this->db);

        $this->db->begin();

        // Load source object
        $object->fetchCommon($fromID);

        // Reset some properties
        unset($object->id);
        unset($object->fk_user_creat);
        unset($object->import_key);
        unset($object->signature_date);
        unset($object->last_email_sent_date);

        // Clear fields
        if (property_exists($object, 'date_creation')) {
            $object->date_creation = dol_now();
        }
        if (property_exists($object, 'fk_object')) {
            $object->fk_object = $objectID;
        }
        if (property_exists($object, 'status')) {
            $object->status = self::STATUS_REGISTERED;
        }
        if (property_exists($object, 'attendance')) {
            $object->attendance = self::ATTENDANCE_PRESENT;
        }
        if (property_exists($object, 'signature')) {
            $object->signature = '';
        }
        if (property_exists($object, 'signature_url')) {
            $object->signature_url = generate_random_id();
        }

        // Create clone
        $object->context['createfromclone'] = 'createfromclone';
        $result                             = $object->createCommon($user);
        unset($object->context['createfromclone']);

        // End
        if ($result > 0) {
            $this->db->commit();
            return $result;
        } else {
            $this->db->rollback();
            return -1;
        }
    }
}