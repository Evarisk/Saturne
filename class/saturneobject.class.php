<?php

/* Copyright (C) 2021-2026 EVARISK <technique@evarisk.com>
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
 * \file    class/saturneobject.class.php
 * \ingroup saturne
 * \brief   This file is a CRUD class file for SaturneObject (Create/Read/Update/Delete)
 */

// Load Dolibarr Libraries
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

// Load Saturne libraries
require_once __DIR__ . '/../lib/object.lib.php';

/**
 * Class for SaturneObject
 */
abstract class SaturneObject extends CommonObject
{
    /**
     * @var int<0,1>|string Does this object support multicompany module ?
     *                      0 = No test on entity, 1 = Test with field entity,
     *                     'field@table' = Test with link by field@table
     */
    public $ismultientitymanaged = 1;

    /**
     * @var int<0,1> Does object support extrafields ? 0 = No, 1 = Yes
     */
    public $isextrafieldmanaged = 0;

    /**
     * @var int<0,1> Does object support category module ? 0 = No, 1 = Yes
     */
    public int $isCategoryManaged = 1;

    /**
     * @var string Name of icon for saturne_object
     *             Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size')
     *             or 'saturne_object@saturne' if picto is file 'img/object_saturne_object.png'
     */
    public string $picto = '';

    public const STATUS_DELETED   = -1;
    public const STATUS_DRAFT     = 0;
    public const STATUS_VALIDATED = 1;
    public const STATUS_LOCKED    = 2;
    public const STATUS_ARCHIVED  = 3;

