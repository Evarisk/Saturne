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
 * or see https://www.gnu.org/
 */

/**
 * \file    core/modules/saturne/modules_saturne.php
 * \ingroup saturne
 * \brief   File that contains parent class for saturne numbering models.
 */

/**
 *  Parent class to manage saturne numbering rules.
 */
abstract class ModeleNumRefSaturne
{
    /**
     * Dolibarr version of the loaded numbering module ref.
     * @var string
     */
    public string $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'.

    /**
     * @var string Numbering module ref prefix.
     */
    public string $prefix = '';

    /**
     * @var string Name.
     */
    public string $name = '';

    /**
     * @var string Error code (or message).
     */
    public string $error = '';

    /**
     * Return if a module can be used or not.
     *
     * @return bool true if module can be used.
     */
    public function isEnabled(): bool
    {
        return true;
    }

    /**
     * Returns the default description of the numbering template.
     *
     * @return string Text with description.
     */
    public function info(): string
    {
        global $langs;

        return $langs->trans('StandardModel', $this->prefix);
    }

    /**
     * Return an example of numbering.
     *
     * @return string Example.
     */
    public function getExample(): string
    {
        return $this->prefix . '0501-0001';
    }

    /**
     * Checks if the numbers already in the database do not
     * cause conflicts that would prevent this numbering working.
     *
     * @param  CommonObject $object Object we need next value for.
     * @return bool                 False if conflicted, true if OK.
     */
    public function canBeActivated(CommonObject $object): bool
    {
        global $conf, $langs, $db;

        $coyymm = ''; $max = '';

        $posIndice = strlen($this->prefix) + 6;
        $sql = 'SELECT MAX(CAST(SUBSTRING(ref FROM ' . $posIndice . ') AS SIGNED)) as max';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . $object->table_element;
        $sql .= " WHERE ref LIKE '" . $db->escape($this->prefix) . "____-%'";
        if ($object->ismultientitymanaged == 1) {
            $sql .= ' AND entity = ' . $conf->entity;
        }

        $resql = $db->query($sql);
        if ($resql) {
            $row = $db->fetch_row($resql);
            if ($row) {
                $coyymm = substr($row[0], 0, 6); $max = $row[0];
            }
        }
        if ($coyymm && !preg_match('/' . $this->prefix . '[0-9][0-9][0-9][0-9]/i', $coyymm)) {
            $this->error = $langs->trans('ErrorNumRefModel', $max);
            return false;
        }

        return true;
    }

    /**
     * Return next free value.
     *
     * @param  CommonObject $object Object we need next value for.
     * @return string               Value if OK, <0 if KO.
     * @throws Exception
     */
    public function getNextValue(CommonObject $object)
    {
        global $db, $conf;

        // First we get the max value.
        $posIndice = strlen($this->prefix) + 6;
        $sql = 'SELECT MAX(CAST(SUBSTRING(ref FROM ' . $posIndice . ') AS SIGNED)) as max';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . $object->table_element;
        $sql .= " WHERE ref LIKE '" . $db->escape($this->prefix) . "____-%'";
        if ($object->ismultientitymanaged == 1) {
            $sql .= ' AND entity = ' . $conf->entity;
        }
        
        $resql = $db->query($sql);
        if ($resql) {
            $obj = $db->fetch_object($resql);
            if ($obj) {
                $max = intval($obj->max);
            } else {
                $max = 0;
            }
        } else {
            dol_syslog(get_class($this) . '::getNextValue', LOG_DEBUG);
            return -1;
        }

        $date = !empty($object->date_creation) ? $object->date_creation : dol_now();
        $yymm = strftime('%y%m', $date);

        if ($max >= (pow(10, 4) - 1)) {
            $num = $max + 1; // If counter > 9999, we do not format on 4 chars, we take number as it is.
        } else {
            $num = sprintf('%04s', $max + 1);
        }

        dol_syslog(get_class($this) . '::getNextValue return ' . $this->prefix . $yymm . '-' . $num);
        return $this->prefix . $yymm . '-' . $num;
    }

    /**
     * Returns version of numbering module.
     *
     * @return string Value.
     */
    public function getVersion(): string
    {
        global $langs;

        if ($this->version == 'development') {
            return $langs->trans('VersionDevelopment');
        }
        if ($this->version == 'experimental') {
            return $langs->trans('VersionExperimental');
        }
        if ($this->version == 'dolibarr') {
            return DOL_VERSION;
        }
        if ($this->version) {
            return $this->version;
        }
        return $langs->trans('NotAvailable');
    }
}
