<?php

/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/admin/admin_write_access_notice.tpl.php
 * \ingroup saturne
 * \brief   Template page telling a user that the settings are shown read only
 *
 * Expected variables, all prepared by the calling page :
 * $langs             - Translate object
 * $permissiontowrite - Result of saturne_check_admin_write_access(), computed here when missing
 */

$permissiontowrite = $permissiontowrite ?? saturne_check_admin_write_access();

if (!$permissiontowrite) {
    // Shown open and without a close button : nothing on the page reacts, the user has to know why
    print saturne_show_notice($langs->trans('ReadOnlyAdminAccessTitle'), $langs->trans('ReadOnlyAdminAccessMessage'), 'warning', 'notice-read-only-admin', true, false);
}