    /**
     * Constructor
     *
     * @param DoliDB $db                  Database handler
     * @param string $moduleNameLowerCase Module name
     * @param string $objectType          Object element type
     */
    public function __construct(DoliDB $db, string $moduleNameLowerCase = 'saturne', string $objectType = 'saturne_object')
    {
        global $langs;

        $this->db      = $db;
        $this->module  = $moduleNameLowerCase;
        $this->element = $objectType;

        if (!getDolGlobalInt('MAIN_SHOW_TECHNICAL_ID') && isset($this->fields['rowid'])) {
            $this->fields['rowid']['visible'] = 0;
        }
        if (!isModEnabled('multicompany') && isset($this->fields['entity'])) {
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
     * @param  User        $user      User that creates
     * @param  int<0,1>    $noTrigger 0 = launch triggers after, 1 = disable triggers
     * @return int<-1,max>            Return integer 0 < if KO, ID of created object if OK
     */
    public function create(User $user, int $noTrigger = 0): int
    {
        return $this->createCommon($user, $noTrigger);
    }

    /**
     * Load object in memory from the database
     *
     * @param  int         $id            ID object
     * @param  string|null $ref           Ref
     * @param  string      $moreWhere     More SQL filters (' AND ...')
     * @param  int<0,1>    $noExtraFields 0 = Default to load extrafields, 1 = No extrafields
     * @param  int<0,1>    $noLines       0 = Default to load lines, 1 = No lines
     * @return int<-4,1>                  Return integer 0 < if KO, 0 if not found, > 0 if OK
     */
    public function fetch(int $id, ?string $ref = '', string $moreWhere = '', int $noExtraFields = 0, int $noLines = 0): int
    {
        $result = $this->fetchCommon($id, $ref, $moreWhere, $noExtraFields);
        if ($result > 0 && !empty($this->table_element_line) && empty($noLines)) {
            $this->fetchLines('', $noExtraFields);
        }
        return $result;
    }

    /**
     * Load object lines in memory from the database
     *
     * @param  string    $morewhere     More SQL filters (' AND ...')
     * @param  int<0,1>  $noExtraFields 0 = Default to load extrafields, 1 = No extrafields
     * @return int<-1,1>                Return integer 0 < if KO, > 0 if OK
     */
    public function fetchLines(string $morewhere = '', int $noExtraFields = 0): int
    {
        $this->lines = [];
        return $this->fetchLinesCommon($morewhere, $noExtraFields);
    }

    /**
     * Load list of objects in memory from the database
     * Using a fetchAll() with limit = 0 is a very bad practice
     * Instead, try to forge yourself an optimized SQL request with
     * your own loop with start and stop pagination
     *
     * @param  string     $sortorder      Sort Order
     * @param  string     $sortfield      Sort field
     * @param  int        $limit          Limit the number of lines returned
     * @param  int        $offset         Offset
     * @param  array      $filter         Filter as a Universal Search string*
     *                                    Example: '((client:=:1) OR ((client:>=:2)
     *                                           AND (client:<=:3))) AND (client:!=:8) AND (nom:like:'a%')'
     * @param  string     $filtermode     No longer used
     * @return array<int,self>|int<-1,-1> Return integer < 0 if KO, array of pages if OK
     * @throws Exception
     */
    public function fetchAll(string $sortorder = '', string $sortfield = '', int $limit = 0, int $offset = 0, array $filter = [], string $filtermode = 'AND')
    {
        dol_syslog(__METHOD__, LOG_DEBUG);

        $records = [];

        $sql = 'SELECT ';
        $sql .= $this->getFieldList('t');
        $sql .= ' FROM ' . $this->db->prefix() . $this->table_element . ' as t';
        if (!empty($this->isextrafieldmanaged) && $this->isextrafieldmanaged == 1) {
            $sql .= ' LEFT JOIN ' . $this->db->prefix() . $this->table_element . '_extrafields as te ON te.fk_object = t.rowid';
        }
        if (isset($this->ismultientitymanaged) && (int) $this->ismultientitymanaged == 1) {
            $sql .= ' WHERE t.entity IN (' . getEntity($this->element) . ')';
        } elseif (preg_match('/^\w+@\w+$/', (string) $this->ismultientitymanaged)) {
            $tmpArray = explode('@', (string) $this->ismultientitymanaged);
            $sql .= ' LEFT JOIN ' . $this->db->prefix() . $tmpArray[1] . ' as pt ON t.' . $this->db->sanitize($tmpArray[0]) . ' = pt.rowid';
            $sql .= ' WHERE pt.entity IN (' . getEntity($this->element) . ')';
        } else {
            $sql .= ' WHERE 1 = 1';
        }

        // Manage filter
        $sqlwhere = [];
        if (count($filter) > 0) {
            foreach ($filter as $key => $value) {
                if ($key == 't.rowid') {
                    $sqlwhere[] = $key . ' = ' . ((int) $value);
                } elseif (isset($this->fields[$key]['type']) && in_array($this->fields[$key]['type'], ['date', 'datetime', 'timestamp'])) {
                    $sqlwhere[] = $key . " = '" . $this->db->idate($value) . "'";
                } elseif ($key == 'customsql') {
                    $sqlwhere[] = $value;
                } elseif (strpos($value, '%') === false) {
                    $sqlwhere[] = $key . ' IN (' . $this->db->sanitize($this->db->escape($value)) . ')';
                } else {
                    $sqlwhere[] = $key . " LIKE '%" . $this->db->escape($value) . "%'";
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
            $sql .= $this->db->plimit($limit, $offset);
        }

        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < ($limit ? min($limit, $num) : $num)) {
                $obj = $this->db->fetch_object($resql);

                $record = new static($this->db);
                $record->setVarsFromFetchObj($obj);

                if (!empty($record->isextrafieldmanaged)) {
                    $record->fetch_optionals();
                }

                $records[$record->id] = $record;

                $i++;
            }
            $this->db->free($resql);

            return $records;
        } else {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);

            return -1;
        }
    }

    /**
     * Update object into database
     *
     * @param  User       $user      User that modifies
     * @param  int<0,1>   $noTrigger 0 = launch triggers after, 1 = disable triggers
     * @return int<-1,1>             Return integer 0 < if KO, > 0 if OK
     */
    public function update(User $user, int $noTrigger = 0): int
    {
        return $this->updateCommon($user, $noTrigger);
    }

    /**
     * Delete object in database
     *
     * @param  User        $user       User that deletes
     * @param  int<0,1>    $noTrigger  0 = launch triggers after, 1 = disable triggers
     * @param  bool        $softDelete Don't delete object
     * @return int<-1,1>               Return integer 0 < if KO, > 0 if OK
     */
    public function delete(User $user, int $noTrigger = 0, bool $softDelete = true): int
    {
        return $softDelete
            ? $this->setDeleted($user, $noTrigger)
            : $this->deleteCommon($user, $noTrigger);
    }

    /**
     * Validate object
     *
     * @param  User      $user      User making status change
     * @param  int<0,1>  $noTrigger 0 = launch triggers after, 1 = disable triggers
     * @return int<-1,1>            Return integer 0 < if OK, 0 = Nothing done, > 0 if KO
     * @throws Exception
     */
    public function validate(User $user, int $noTrigger = 0): int
    {
        global $conf;

        $error = 0;

        // Protection
        if ($this->status == static::STATUS_VALIDATED) {
            dol_syslog(get_class($this) . '::validate action abandonned: already validated', LOG_WARNING);
            return 0;
        }

        $this->db->begin();

        // Define new ref
        if ((preg_match('/^\(?PROV/i', $this->ref) || empty($this->ref))) {
            $num = $this->getNextNumRef();
        } else {
            $num = $this->ref;
        }
        $this->newref = $num;

        if (!empty($num)) {
            // Validate
            $sql  = 'UPDATE ' . $this->db->prefix() . $this->table_element;
            $sql .= " SET ";
            if (!empty($this->fields['ref'])) {
                $sql .= " ref = '" . $this->db->escape($num) . "',";
            }
            $sql .= ' status = ' . static::STATUS_VALIDATED;
            $sql .= ' WHERE rowid = ' . ($this->id);

            dol_syslog(get_class($this) . '::validate()', LOG_DEBUG);
            $resql = $this->db->query($sql);
            if (!$resql) {
                dol_print_error($this->db);
                $this->error = $this->db->lasterror();
                $error++;
            }

            if (!$error && !$noTrigger) {
                // Call trigger
                $result = $this->call_trigger(dol_strtoupper($this->element) . '_VALIDATE', $user);
                if ($result < 0) {
                    $error++;
                }
                // End call triggers
            }
        }

        if (!$error) {
            $this->oldref = $this->ref;

            // Rename directory if dir was a temporary ref
            if (preg_match('/^\(?PROV/i', $this->ref)) {
                // Now we rename also files into index
                $sql  = 'UPDATE ' . $this->db->prefix() . 'ecm_files';
                $sql .= " SET filename = CONCAT('" . $this->db->escape($this->newref) . "', SUBSTR(filename, " . (strlen($this->ref) + 1) . ')),';
                $sql .= " filepath = '" . $this->db->escape($this->element . '/' . $this->newref) . "'";
                $sql .= " WHERE filename LIKE '" . $this->db->escape($this->ref) . "%' AND filepath = '" . $this->db->escape($this->element . '/' . $this->ref) . "' AND entity = " . $conf->entity;

                $resql = $this->db->query($sql);
                if (!$resql) {
                    $error++;
                    $this->error = $this->db->lasterror();
                }

                $sql  = 'UPDATE ' . $this->db->prefix() . "ecm_files set filepath = '" . $this->db->escape($this->element . '/' . $this->newref) . "'";
                $sql .= " WHERE filepath = '" . $this->db->escape($this->element . '/' . $this->ref) . "' and entity = " . $conf->entity;

                $resql = $this->db->query($sql);
                if (!$resql) {
                    $error++;
                    $this->error = $this->db->lasterror();
                }

                // We rename directory ($oldRef = old ref, $num = new ref) in order not to lose the attachments
                $oldRef    = dol_sanitizeFileName($this->ref);
                $newRef    = dol_sanitizeFileName($num);
                $dirSource = getMultidirOutput($this) . '/' . $this->element . '/' . $oldRef;
                $dirDest   = getMultidirOutput($this) . '/' . $this->element . '/' . $newRef;
                if (!$error && file_exists($dirSource)) {
                    dol_syslog(get_class($this) . '::validate() rename dir ' . $dirSource . ' into ' . $dirDest);

                    if (@rename($dirSource, $dirDest)) {
                        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

                        dol_syslog('Rename ok');

                        // Rename docs starting with $oldRef with $newRef
                        $listOfFiles = dol_dir_list($dirDest, 'files', 1, '^' . preg_quote($oldRef, '/'));
                        foreach ($listOfFiles as $fileEntry) {
                            $dirSource = $fileEntry['name'];
                            $dirDest   = preg_replace('/^' . preg_quote($oldRef, '/') . '/', $newRef, $dirSource);
                            $dirSource = $fileEntry['path'] . '/' . $dirSource;
                            $dirDest   = $fileEntry['path'] . '/' . $dirDest;
                            @rename($dirSource, $dirDest);
                        }
                    }
                }
            }
        }

        // Set new ref and current status
        if (!$error) {
            $this->ref    = $num;
            $this->status = static::STATUS_VALIDATED;
        }

        if (!$error) {
            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    /**
     * Set deleted status
     *
     * @param  User      $user      Object user that modify
     * @param  int<0,1>  $noTrigger 0 = launch triggers after, 1 = disable triggers
     * @return int<-1,1>            Return integer 0 < if KO, > 0 if OK
     */
    public function setDeleted(User $user, int $noTrigger = 0): int
    {
        return $this->setStatusCommon($user, static::STATUS_DELETED, $noTrigger, strtoupper($this->element) . '_DELETE');
    }

    /**
     * Set draft status
     *
     * @param  User      $user      Object user that modify
     * @param  int<0,1>  $noTrigger 0 = launch triggers after, 1 = disable triggers
     * @return int<-1,1>            Return integer 0 < if KO, > 0 if OK
     */
    public function setDraft(User $user, int $noTrigger = 0): int
    {
        // Protection
        if ($this->status <= static::STATUS_DRAFT) {
            return 0;
        }

        return $this->setStatusCommon($user, static::STATUS_DRAFT, $noTrigger, strtoupper($this->element) . '_UNVALIDATE');
    }

    /**
     * Set locked status
     *
     * @param  User      $user      Object user that modify
     * @param  int<0,1>  $noTrigger 0 = launch triggers after, 1 = disable triggers
     * @return int<-1,1>            Return integer 0 < if KO, > 0 if OK
     */
    public function setLocked(User $user, int $noTrigger = 0): int
    {
        return $this->setStatusCommon($user, static::STATUS_LOCKED, $noTrigger, strtoupper($this->element) . '_LOCK');
    }

    /**
     * Set archived status
     *
     * @param  User      $user      Object user that modify
     * @param  int<0,1>  $noTrigger 0 = launch triggers after, 1 = disable triggers
     * @return int<-1,1>            Return integer 0 < if KO, > 0 if OK
     */
    public function setArchived(User $user, int $noTrigger = 0): int
    {
        return $this->setStatusCommon($user, static::STATUS_ARCHIVED, $noTrigger, strtoupper($this->element) . '_ARCHIVE');
    }

    /**
     * Return array of data to show into a tooltip
     * This method must be implemented in each object class
     *
     * @param  array<string,mixed>   $params params to construct tooltip data
     * @return array<string,string>         Data to show in tooltip
     */
    public function getTooltipContentArray($params): array
    {
        global $langs;

        $datas = [];

        if (getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER')) {
            return ['optimize' => $langs->trans('Show' . dol_ucfirst($this->element))];
        }
        $datas['picto'] = img_picto('', $this->picto) . ' <u>' . $langs->trans(dol_ucfirst($this->element)) . '</u>';
        if (isset($this->status)) {
            $datas['picto'] .= ' ' . $this->getLibStatut(5);
        }
        if (property_exists($this, 'ref')) {
            $datas['ref'] = '<br><b>' . $langs->trans('Ref') . ' : </b> ' . $this->ref;
        }
        if (property_exists($this, 'label')) {
            $datas['label'] = '<br><b>' . $langs->trans('Label') . ' : </b> ' . $this->label;
        }

        return $datas;
    }

    /**
     * Return a link to the object card (with optionaly the picto)
     *
     * @param  int     $withPicto           Include picto in link (0 = No picto, 1 = Include picto into link, 2 = Only picto)
     * @param  string  $option              On what the link point to ('nolink', ...)
     * @param  int     $noToolTip           1 = Disable tooltip
     * @param  string  $moreCSS             Add more css on link
     * @param  int     $saveLastSearchValue -1 = Auto, 0 = No save of lastsearch_values when clicking, 1 = Save lastsearch_values whenclicking
     * @param  int     $addLabel            0 = Default, 1 = Add label into string, >1 = Add first chars into string
     * @return string                       String with URL
     */
    public function getNomUrl(int $withPicto = 0, string $option = '', int $noToolTip = 0, string $moreCSS = '', int $saveLastSearchValue = -1, int $addLabel = 0): string
    {
        global $action, $conf, $hookmanager, $langs;

        if (!empty($conf->dol_no_mouse_hover)) {
            // Force disable tooltips
            $noToolTip = 1;
        }

        $result = '';
        $params = [
            'id'         => $this->id,
            'objecttype' => $this->element . ($this->module ? '@' . $this->module : ''),
            'option'     => $option,
        ];
        $classForTooltip = 'classfortooltip';
        $dataParams      = '';
        if (getDolGlobalInt('MAIN_ENABLE_AJAX_TOOLTIP')) {
            $classForTooltip = 'classforajaxtooltip';
            $dataParams      = ' data-params="' . dol_escape_htmltag(json_encode($params)) . '"';
            $label           = '';
        } else {
            $label = implode($this->getTooltipContentArray($params));
        }

        $baseurl = dol_buildpath('/' . $this->module . '/view/' . $this->element . '/' . $this->element . '_card.php', 1);
        $query   = ['id' => $this->id];
        if ($option !== 'nolink') {
            // Add param to save lastsearch_values or not
            $addSaveLastSearchValues = ($saveLastSearchValue == 1 ? 1 : 0);
            if ($saveLastSearchValue == -1 && isset($_SERVER['PHP_SELF']) && preg_match('/list\.php/', $_SERVER['PHP_SELF'])) {
                $addSaveLastSearchValues = 1;
            }
            if ($addSaveLastSearchValues) {
                $query = array_merge($query, ['save_lastsearch_values' => 1]);
            }
        }
        // TODO: replace with dolBuildUrl($baseurl, $query) once Dolibarr 22 support is dropped (function introduced in Dolibarr 24)
        $url = $baseurl . '?' . http_build_query($query);

        $linkclose = '';
        if (empty($noToolTip)) {
            if (getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER')) {
                $label      = $langs->trans('Show' . dol_ucfirst($this->element));
                $linkclose .= ' alt="' . dolPrintHTMLForAttribute($label) . '"';
            }
            $linkclose .= ($label ? ' title="' . dolPrintHTMLForAttribute($label) . '"' : ' title="tocomplete"');
            $linkclose .= $dataParams . ' class="' . $classForTooltip . ($moreCSS ? ' ' . $moreCSS : '') . '"';
        } else {
            $linkclose = ($moreCSS ? ' class="' . $moreCSS . '"' : '');
        }

        if ($option == 'nolink') {
            $linkstart = '<span';
        } else {
            $linkstart = '<a href="' . $url . '"';
        }
        if ($option == 'blank') {
            $linkstart .= ' target=_blank';
        }
        $linkstart .= $linkclose . '>';
        if ($option == 'nolink') {
            $linkend = '</span>';
        } else {
            $linkend = '</a>';
        }

        $result .= $linkstart;

        if (empty($this->showphoto_on_popup)) {
            if ($withPicto) {
                $result .= img_object(($noToolTip ? '' : $label), ($this->picto ?: 'generic'), (($withPicto != 2) ? 'class="paddingright"' : ''), 0, 0, $noToolTip ? 0 : 1);
            }
            //@todo gérer le else
        }

        if ($withPicto != 2) {
            $result .= $this->ref;
        }

        $result .= $linkend;

        if ($withPicto != 2) {
            if ($withPicto == 3) {
                $addLabel = 1;
            }
            $result .= (($addLabel && property_exists($this, 'label')) ? '<span class="opacitymedium"> - <span contenteditable="true" data-field="label">' . dol_trunc($this->label, ($addLabel > 1 ? $addLabel : 0)) . '</span></span>' : '');
        }

        $hookmanager->initHooks([$this->element . 'dao']);
        $parameters = ['id' => $this->id, 'getnomurl' => &$result];
        $resHook    = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action);
        if ($resHook > 0) {
            $result = $hookmanager->resPrint;
        } else {
            $result .= $hookmanager->resPrint;
        }

        return $result;
    }

    /**
     * Initialize status labels
     *
     * @return void
     */
    protected function initStatutLabels(): void
    {
        global $langs;

        $this->labelStatus[static::STATUS_DELETED]      = $langs->transnoentitiesnoconv('Deleted');
        $this->labelStatus[static::STATUS_DRAFT]        = $langs->transnoentitiesnoconv('StatusDraft');
        $this->labelStatus[self::STATUS_VALIDATED]      = $langs->transnoentitiesnoconv('Validated');
        $this->labelStatus[self::STATUS_LOCKED]         = $langs->transnoentitiesnoconv('Locked');
        $this->labelStatus[self::STATUS_ARCHIVED]       = $langs->transnoentitiesnoconv('Archived');

        $this->labelStatusShort[static::STATUS_DELETED] = $langs->transnoentitiesnoconv('Deleted');
        $this->labelStatusShort[static::STATUS_DRAFT]   = $langs->transnoentitiesnoconv('StatusDraft');
        $this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Validated');
        $this->labelStatusShort[self::STATUS_LOCKED]    = $langs->transnoentitiesnoconv('Locked');
        $this->labelStatusShort[self::STATUS_ARCHIVED]  = $langs->transnoentitiesnoconv('Archived');
    }

    /**
     * Return the status type for dolGetStatus
     *
     * @param  int    $status ID status
     * @return string         Status type
     */
    protected function getStatusType(int $status): string
    {
        // TODO: PHP8+ replace switch with match()
        switch ($status) {
            case self::STATUS_VALIDATED:
                return 'status4';
            case self::STATUS_LOCKED:
                return 'status6';
            case self::STATUS_ARCHIVED:
                return 'status8';
            case self::STATUS_DELETED:
                return 'status9';
            default:
                return 'status' . $status;
        }
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
            $this->initStatutLabels();
        }

        return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $this->getStatusType($status), $mode);
    }

