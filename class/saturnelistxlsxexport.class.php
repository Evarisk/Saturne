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
 * \file    class/saturnelistxlsxexport.class.php
 * \ingroup saturne
 * \brief   Excel export of any list built on the list templates of Saturne
 */

require_once __DIR__ . '/saturnexlsxexport.class.php';

/**
 * Class for SaturneListXlsxExport
 *
 * Writes the lines of a list as the user sees them: the columns shown, in their order, each value typed. The summary
 * gives the filters and the breakdown by status; each column holding a code or a linked object gets a breakdown
 * sheet, and the creation date a sheet by month.
 */
class SaturneListXlsxExport extends SaturneXlsxExport
{
    /**
     * Types of field written as numbers, with their format
     */
    protected const NUMBER_FORMAT_OF_TYPES = [
        'integer' => 'int',
        'int'     => 'int',
        'double'  => 'decimal2',
        'real'    => 'decimal2',
        'price'   => 'money',
    ];

    /**
     * Types of extrafield a breakdown sheet is written for
     */
    protected const GROUPED_EXTRAFIELD_TYPES = ['select', 'sellist', 'radio', 'link', 'boolean'];

    /**
     * @var array<string, mixed> Cells of the codes and of the linked objects already printed, by column and value
     */
    protected array $cells = [];

    /**
     * Write the workbook of a list and send it
     *
     * @param  CommonObject $object      Object of the list, its fields in the order of the list
     * @param  ExtraFields  $extrafields Extrafields of the object, their labels fetched
     * @param  array        $arrayfields Columns of the list, the ones the user shows checked
     * @param  mixed        $resql       Result of the query of the list, without its paging
     * @param  string       $title       Title of the list
     * @return void
     */
    public function export(CommonObject $object, ExtraFields $extrafields, array $arrayfields, $resql, string $title): void
    {
        global $langs;

        $this->start($title, ucfirst($object->module ?: 'saturne'));
        $columns = $this->getColumns($object, $extrafields, $arrayfields);
        $records = $this->fetchRecords($object, $extrafields, $arrayfields, $columns, $resql);

        $row = $this->writeSummaryHead($title);
        $row = $this->writeSummaryFilters($row);

        $figures = [['label' => $langs->transnoentities('XlsxListLines'), 'value' => count($records)]];
        $created = array_filter(array_column($records, 'created'));
        if (!empty($created)) {
            $figures[] = ['label' => $langs->transnoentities('XlsxListFirstCreation'), 'value' => $this->getExcelDate(min($created)), 'format' => 'date'];
            $figures[] = ['label' => $langs->transnoentities('XlsxListLastCreation'), 'value' => $this->getExcelDate(max($created)), 'format' => 'date'];
        }
        $row = $this->writeSummaryKeyFigures($row, $figures);

        $statusLabels = $this->getStatusLabels($records);
        if (!empty($statusLabels)) {
            $statusLines  = [];
            $statusGroups = $this->groupBy($records, function (array $record): array {
                return [$record['status']];
            });
            foreach ($statusGroups as $status => $statusRecords) {
                $statusLines[$status] = [$statusLabels[$status], count($statusRecords), count($statusRecords) / max(1, count($records))];
            }
            ksort($statusLines);
            $row = $this->writeSummaryTable($row, $langs->transnoentities('XlsxListByStatus'), [$langs->transnoentities('Status'), $langs->transnoentities('XlsxLines'), $langs->transnoentities('XlsxShare')], $statusLines, ['text', 'int', 'percent']);
        }

        $this->addLinesSheet($columns, $records);
        foreach ($columns as $index => $column) {
            if ($column['grouped']) {
                $this->addColumnGroupsSheet($column, $index, $records);
            }
        }
        if (!empty($created)) {
            $this->addMonthsSheet($records, $statusLabels);
        }

        $this->writeSummaryContents($row);
        $this->send($title . '_' . dol_print_date($this->now, 'dayhourlog'));
    }

