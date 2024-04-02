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
 * or see https://www.gnu.org/
 */

/**
 * \file    core/substitutions/functions_saturne.lib.php
 * \ingroup saturne
 * \brief   File of functions to substitutions array
 */

/** Function called to complete substitution array (before generating on ODT, or a personalized email)
 * functions xxx_completesubstitutionarray are called by make_substitutions() if file
 * is inside directory htdocs/core/substitutions
 *
 * @param  array              $substitutionarray Array with substitution key => val
 * @param  Translate          $langs             Output langs
 * @param  Object|string|null $object            Object to use to get values
 * @return void                                  The entry parameter $substitutionarray is modified
 * @throws Exception
 */
function saturne_completesubstitutionarray(array &$substitutionarray, Translate $langs, $object)
{
    $signatoryID = GETPOST('signatoryID', 'int');

    if (GETPOSTISSET('signatoryID') && $signatoryID > 0) {
        // Global variables definitions
        global $conf, $db;

        // Get module parameters
        $moduleName   = GETPOST('module_name', 'alpha');
        $objectType   = GETPOST('object_type', 'alpha');
        $documentType = GETPOST('document_type', 'alpha');

        $moduleNameLowerCase = strtolower($moduleName);

        // Load Saturne libraries
        require_once __DIR__ . '/../../class/saturnesignature.class.php';

        // Initialize technical objects
        $signatory = new SaturneSignature($db, $moduleNameLowerCase, $object->element);

        $signatory->fetch($signatoryID);

        $url = dol_buildpath('/custom/saturne/public/signature/add_signature.php?track_id=' . $signatory->signature_url  . '&entity=' . $conf->entity . '&module_name=' . $moduleNameLowerCase . '&object_type=' . $object->element . '&document_type=' . $documentType, 3);

        $substitutionarray['__SATURNE_SIGNATORY_URL__'] = '<a href=' . $url . ' target="_blank">' . $langs->transnoentities('SignatureEmailURL') . '</a>';
    }
}