    /**
     * Return the label of the status
     *
     * @param  int    $mode 0 = long label, 1 = short label, 2 = Picto + short label, 3 = Picto, 4 = Picto + long label, 5 = Short label + Picto, 6 = Long label + Picto
     * @return string       Label of status
     */
    public function getLibStatut(int $mode = 0): string
    {
        return $this->LibStatut($this->status, $mode);
    }

    /**
     * Load the info information in the object
     *
     * @param  int  $id ID of object
     * @return void
     */
    public function info(int $id): void
    {
        $sql = 'SELECT t.rowid, t.date_creation as datec';
        if (!empty($this->isextrafieldmanaged) && $this->isextrafieldmanaged == 1) {
            $sql .= ', GREATEST(t.tms, te.tms) as datem';
        } else {
            $sql .= ', t.tms as datem';
        }
        if (!empty($this->fields['fk_user_creat'])) {
            $sql .= ', t.fk_user_creat';
        }
        if (!empty($this->fields['fk_user_modif'])) {
            $sql .= ', t.fk_user_modif';
        }
        $sql .= ' FROM ' . $this->db->prefix() . $this->table_element . ' as t';
        if (!empty($this->isextrafieldmanaged) && $this->isextrafieldmanaged == 1) {
            $sql .= ' LEFT JOIN ' . $this->db->prefix() . $this->table_element . '_extrafields as te ON te.fk_object = t.rowid';
        }
        $sql .= ' WHERE t.rowid = ' . $id;

        $result = $this->db->query($sql);
        if ($result) {
            if ($this->db->num_rows($result)) {
                $obj = $this->db->fetch_object($result);

                $this->id = $obj->rowid;

                if (!empty($this->fields['fk_user_creat'])) {
                    $this->user_creation_id = $obj->fk_user_creat;
                }
                if (!empty($this->fields['fk_user_modif'])) {
                    $this->user_modification_id = $obj->fk_user_modif;
                }

                $this->date_creation     = $this->db->jdate($obj->datec);
                $this->date_modification = empty($obj->datem) ? '' : $this->db->jdate($obj->datem);
            }

            $this->db->free($result);
        } else {
            dol_print_error($this->db);
        }
    }