    /**
     * Columns the user shows, in the order of the list: the fields of the object, then its extrafields
     *
     * @param  CommonObject $object      Object of the list
     * @param  ExtraFields  $extrafields Extrafields of the object
     * @param  array        $arrayfields Columns of the list
     * @return array                     Columns: key, extrafield or not, label, kind of value, format, width, breakdown or not
     */
    public function getColumns(CommonObject $object, ExtraFields $extrafields, array $arrayfields): array
    {
        global $langs;

        $columns = [];
        foreach ($object->fields as $key => $field) {
            if (empty($arrayfields['t.' . $key]['checked'])) {
                continue;
            }
            $type = preg_replace('/\(.*$/', '', (string) ($field['type'] ?? ''));
            if ($key == 'status') {
                $kind = 'status';
            } elseif ($key == 'rowid') {
                $kind = 'id';
            } elseif (!empty($field['arrayofkeyval'])) {
                $kind = 'code';
            } elseif (in_array($type, ['date', 'datetime', 'timestamp'])) {
                $kind = $type == 'date' ? 'date' : 'datetime';
            } elseif (isset(self::NUMBER_FORMAT_OF_TYPES[$type])) {
                $kind = 'number';
            } elseif ($type == 'boolean') {
                $kind = 'boolean';
            } elseif (in_array($type, ['text', 'html'])) {
                $kind = 'longtext';
            } elseif (preg_match('/^(integer|sellist):/', $type)) {
                $kind = 'link';
            } else {
                $kind = 'text';
            }
            $columns[] = $this->getColumn($key, false, $this->getPlainText($langs->transnoentities($arrayfields['t.' . $key]['label'] ?? $field['label'] ?? $key)), $kind, $type, $key == 'ref');
        }

        $elementType = $object->table_element;
        foreach ($extrafields->attributes[$elementType]['label'] ?? [] as $key => $label) {
            $type = $extrafields->attributes[$elementType]['type'][$key] ?? '';
            if (empty($arrayfields['ef.' . $key]['checked']) || $type == 'separate') {
                continue;
            }
            if (in_array($type, ['date', 'datetime'])) {
                $kind = $type;
            } elseif (isset(self::NUMBER_FORMAT_OF_TYPES[$type])) {
                $kind = 'number';
            } elseif ($type == 'boolean') {
                $kind = 'boolean';
            } elseif (in_array($type, ['text', 'html'])) {
                $kind = 'longtext';
            } else {
                $kind = in_array($type, self::GROUPED_EXTRAFIELD_TYPES) ? 'code' : 'text';
            }
            $columns[] = $this->getColumn($key, true, $this->getPlainText($langs->transnoentities($label)), $kind, $type, false);
        }

        return $columns;
    }

    /**
     * Records of the list, each with its cells in the order of the columns, its value in each grouped column, its
     * status and its creation date
     *
     * @param  CommonObject $object      Object of the list, loaded with each line
     * @param  ExtraFields  $extrafields Extrafields of the object
     * @param  array        $arrayfields Columns of the list
     * @param  array        $columns     Columns, from getColumns()
     * @param  mixed        $resql       Result of the query of the list
     * @return array                     Records
     */
    public function fetchRecords(CommonObject $object, ExtraFields $extrafields, array $arrayfields, array $columns, $resql): array
    {
        global $hookmanager;

        $hasStatus  = method_exists($object, 'getLibStatut') && (array_key_exists('status', $object->fields) || array_key_exists('fk_statut', $object->fields));
        $hasCreated = array_key_exists('date_creation', $object->fields);

        $records = [];
        while ($obj = $this->db->fetch_object($resql)) {
            // As the list loads its lines, so that the hooks of the list fill the same columns
            $object->setVarsFromFetchObj($obj);
            if (array_key_exists('fk_statut', $object->fields) && !array_key_exists('status', $object->fields)) {
                $object->status = $object->fk_statut;
            }
            $parameters = [];
            $hookmanager->executeHooks('saturneSetVarsFromFetchObj', $parameters, $object);

            $record = ['cells' => [], 'groups' => [], 'status' => '', 'created' => 0];
            foreach ($columns as $index => $column) {
                $cell = $column['extra'] ? $this->getExtraFieldCell($object, $extrafields, $column, $obj) : $this->getFieldCell($object, $arrayfields, $column, $obj);
                $record['cells'][$index] = $cell;
                if ($column['grouped']) {
                    $record['groups'][$index] = (string) (is_array($cell) ? ($cell['value'] ?? '') : $cell);
                }
            }
            if ($hasStatus) {
                $record['status'] = (string) $object->status;
                $record['label']  = $this->getPlainText($object->getLibStatut(1));
            }
            if ($hasCreated) {
                $record['created'] = $this->getTimestamp($object->date_creation);
            }
            $records[] = $record;
        }

        return $records;
    }

