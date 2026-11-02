<?php

/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/list/objectfields_list_export_xlsx.tpl.php
 * \ingroup saturne
 * \brief   Template page for the Excel export of a list, in place of the page
 */

/**
 * The following vars must be defined :
 * Globals    : $db, $langs
 * Parameters : $contextpage, $searchAll
 * Objects    : $extrafields, $object
 * Variables  : $arrayfields, $listXlsxExportBufferLevel, $resql (without its paging), $search, $search_array_options,
 *              $searchCategories, $title
 */

require_once __DIR__ . '/../../../class/saturnelistxlsxexport.class.php';

// The columns the user shows, in the order the user gave them, as the list header applies them
$listLayoutId = $listLayoutId ?? ($object->element ?? '');
if (!isset($listColumnWidths)) {
    $listColumnWidths = saturne_apply_list_layout($object, $arrayfields, $listLayoutId);
}
Form::multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $contextpage ?: $_SERVER['PHP_SELF']);

$xlsxExport = new SaturneListXlsxExport($db);
if ($searchAll) {
    $xlsxExport->filters[$langs->transnoentities('XlsxSearch')] = $searchAll;
}
$xlsxExport->addFieldFilters($object, $search);
// An object without extrafields reads 0 from getOptionalsFromPost()
$xlsxExport->addExtraFieldFilters($extrafields, $object->table_element, is_array($search_array_options ?? null) ? $search_array_options : []);
$xlsxExport->addCategoryFilters($searchCategories ?? []);

// A note the page adds to its title, such as a missing module, is not part of the name of the list
$xlsxExportTitle = $xlsxExport->getPlainText(preg_replace('/<small\b.*?<\/small>/is', '', (string) ($title ?? '')));
if ($xlsxExportTitle === '') {
    $xlsxExportTitle = $langs->transnoentities('List');
}

// The page drawn so far gives way to the workbook
while (ob_get_level() >= $listXlsxExportBufferLevel) {
    ob_end_clean();
}

$xlsxExport->export($object, $extrafields, $arrayfields, $resql, $xlsxExportTitle);
$db->close();
exit;