    /**
     * Initialise object with example values
     * ID must be 0 if object instance is a specimen
     *
     * @return void
     */
    public function initAsSpecimen(): void
    {
        $this->initAsSpecimenCommon();
    }

    /**
     * Returns the reference to the following non-used object depending on the active numbering module
     *
     * @param  string $objectType Object type (used to get the numbering module if $this->element is not wanted for this object)
     *
     * @return string             Object free reference
     */
    public function getNextNumRef(string $objectType = ''): string
    {
        global $langs;

        $moduleNameUpperCase      = dol_strtoupper($this->module);
        $element                  = $objectType ?: $this->element;
        $modRefConfName           = $moduleNameUpperCase . '_' . dol_strtoupper($element) . '_ADDON';
        $numberingModuleName      = [$objectType ? $this->element . '/' . $element : $element => getDolGlobalString($modRefConfName, 'mod_' . $element . '_standard')];
        list($objNumberingModule) = saturne_require_objects_mod($numberingModuleName, dol_strtolower($this->module));

        if (is_object($objNumberingModule)) {
            $numRef = $objNumberingModule->getNextValue($this);

            if ($numRef != '' && $numRef != '-1') {
                return $numRef;
            } else {
                $this->error = $objNumberingModule->error;
                return '';
            }
        } else {
            print $langs->trans('Error') . ' ' . $langs->trans('ClassNotFound') . ' ' . getDolGlobalString($moduleNameUpperCase);
            return '';
        }
    }

