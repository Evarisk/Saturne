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
 * \file    lib/saturne.lib.php
 * \ingroup saturne
 * \brief   Library files with common functions for Saturne.
 */

/**
 * Prepare admin pages header.
 *
 * @return array $head Selectable tabs.
 */
function saturne_admin_prepare_head(): array
{
    // Global variables definitions.
    global $langs, $conf;

    // Load translation files required by the page.
    saturne_load_langs();

    // Initialize values.
    $h    = 0;
    $head = [];

    $head[$h][0] = dol_buildpath('/saturne/admin/setup.php', 1);
    $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-cog pictofixedwidth"></i>' . $langs->trans('ModuleSettings') : '<i class="fas fa-cog"></i>';
    $head[$h][2] = 'settings';
    $h++;

    $head[$h][0] = dol_buildpath('/saturne/admin/redirection.php', 1) . '?module_name=Saturne';
    $head[$h][1] = $conf->browser->layout == 'classic' ? '<i class="fas fa-forward pictofixedwidth"></i>' . $langs->trans('Redirections') : '<i class="fas fa-forward"></i>';
    $head[$h][2] = 'redirection';
    $h++;

    $head[$h][0] = dol_buildpath('/saturne/admin/about.php', 1) . '?module_name=Saturne';
    $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fab fa-readme pictofixedwidth"></i>' . $langs->trans('About') : '<i class="fab fa-readme"></i>';
    $head[$h][2] = 'about';
    $h++;

    $head[$h][0] = dol_buildpath('/saturne/admin/information.php', 1) . '?filename=saturne_dev&tab_name=information';
    $head[$h][1] = '<i class="fas fa-hands-helping pictofixedwidth"></i>' . $langs->trans('Contributing');
    $head[$h][2] = 'information';
    $h++;

    $head[$h][0] = dol_buildpath('/saturne/admin/information.php', 1) . '?filename=evarisk_modules&tab_name=evariskModule';
    $head[$h][1] = '<i class="fas fa-cogs pictofixedwidth"></i>' . $langs->trans('SaturneModule', 'Evarisk');
    $head[$h][2] = 'evariskModule';

    $h++;
    $head[$h][0] = dol_buildpath('/saturne/admin/information.php', 1) . '?filename=eoxia_modules&tab_name=eoxiaModule';
    $head[$h][1] = '<i class="fas fa-cogs pictofixedwidth"></i>' . $langs->trans('SaturneModule', 'Eoxia');
    $head[$h][2] = 'eoxiaModule';
    $h++;

    complete_head_from_modules($conf, $langs, null, $head, $h, 'saturne@saturne');

    complete_head_from_modules($conf, $langs, null, $head, $h, 'saturne@saturne', 'remove');

    return $head;
}
