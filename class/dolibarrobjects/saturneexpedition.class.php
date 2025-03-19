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
 * \file    class/dolibarrobjects/saturneexpedition.class.php
 * \ingroup digiquali
 * \brief   This file is a CRUD class file for SaturneExpedition (Create/Read/Update/Delete)
 */

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/expedition/class/expedition.class.php';

/**
 * Class for SaturneExpedition
 */
class SaturneExpedition extends Expedition
{
    /**
     * @var array Array with all fields and their property. Do not use it as a static var. It may be modified by constructor
     */
    public $fields = [
        'rowid' => ['type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'position' => 1, 'notnull' => 1, 'visible' => -1, 'noteditable' => 1, 'index' => 1, 'comment' => 'Id'],
        'tms' => ['type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'position' => 10, 'notnull' => 0, 'visible' => -1],
        'ref' => ['type' => 'varchar(30)', 'label' => 'Ref', 'enabled' => 1, 'position' => 20, 'notnull' => 1, 'visible' => -1, 'noteditable' => 1, 'index' => 1, 'searchall' => 1, 'showoncombobox' => 1, 'validate' => 1, 'comment' => 'Reference of object'],
        'entity' => ['type' => 'integer', 'label' => 'Entity', 'enabled' => 1, 'position' => 30, 'notnull' => 1, 'visible' => -1, 'index' => 1],
        'fk_soc' => ['type' => 'integer:Societe:societe/class/societe.class.php', 'label' => 'ThirdParty', 'picto' => 'company', 'enabled' => '$conf->societe->enabled', 'position' => 40, 'notnull' => 1, 'visible' => -1, 'index' => 1, 'css' => 'maxwidth500 widthcentpercentminusxx', 'foreignkey' => 'societe.rowid'],
        'fk_projet' => ['type' => 'integer:Project:projet/class/project.class.php:1:fk_statut=1', 'label' => 'Project', 'picto' => 'projet', 'enabled' => '$conf->project->enabled', 'position' => 50, 'notnull' => 0, 'visible' => -1, 'index' => 1, 'css' => 'maxwidth500 widthcentpercentminusxx', 'foreignkey' => 'projet.rowid'],
        'ref_ext' => ['type' => 'varchar(255)', 'label' => 'RefExt', 'enabled' => 1, 'position' => 60, 'notnull' => 0, 'visible' => -1],
        'ref_int' => ['type' => 'varchar(255)', 'label' => 'RefInt', 'enabled' => 1, 'position' => 70, 'notnull' => 0, 'visible' => -1],
        'ref_customer' => ['type' => 'varchar(255)', 'label' => 'RefCustomer', 'enabled' => 1, 'position' => 80, 'notnull' => 0, 'visible' => -1],
        'date_creation' => ['type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'position' => 90, 'notnull' => 0, 'visible' => -1],
        'fk_user_author' => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'picto' => 'user', 'enabled' => 1, 'position' => 100, 'notnull' => 0, 'visible' => -1, 'index' => 1, 'foreignkey' => 'user.rowid'],
        'fk_user_modif' => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'picto' => 'user', 'enabled' => 1, 'position' => 110, 'notnull' => 0, 'visible' => -1, 'index' => 1, 'foreignkey' => 'user.rowid'],
        'date_valid' => ['type' => 'datetime', 'label' => 'DateValidation', 'enabled' => 1, 'position' => 120, 'notnull' => 0, 'visible' => 5],
        'fk_user_valid' => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserValidation', 'picto' => 'user', 'enabled' => 1, 'position' => 130, 'notnull' => 0, 'visible' => -1, 'index' => 1, 'foreignkey' => 'user.rowid'],
        'date_delivery' => ['type' => 'datetime', 'label' => 'DateDelivery', 'enabled' => 1, 'position' => 140, 'notnull' => 0, 'visible' => -1],
        'date_expedition' => ['type' => 'datetime', 'label' => 'DateExpedition', 'enabled' => 1, 'position' => 150, 'notnull' => 0, 'visible' => -1],
        'fk_address' => ['type' => 'integer', 'label' => 'Address', 'enabled' => 1, 'position' => 160, 'notnull' => 0, 'visible' => -1],
        'fk_shipping_method' => ['type' => 'integer', 'label' => 'ShippingMethod', 'enabled' => 1, 'position' => 170, 'notnull' => 0, 'visible' => -1, 'index' => 1, 'default' => 0],
        'tracking_number' => ['type' => 'varchar(50)', 'label' => 'TrackingNumber', 'enabled' => 1, 'position' => 180, 'notnull' => 0, 'visible' => -1],
        'fk_statut' => ['type' => 'smallint(6)', 'label' => 'Status', 'enabled' => 1, 'position' => 190, 'notnull' => 0, 'visible' => -1, 'index' => 1, 'default' => 0],
        'billed' => ['type' => 'smallint(6)', 'label' => 'Billed', 'enabled' => 1, 'position' => 200, 'notnull' => 0, 'visible' => -1],
        'height' => ['type' => 'float', 'label' => 'Height', 'enabled' => 1, 'position' => 210, 'notnull' => 0, 'visible' => -1],
        'width' => ['type' => 'float', 'label' => 'Width', 'enabled' => 1, 'position' => 220, 'notnull' => 0, 'visible' => -1],
        'size_units' => ['type' => 'integer', 'label' => 'SizeUnits', 'enabled' => 1, 'position' => 230, 'notnull' => 0, 'visible' => -1],
        'size' => ['type' => 'float', 'label' => 'Size', 'enabled' => 1, 'position' => 240, 'notnull' => 0, 'visible' => -1],
        'weight_units' => ['type' => 'integer', 'label' => 'WeightUnits', 'enabled' => 1, 'position' => 250, 'notnull' => 0, 'visible' => -1],
        'weight' => ['type' => 'float', 'label' => 'Weight', 'enabled' => 1, 'position' => 260, 'notnull' => 0, 'visible' => -1],
        'note_private' => ['type' => 'text', 'label' => 'NotePrivate', 'enabled' => 1, 'position' => 270, 'notnull' => 0, 'visible' => -1],
        'note_public' => ['type' => 'text', 'label' => 'NotePublic', 'enabled' => 1, 'position' => 280, 'notnull' => 0, 'visible' => -1],
        'model_pdf' => ['type' => 'varchar(255)', 'label' => 'PDFTemplate', 'enabled' => 1, 'position' => 290, 'notnull' => 0, 'visible' => -1],
        'last_main_doc' => ['type' => 'varchar(255)', 'label' => 'LastMainDoc', 'enabled' => 1, 'position' => 300, 'notnull' => 0, 'visible' => -1],
        'fk_incoterms' => ['type' => 'integer', 'label' => 'IncotermCode', 'enabled' => '$conf->incoterm->enabled', 'position' => 310, 'notnull' => 0, 'visible' => -1],
        'location_incoterms' => ['type' => 'varchar(255)', 'label' => 'IncotermLabel', 'enabled' => '$conf->incoterm->enabled', 'position' => 320, 'notnull' => 0, 'visible' => -1],
        'import_key' => ['type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => 1, 'position' => 330, 'notnull' => 0, 'visible' => -1],
        'extraparams' => ['type' => 'varchar(255)', 'label' => 'ExtraParams', 'enabled' => 1, 'position' => 340, 'notnull' => 0, 'visible' => -1]
    ];
}