    /**
     * Cell of a field of the object, as the list prints it: a hook of the list may fill it in its place
     *
     * @param  CommonObject $object      Object, loaded with the line
     * @param  array        $arrayfields Columns of the list
     * @param  array        $column      Column, from getColumns()
     * @param  object       $obj         Line read from the database
     * @return mixed                     Cell
     */
    public function getFieldCell(CommonObject $object, array $arrayfields, array $column, object $obj)
    {
        global $hookmanager;

        $key        = $column['key'];
        $field      = $object->fields[$key];
        $parameters = ['arrayfields' => $arrayfields, 'key' => $key, 'val' => $field, 'obj' => $obj];
        $hookmanager->executeHooks('saturnePrintFieldListLoopObject', $parameters, $object);
        if (!empty($hookmanager->resArray[$key])) {
            return $this->getHtmlCell((string) $hookmanager->resArray[$key]);
        }

        $value = $key == 'rowid' ? $object->id : ($object->$key ?? null);
        switch ($column['kind']) {
            case 'status':
                return method_exists($object, 'getLibStatut') ? $this->getPlainText($object->getLibStatut(1)) : $value;
            case 'id':
                return (int) $value;
            case 'date':
            case 'datetime':
                return $this->getExcelDate($value, $column['kind'] == 'datetime');
            case 'number':
                return ($value === null || $value === '') ? null : ($column['format'] == 'int' ? (int) $value : (float) $value);
            case 'boolean':
                return ($value === null || $value === '') ? null : yn(!empty($value) ? 1 : 0);
            case 'code':
            case 'link':
                // A linked object is fetched to print it: once per value for the whole list
                $cacheKey = 't.' . $key . '|' . (is_scalar($value) ? (string) $value : json_encode($value));
                if (!array_key_exists($cacheKey, $this->cells)) {
                    $this->cells[$cacheKey] = $this->getHtmlCell((string) $object->showOutputField($field, $key, $value));
                }

                return $this->cells[$cacheKey];
            default:
                return $this->getHtmlCell((string) $object->showOutputField($field, $key, $value));
        }
    }

    /**
     * Cell of an extrafield of the object, as the list prints it
     *
     * @param  CommonObject $object      Object, loaded with the line
     * @param  ExtraFields  $extrafields Extrafields of the object
     * @param  array        $column      Column, from getColumns()
     * @param  object       $obj         Line read from the database
     * @return mixed                     Cell
     */
    public function getExtraFieldCell(CommonObject $object, ExtraFields $extrafields, array $column, object $obj)
    {
        $value = $obj->{'options_' . $column['key']} ?? null;
        if ($value === null || $value === '') {
            return null;
        }
        switch ($column['kind']) {
            case 'date':
            case 'datetime':
                return $this->getExcelDate($value, $column['kind'] == 'datetime');
            case 'number':
                return $column['format'] == 'int' ? (int) $value : (float) $value;
            case 'boolean':
                return yn(!empty($value) ? 1 : 0);
            case 'code':
                $cacheKey = 'ef.' . $column['key'] . '|' . $value;
                if (!array_key_exists($cacheKey, $this->cells)) {
                    $this->cells[$cacheKey] = $this->getHtmlCell((string) $extrafields->showOutputField($column['key'], $value, '', $object->table_element));
                }

                return $this->cells[$cacheKey];
            default:
                return $this->getHtmlCell((string) $extrafields->showOutputField($column['key'], $value, '', $object->table_element));
        }
    }

