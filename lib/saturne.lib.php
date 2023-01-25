<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
 * Prepare admin pages header
 *
 * @return array $head 	Selectable tabs
 */
function saturne_admin_prepare_head(): array
{
	global $langs, $conf;

	$langs->load('saturne@saturne');

	$h = 0;
	$head = [];

    $head[$h][0] = dol_buildpath('/saturne/admin/setup.php', 1);
    $head[$h][1] = '<i class="fas fa-cog pictofixedwidth"></i>' . $langs->trans('Settings');
    $head[$h][2] = 'settings';
    $h++;

	$head[$h][0] = dol_buildpath('/saturne/admin/media_gallery.php', 1);
	$head[$h][1] = '<i class="fas fa-image pictofixedwidth"></i>' . $langs->trans('MediaGallery');
	$head[$h][2] = 'media_gallery';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'saturne@saturne');

	return $head;
}
