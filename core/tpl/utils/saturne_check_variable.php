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
 * \file    core/tpl/utils/saturne_check_variable.tpl.php
 * \ingroup saturne
 * \brief   Template page for check vars must be defined
 */

/**
 * The following vars must be defined :
 * Variable : $varsToChecks
 */

if (!empty($varsToChecks)) {
    if (!is_array($varsToChecks)) {
        throw new Exception('Error : varsToChecks should be an array');
    }
} else {
    throw new Exception('Error : varsToChecks is not set');
}

foreach ($varsToChecks as $keyVarsToCheck => $varsToCheck) {
    if (isset($$keyVarsToCheck)) {
        if ($varsToCheck['not_empty'] && empty($$keyVarsToCheck)) {
            throw new Exception('Error : ' . $keyVarsToCheck . ' should not be empty');
        }

        if (!call_user_func('is_' . $varsToCheck['type'], $$keyVarsToCheck)) {
            throw new Exception('Error : ' . $keyVarsToCheck . ' is not of type ' . $varsToCheck['type']);
        }
    } else {
        if ($varsToCheck['isset']) {
            throw new Exception('Error : ' . $keyVarsToCheck . ' should be set');
        }
    }
}