    /**
     * Sets object to given categories
     *
     * Assign the object to all categories not yet assigned
     * Unassign object from existing categories not supplied in $categories (if $removeExisting==true)
     * If $removeExisting is false, existing categories are left untouched
     *
     * @param  int[]|int $categories     Category ID or array of Categories IDs
     * @param  string    $typeCateg      Category type ('customer', 'supplier', 'website_page', ...)
     *                                   defined into const class Categorie type
     * @param  boolean   $removeExisting True : Remove existing categories from Object if not supplies by $categories,
     *                                   False : Let them
     * @return int                       Return integer <0 if KO, >0 if OK
    */
    public function setCategories($categories, string $typeCateg = '', bool $removeExisting = false): int
    {
        if ($this->isCategoryManaged == 1) {
            $typeCateg = $typeCateg ?: $this->element;
            return parent::setCategoriesCommon($categories, $typeCateg, $removeExisting);
        }
        return 0;
    }

    /**
     * Delete all links between an object $this
     *
     * @param  int|null  $sourceid   Object source id
     * @param  string    $sourcetype Object source type
     * @param  int|null  $targetid   Object target id
     * @param  string    $targettype Object target type
     * @param  int       $rowid      Row id of line to delete. If defined, other parameters are not used.
     * @param  User|null $f_user     User that create
     * @param  int<0,1>  $notrigger  1 = Does not execute triggers, 0 = execute triggers
     * @return int                   > 0 if OK, <0 if KO
     * @see add_object_linked(), updateObjectLinked(), fetchObjectLinked()
     */
    public function deleteObjectLinked($sourceid = null, $sourcetype = '', $targetid = null, $targettype = '', $rowid = 0, $f_user = null, $notrigger = 0)
    {
        global $user;

        $deletesource = false;
        $deletetarget = false;
        $f_user       = isset($f_user) ? $f_user : $user;

        if (!empty($sourceid) && !empty($sourcetype) && !empty($targetid) && !empty($targettype)) {
            $deletesource = true;
            $deletetarget = true;
        } elseif (!empty($sourceid) && !empty($sourcetype) && empty($targetid) && empty($targettype)) {
            $deletesource = true;
        } elseif (empty($sourceid) && empty($sourcetype) && !empty($targetid) && !empty($targettype)) {
            $deletetarget = true;
        }

        $sourceid   = (!empty($sourceid) ? $sourceid : $this->id);
        $sourcetype = (!empty($sourcetype) ? $sourcetype : $this->element);
        $targetid   = (!empty($targetid) ? $targetid : $this->id);
        $targettype = (!empty($targettype) ? $targettype : $this->element);
        $this->db->begin();
        $error = 0;

        if (!$notrigger) {
            // Call trigger
            $this->context['link_id']          = $rowid;
            $this->context['link_source_id']   = $sourceid;
            $this->context['link_source_type'] = $sourcetype;
            $this->context['link_target_id']   = $targetid;
            $this->context['link_target_type'] = $targettype;

            $result = $this->call_trigger('OBJECT_LINK_DELETE', $f_user);
            if ($result < 0) {
                $error++;
            }
            // End call triggers
        }

        if (!$error) {
            $sql  = 'DELETE FROM ' . $this->db->prefix() . 'element_element';
            $sql .= ' WHERE';
            if ($rowid > 0) {
                $sql .= ' rowid = ' . ((int) $rowid);
            } else {
                if ($deletesource && $deletetarget) {
                    $sql .= ' (fk_source = ' . ((int) $sourceid) . " AND sourcetype = '" . $this->db->escape($sourcetype) . "')";
                    $sql .= ' AND';
                    $sql .= ' (fk_target = ' . ((int) $targetid) . " AND targettype = '" . $this->db->escape($targettype) . "')";
                } elseif ($deletesource) {
                    $sql .= ' fk_source = ' . ((int) $sourceid) . " AND sourcetype = '" . $this->db->escape($sourcetype) . "'";
                    $sql .= ' AND fk_target = ' . ((int) $sourceid) . " AND targettype = '" . $this->db->escape($targettype) . "'";
                } elseif ($deletetarget) {
                    $sql .= ' fk_target = ' . ((int) $targetid) . " AND targettype = '" . $this->db->escape($targettype) . "'";
                    $sql .= ' AND fk_source = ' . ((int) $targetid) . " AND sourcetype = '" . $this->db->escape($targettype) . "'";
                } else {
                    $sql .= ' (fk_source = ' . ((int) $sourceid) . " AND sourcetype = '" . $this->db->escape($sourcetype) . "')";
                    $sql .= ' OR';
                    $sql .= ' (fk_target = ' . ((int) $targetid) . " AND targettype = '" . $this->db->escape($targettype) . "')";
                }
            }

            dol_syslog(get_class($this) . "::deleteObjectLinked", LOG_DEBUG);
            if (!$this->db->query($sql)) {
                $this->error = $this->db->lasterror();
                $this->errors[] = $this->error;
                $error++;
            }
        }

        if (!$error) {
            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            return 0;
        }
    }

