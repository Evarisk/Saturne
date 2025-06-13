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
 * \file    class/dolibarrobjects/saturnesupplierproposal.class.php
 * \ingroup digiquali
 * \brief   This file is a CRUD class file for SaturneSupplierProposal (Create/Read/Update/Delete)
 */

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/supplier_proposal/class/supplier_proposal.class.php';

/**
 * Class for SaturneSupplierProposal
 */
class SaturneSupplierProposal extends SupplierProposal
{
    /**
     * @var array Array with all fields and their property. Do not use it as a static var. It may be modified by constructor
     */
    public $fields = [
        'rowid' => ['type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'position' => 1, 'notnull' => 1, 'visible' => -1, 'noteditable' => 1, 'index' => 1, 'comment' => 'Id'],
        'tms' => ['type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'position' => 10, 'notnull' => 0, 'visible' => -1],
        'ref' => ['type' => 'varchar(30)', 'label' => 'Ref', 'enabled' => 1, 'position' => 20, 'notnull' => 1, 'visible' => -1, 'noteditable' => 1, 'index' => 1, 'searchall' => 1, 'showoncombobox' => 1, 'validate' => 1, 'comment' => 'Reference of object'],
        'entity' => ['type' => 'integer', 'label' => 'Entity', 'enabled' => 1, 'position' => 30, 'notnull' => 1, 'visible' => -1, 'index' => 1]
    ];
}