    /**
     * Cell of a value printed as HTML: its text, and the URL of its link when it opens a card
     *
     * @param  string $html HTML of the value
     * @return mixed        Text, or array with the text and the URL
     */
    public function getHtmlCell(string $html)
    {
        $text = $this->getPlainText($html);
        $url  = $text !== '' ? $this->getLinkUrl($html) : '';

        return $url !== '' ? ['value' => $text, 'url' => $url] : $text;
    }

    /**
     * Full URL of the first link of a HTML text, the way a cell of the workbook opens it from outside Dolibarr
     *
     * @param  string $html HTML
     * @return string       URL, empty when there is no link to a page
     */
    public function getLinkUrl(string $html): string
    {
        if (!preg_match('/<a\b[^>]*\shref="([^"]+)"/i', $html, $matches)) {
            return '';
        }
        $url = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
        if (preg_match('/^https?:\/\//i', $url)) {
            return $url;
        }
        // A link of Dolibarr starts with the path of its root: the cell needs the address of the instance before it
        if (strpos($url, '/') !== 0) {
            return '';
        }
        if (strpos($url, DOL_URL_ROOT . '/') === 0) {
            $url = substr($url, strlen(DOL_URL_ROOT));
        }

        return DOL_MAIN_URL_ROOT . $url;
    }

    /**
     * Sheet of the lines of the list, the reference kept in view when scrolling to the right
     *
     * @param  array $columns Columns, from getColumns()
     * @param  array $records Records, from fetchRecords()
     * @return void
     */
    public function addLinesSheet(array $columns, array $records): void
    {
        global $langs;

        $sheet = $this->addSheet($langs->transnoentities('List'), $langs->transnoentities('XlsxListSheetDescription'));
        $lines = array_map(function (array $record): array {
            return $record['cells'];
        }, $records);

        $frozenColumns = 0;
        foreach ($columns as $index => $column) {
            if ($column['key'] == 'ref' && !$column['extra']) {
                $frozenColumns = $index + 1;
            }
        }

        $this->writeTable($sheet, 1, $columns, $lines, $frozenColumns);
    }

    /**
     * Sheet of the breakdown of the lines by the value of a column: the count and the share of each value, the
     * largest first and the lines without value last
     *
     * @param  array $column  Column, from getColumns()
     * @param  int   $index   Index of the column
     * @param  array $records Records, from fetchRecords()
     * @return void
     */
    public function addColumnGroupsSheet(array $column, int $index, array $records): void
    {
        global $langs;

        $notFilled = $langs->transnoentities('XlsxNotFilled');
        $groups    = $this->groupBy($records, function (array $record) use ($index, $notFilled): array {
            return [($record['groups'][$index] ?? '') !== '' ? $record['groups'][$index] : $notFilled];
        });
        // One value for every line, or a different value on each line, says nothing a breakdown would add
        if (count($groups) < 2 || (count($groups) == count($records) && !isset($groups[$notFilled]))) {
            return;
        }

        uksort($groups, function ($first, $second) use ($groups, $notFilled): int {
            if ($first === $notFilled || $second === $notFilled) {
                return $first === $notFilled ? 1 : -1;
            }

            return count($groups[$second]) <=> count($groups[$first]) ?: strnatcasecmp((string) $first, (string) $second);
        });

        $lines = [];
        foreach ($groups as $label => $groupRecords) {
            $lines[] = [(string) $label, count($groupRecords), count($groupRecords) / max(1, count($records))];
        }

        $sheet = $this->addSheet($langs->transnoentities('XlsxListBy', $column['label']), $langs->transnoentities('XlsxListBySheetDescription', $column['label']));
        $this->writeTable($sheet, 1, [
            ['label' => $column['label'], 'width' => 42],
            ['label' => $langs->transnoentities('XlsxLines'), 'width' => 10, 'format' => 'int'],
            ['label' => $langs->transnoentities('XlsxShare'), 'width' => 10, 'format' => 'percent'],
        ], $lines, 1);
    }