    /**
     * Write generic information of trigger description
     *
     * @return string Description to display in actioncomm->note_private
     */
    public function getTriggerDescription(): string
    {
        global $conf, $db, $langs, $mysoc;

        $ret = $langs->transnoentities('TechnicalID') . ' : ' . $this->id . '<br>';
        if (property_exists($this, 'ref') && !empty($this->ref)) {
            $ret .= $langs->transnoentities('Ref') . ' : ' . $this->ref . '<br>';
        }
        if (property_exists($this, 'label') && !empty($this->label)) {
            $ret .= $langs->transnoentities('Label') . ' : ' . $this->label . '<br>';
        }
        if (property_exists($this, 'description') && !empty($this->description)) {
            $ret .= $langs->transnoentities('Description') . ' : ' . $this->description . '<br>';
        }

        $ret .= $langs->transnoentities('DateCreation') . ' : ' . dol_print_date($this->date_creation, 'dayhoursec', 'tzuserrel') . '<br>';
        $ret .= $langs->transnoentities('DateModification') . ' : ' . dol_print_date($this->date_modification, 'dayhoursec', 'tzuserrel') . '<br>';

        if (property_exists($this, 'fk_user_creat') &&  !empty($this->fk_user_creat)) {
            $userTmp = new User($db);
            $result  = $userTmp->fetch($this->fk_user_creat);
            if ($result > 0) {
                $ret .= $langs->transnoentities('CreatedByLogin') . ' : ' . $userTmp->getFullName($langs) . '<br>';
            }
        }
        if (property_exists($this, 'fk_user_modif') && !empty($this->fk_user_modif)) {
            $userTmp = $userTmp ?? new User($db);
            $result  = $userTmp->fetch($this->fk_user_modif);
            if ($result > 0) {
                $ret .= $langs->transnoentities('ModifiedByLogin') . ' : ' . $userTmp->getFullName($langs) . '<br>';
            }
        }

        $ret .= $langs->transnoentities('EntityNumber') . ' : ' . $conf->entity . '<br>';
        $ret .= $langs->transnoentities('EntityName') . ' : ' . $mysoc->name . '<br>';

        if (property_exists($this, 'fk_soc') && isModEnabled('societe')) {
            $result = $this->fetch_thirdparty();
            if ($result > 0 && is_object($this->thirdparty)) {
                $ret .= $langs->transnoentities('ThirdParty') . ' : ' . (dol_strlen($this->thirdparty->name) > 0 ? $this->thirdparty->name : $langs->transnoentities('NoData')) . '<br>';
            }
        }
        if (property_exists($this, 'fk_project') && isModEnabled('project')) {
            $result = $this->fetchProject();
            if ($result > 0 && is_object($this->project)) {
                $ret .= $langs->transnoentities('Project') . ' : ' . $this->project->ref . ' ' . $this->project->title . '<br>';
            }
        }

        if (property_exists($this, 'note_public') && !empty($this->note_public)) {
            $ret .= $langs->transnoentities('NotePublic') . ' : ' . $this->note_public . '<br>';
        }
        if (property_exists($this, 'note_private') && !empty($this->note_private)) {
            $ret .= $langs->transnoentities('NotePrivate') . ' : ' . $this->note_private . '<br>';
        }
        if (property_exists($this, 'status') && !empty($this->status) && isset($this->fields['status']['arrayofkeyval'][$this->status])) {
            $ret .= $langs->transnoentities('Status') . ' : ' . $langs->transnoentities($this->fields['status']['arrayofkeyval'][$this->status]) . '<br>';
        }

        return $ret;
    }

    /**
     * Return a thumb for kanban views
     *
     * @param  string                   $option     Where point the link (0 => main card, 1,2 => shipment, 'nolink' => No link)
     * @param  array<string,mixed>|null $moreParams Parameters for load kanban view
     * @return string                               HTML code for Kanban thumb
     */
    public function getKanbanView(string $option = '', ?array $moreParams = null): string
    {
        $selected = (empty($moreParams['selected']) ? 0 : $moreParams['selected']);

        $out  = '<div class="box-flex-item box-flex-grow-zero">';
        $out .= '<div class="info-box info-box-sm">';
        $out .= '<span class="info-box-icon bg-infobox-action">';
        $out .= img_picto('', $this->picto);
        $out .= '</span>';
        $out .= '<div class="info-box-content">';
        $out .= '<span class="info-box-ref inline-block tdoverflowmax150 valignmiddle">' . (method_exists($this, 'getNomUrl') ? $this->getNomUrl() : $this->ref) . '</span>';
        if ($selected >= 0) {
            $out .= '<input id="cb' . $this->id . '" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="' . $this->id . '"' . ($selected ? ' checked="checked"' : '') . '>';
        }
        if (property_exists($this, 'label')) {
            $out .= '<div class="inline-block opacitymedium valignmiddle tdoverflowmax100">' . $this->label . '</div>';
        }
        if (property_exists($this, 'thirdparty') && is_object($this->thirdparty)) {
            $out .= '<br><div class="info-box-ref tdoverflowmax150">' . $this->thirdparty->getNomUrl(1) . '</div>';
        }
        if (method_exists($this, 'getLibStatut')) {
            $out .= '<br><div class="info-box-status">' . $this->getLibStatut(3) . '</div>';
        }
        $out .= '</div>';
        $out .= '</div>';
        $out .= '</div>';

        return $out;
    }