    /**
     * Sheet of the lines created each month, every month between the first and the last one, by status
     *
     * @param  array $records      Records, from fetchRecords()
     * @param  array $statusLabels Labels of the statuses, by status
     * @return void
     */
    public function addMonthsSheet(array $records, array $statusLabels): void
    {
        global $langs;

        $created  = array_filter(array_column($records, 'created'));
        $byMonth  = $this->groupBy(array_filter($records, function (array $record): bool {
            return !empty($record['created']);
        }), function (array $record): array {
            return [$this->getMonthKey($record['created'])];
        });
        $columns  = [
            ['label' => $langs->transnoentities('XlsxMonth'), 'width' => 16, 'format' => 'month'],
            ['label' => $langs->transnoentities('XlsxListCreated'), 'width' => 12, 'format' => 'int'],
        ];
        foreach ($statusLabels as $statusLabel) {
            $columns[] = ['label' => $statusLabel, 'width' => 14, 'format' => 'int'];
        }

        $lines = [];
        foreach ($this->getMonthKeys(min($created), max($created)) as $monthKey) {
            $monthRecords = $byMonth[$monthKey] ?? [];
            $line         = [$this->getMonthExcelDate($monthKey), count($monthRecords)];
            foreach (array_keys($statusLabels) as $status) {
                $line[] = count(array_filter($monthRecords, function (array $record) use ($status): bool {
                    return (string) $record['status'] === (string) $status;
                }));
            }
            $lines[] = $line;
        }

        $sheet = $this->addSheet($langs->transnoentities('XlsxListByMonth'), $langs->transnoentities('XlsxListByMonthSheetDescription'));
        $this->writeTable($sheet, 1, $columns, $lines, 1);
    }

    /**
     * Labels of the statuses of the records, by status, in the order of the statuses
     *
     * @param  array $records Records, from fetchRecords()
     * @return array          Labels, by status
     */
    public function getStatusLabels(array $records): array
    {
        $labels = [];
        foreach ($records as $record) {
            if ($record['status'] !== '' && !isset($labels[$record['status']])) {
                $labels[$record['status']] = $record['label'];
            }
        }
        ksort($labels);

        return $labels;
    }

    /**
     * Column of the lines sheet: its kind of value sets its format and its width
     *
     * @param  string $key     Key of the field
     * @param  bool   $extra   Extrafield or field of the object
     * @param  string $label   Label of the column
     * @param  string $kind    Kind of value: status, id, code, date, datetime, number, boolean, longtext, link, text
     * @param  string $type    Type of the field, without its size
     * @param  bool   $isRef   Column of the reference
     * @return array           Column
     */
    protected function getColumn(string $key, bool $extra, string $label, string $kind, string $type, bool $isRef): array
    {
        $formats = ['date' => 'date', 'datetime' => 'datetime', 'id' => 'int', 'longtext' => 'longtext'];
        $widths  = ['date' => 12, 'datetime' => 16, 'id' => 8, 'number' => 12, 'boolean' => 8, 'longtext' => 60, 'status' => 16];

        return [
            'key'     => $key,
            'extra'   => $extra,
            'label'   => $label,
            'kind'    => $kind,
            'format'  => $kind == 'number' ? self::NUMBER_FORMAT_OF_TYPES[$type] : ($formats[$kind] ?? 'text'),
            'width'   => $isRef ? 18 : ($widths[$kind] ?? 24),
            'grouped' => in_array($kind, ['code', 'link', 'boolean']) && !$isRef,
        ];
    }
}