    /**
     * Return validation test result for a field
     *
     * @param  array  $fields     Array of properties of field to show
     * @param  string $fieldKey   Key of attribute
     * @param  string $fieldValue Value of attribute
     * @return bool               Return false if fail true on success, see $this->error for error message
     */
    public function validateField($fields, $fieldKey, $fieldValue): bool
    {
        global $langs;

        $validationResult = true;

        $commonValidationResult = parent::validateField($fields, $fieldKey, $fieldValue);

        if ($commonValidationResult) {
            $field = $fields[$fieldKey] ?? null;
            if (isset($field)) {
                if (isset($field['bounds']['min'])) {
                    $min = $field['bounds']['min'];
                    if ($fieldValue < $min) {
                        $this->setFieldError($fieldKey, $langs->trans('FieldMinValue', dol_strtolower(html_entity_decode($langs->trans($field['label']), ENT_COMPAT, 'UTF-8')), $min));
                        $validationResult = false;
                    }
                }
                if (isset($field['bounds']['max'])) {
                    $max = $field['bounds']['max'];
                    if ($fieldValue > $max) {
                        $this->setFieldError($fieldKey, $langs->trans('FieldMaxValue', dol_strtolower(html_entity_decode($langs->trans($field['label']), ENT_COMPAT, 'UTF-8')), $max));
                        $validationResult = false;
                    }
                }
            }
        }

        return $commonValidationResult && $validationResult;
    }
}
