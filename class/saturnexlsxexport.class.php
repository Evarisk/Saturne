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
 * \file    class/saturnexlsxexport.class.php
 * \ingroup saturne
 * \brief   Base of the Excel exports of the modules: one workbook, a summary, then one sheet per view of the data
 */

// Load PhpSpreadsheet, shipped with the Dolibarr core
require_once DOL_DOCUMENT_ROOT . '/includes/phpoffice/phpspreadsheet/src/autoloader.php';
require_once DOL_DOCUMENT_ROOT . '/includes/Psr/autoloader.php';
require_once PHPEXCELNEW_PATH . 'Spreadsheet.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Class for SaturneXlsxExport
 *
 * A module extends it and only writes its own sheets: this class sets the workbook up, lays the tables out with
 * their formats, writes the summary with the contents of the workbook and sends the file. The PhpSpreadsheet of the
 * core (1.12) writes charts out of the order of the schema, so the views are tables ready to be charted rather than
 * native charts.
 */
class SaturneXlsxExport
{
    /**
     * Colours of the head rows: background, text
     */
    public const HEAD_COLOURS = ['263C5C', 'FFFFFF'];

    /**
     * Colour of the secondary texts
     */
    public const MUTED_COLOUR = '64748B';

    /**
     * Colour of the links
     */
    public const LINK_COLOUR = '1D4ED8';

    /**
     * Colour of the borders of the tables
     */
    public const BORDER_COLOUR = 'D9D9D9';

    /**
     * Colours of the cells of a matrix, from the lowest count to the highest: background, text
     */
    public const HEAT_COLOURS = [
        ['EEF2F7', '1E293B'],
        ['C9D6E8', '1E293B'],
        ['93ACD1', '1E293B'],
        ['5A7DB3', 'FFFFFF'],
        ['263C5C', 'FFFFFF'],
    ];

    /**
     * Excel number format of each column format
     */
    protected const NUMBER_FORMATS = [
        'int'      => '0',
        'decimal'  => '0.0',
        'decimal2' => '0.00',
        'percent'  => '0%',
        'money'    => '#,##0.00',
        'month'    => 'mmmm yyyy',
    ];

    /**
     * Highest length of the text of a cell
     */
    protected const CELL_TEXT_MAX_LENGTH = 32000;

    /**
     * @var DoliDB Database handler
     */
    public DoliDB $db;

    /**
     * @var array<string, string> Filters of the exported list, as label => value, printed on the summary
     */
    public array $filters = [];

    /**
     * @var Spreadsheet Workbook being built
     */
    protected Spreadsheet $spreadsheet;

    /**
     * @var array<int, array{sheet: Worksheet, description: string, lines: int}> Sheets after the summary, for its contents
     */
    protected array $contents = [];

    /**
     * @var string Excel format of a date, from the date format of the user language
     */
    protected string $dateFormat = 'dd/mm/yyyy';

    /**
     * @var int Date of the export
     */
    protected int $now = 0;

    /**
     * @var int Level of the output buffer opened by start(), which send() drops
     */
    protected int $outputBufferLevel = 0;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;
    }

    /**
     * Button exporting a list: the request of the list again, with its filters and its sort, and the action
     * export_xlsx the list page answers with the workbook
     *
     * @param  array  $moreParams Other parameters of the list page, by name
     * @param  string $moreCss    More CSS classes of the button
     * @return string             HTML of the button
     */
    public static function getListExportButton(array $moreParams = [], string $moreCss = 'reposition'): string
    {
        global $langs;

        $params = ['action' => 'export_xlsx', 'token' => newToken()];
        // The search form of a list posts its filters: they are read from the query string and from the post alike
        foreach (array_merge($_GET, $_POST) as $key => $value) {
            if (preg_match('/^(search_|sortfield$|sortorder$|contextpage$)/', (string) $key) && $value !== '') {
                $params[$key] = $value;
            }
        }
        $url = $_SERVER['PHP_SELF'] . '?' . http_build_query(array_merge($params, $moreParams));

        return dolGetButtonTitle($langs->trans('XlsxExport'), $langs->trans('XlsxExportHelp'), 'fa fa-file-excel', $url, '', 1, ['morecss' => $moreCss]);
    }

    /**
     * Add the search criteria of the fields of an object to the filters printed on the summary
     *
     * @param  CommonObject $object Object of the list
     * @param  array        $search Search criteria, by field, the dates as <field>_dtstart and <field>_dtend
     * @return void
     */
    public function addFieldFilters(CommonObject $object, array $search): void
    {
        global $langs;

        foreach ($object->fields as $key => $field) {
            $label = $this->getPlainText($langs->transnoentities($field['label'] ?? $key));
            $start = $search[$key . '_dtstart'] ?? '';
            $end   = $search[$key . '_dtend'] ?? '';
            if (!empty($start) || !empty($end)) {
                $this->filters[$label] = (!empty($start) ? dol_print_date($start, 'day') : '...') . ' - ' . (!empty($end) ? dol_print_date($end, 'day') : '...');
                continue;
            }

            $values = [];
            foreach ((array) ($search[$key] ?? []) as $value) {
                if ($value === '' || $value === null || (string) $value === '-1' || is_array($value) || is_object($value)) {
                    continue;
                }
                // A code reads as its label, a linked object as its name; any other criterion is printed as typed
                $output = '';
                if (!empty($field['arrayofkeyval'])) {
                    $output = array_key_exists($value, $field['arrayofkeyval']) ? $this->getPlainText($langs->transnoentities($field['arrayofkeyval'][$value])) : '';
                } elseif (is_numeric($value) && (int) $value > 0 && strpos($field['type'] ?? '', 'integer:') === 0) {
                    $output = $this->getPlainText($object->showOutputField($field, $key, $value));
                }
                $values[] = $output !== '' ? $output : (string) $value;
            }
            if (!empty($values)) {
                $this->filters[$label] = implode(', ', $values);
            }
        }
    }

    /**
     * Add the search criteria of the extrafields of an object to the filters printed on the summary
     *
     * @param  ExtraFields $extrafields        Extrafields, their labels fetched
     * @param  string      $elementType        Element type of the extrafields
     * @param  array       $searchArrayOptions Search criteria, by search_options_<extrafield>
     * @return void
     */
    public function addExtraFieldFilters(ExtraFields $extrafields, string $elementType, array $searchArrayOptions): void
    {
        global $langs;

        foreach ($searchArrayOptions as $searchKey => $value) {
            $key  = preg_replace('/^search_options_/', '', (string) $searchKey);
            $type = $extrafields->attributes[$elementType]['type'][$key] ?? '';
            if (empty($extrafields->attributes[$elementType]['label'][$key]) || $value === '' || $value === null || $value === [] || is_object($value)) {
                continue;
            }
            // As the list reads them: an empty select is 0, an empty link is -1
            if (!is_array($value) && ((string) $value === '-1' || (in_array($type, ['select', 'sellist']) && (string) $value === '0'))) {
                continue;
            }
            $label = $this->getPlainText($langs->transnoentities($extrafields->attributes[$elementType]['label'][$key]));
            // A date is searched between two days
            if (is_array($value) && (array_key_exists('start', $value) || array_key_exists('end', $value))) {
                if (!empty($value['start']) || !empty($value['end'])) {
                    $this->filters[$label] = (!empty($value['start']) ? dol_print_date($value['start'], 'day') : '...') . ' - ' . (!empty($value['end']) ? dol_print_date($value['end'], 'day') : '...');
                }
                continue;
            }
            $value  = is_array($value) ? implode(',', $value) : (string) $value;
            $output = $this->getPlainText($extrafields->showOutputField($key, $value, '', $elementType));

            $this->filters[$label] = $output !== '' ? $output : $value;
        }
    }

    /**
     * Add the category criteria of a list to the filters printed on the summary
     *
     * @param  array $categoryTokens Criteria: a category id, signed with - when excluded, or NOTCATEGORIZED
     * @return void
     */
    public function addCategoryFilters(array $categoryTokens): void
    {
        global $langs;

        require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

        $labels = [];
        foreach ($categoryTokens as $categoryToken) {
            $categoryId = ltrim((string) $categoryToken, '+-');
            if ($categoryId === 'NOTCATEGORIZED') {
                $label = $langs->transnoentities('NotCategorized');
            } else {
                $category = new Categorie($this->db);
                if ((int) $categoryId <= 0 || $category->fetch((int) $categoryId) <= 0) {
                    continue;
                }
                $label = $category->label;
            }
            $labels[] = strpos((string) $categoryToken, '-') === 0 ? $langs->transnoentities('XlsxExcluded', $label) : $label;
        }
        if (!empty($labels)) {
            $this->filters[$langs->transnoentities('Categories')] = implode(', ', $labels);
        }
    }

    /**
     * Start a new workbook, its first sheet kept for the summary
     *
     * @param  string      $title   Title of the workbook, in its properties
     * @param  string      $creator Name of the module writing it
     * @return Spreadsheet          Workbook
     */
    public function start(string $title, string $creator = 'Saturne'): Spreadsheet
    {
        global $langs;

        $this->now        = dol_now();
        $this->dateFormat = strtolower($langs->transnoentities('FormatDateShortJava'));
        $this->contents   = [];

        // Whatever is printed while the workbook is built, such as a PHP warning on an instance showing them,
        // would be sent ahead of the file and break it: it is held back, then dropped by send()
        ob_start();
        $this->outputBufferLevel = ob_get_level();

        $this->spreadsheet = new Spreadsheet();
        $this->spreadsheet->getProperties()->setCreator($creator)->setTitle($title);
        $this->spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);
        $this->spreadsheet->getDefaultStyle()->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        return $this->spreadsheet;
    }

    /**
     * Send the workbook to the browser, opened on its summary
     *
     * @param  string $fileName Name of the file, without extension
     * @return void
     */
    public function send(string $fileName): void
    {
        $this->spreadsheet->setActiveSheetIndex(0);

        while ($this->outputBufferLevel > 0 && ob_get_level() >= $this->outputBufferLevel) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . dol_sanitizeFileName($fileName) . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($this->spreadsheet);
        $writer->save('php://output');
    }

    /**
     * Add a sheet after the others, listed in the contents of the summary
     *
     * @param  string    $title       Title of the sheet
     * @param  string    $description Description of the sheet, printed in the contents of the summary
     * @return Worksheet              Sheet
     */
    public function addSheet(string $title, string $description): Worksheet
    {
        $sheet = $this->spreadsheet->createSheet();
        $this->setSheetTitle($sheet, $title);
        $this->contents[] = ['sheet' => $sheet, 'description' => $description, 'lines' => 0];

        return $sheet;
    }

    /**
     * Name a sheet and set up its printing: landscape, one page width, the company, the date and the page number
     *
     * @param  Worksheet $sheet Sheet
     * @param  string    $title Title of the sheet
     * @return void
     */
    public function setSheetTitle(Worksheet $sheet, string $title): void
    {
        global $mysoc;

        // A sheet name holds 31 characters at most, without []:*?/\, and two sheets cannot share it. Cut by
        // dol_substr(), as dol_trunc() cuts nothing on a page setting MAIN_DISABLE_TRUNC, such as a dashboard
        $title     = trim(dol_substr(trim(preg_replace('/\s+/u', ' ', str_replace(['[', ']', ':', '*', '?', '/', '\\'], ' ', $title))), 0, 31));
        $baseTitle = $title;
        $suffix    = 1;
        while ($this->spreadsheet->sheetNameExists($title) && $this->spreadsheet->getSheetByName($title) !== $sheet) {
            $suffix++;
            $title = trim(dol_substr($baseTitle, 0, 31 - strlen(' (' . $suffix . ')'))) . ' (' . $suffix . ')';
        }
        $sheet->setTitle($title);

        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setPaperSize(PageSetup::PAPERSIZE_A4)->setFitToWidth(1)->setFitToHeight(0);
        // A & starts a code in a header or a footer: a literal one is doubled
        $sheet->getHeaderFooter()->setOddHeader('&L' . str_replace('&', '&&', (string) $mysoc->name) . '&C&B' . str_replace('&', '&&', $title) . '&R' . dol_print_date($this->now, 'day'));
        $sheet->getHeaderFooter()->setOddFooter('&R&P / &N');
    }

    /**
     * Write a table: a head row, then one row per line, each column with its width and its format
     *
     * A cell is a value, or an array with its value and the colours of the cell. A text is always written as a
     * text, so that a description starting like a formula is never computed. A date is an Excel date, from
     * getExcelDate().
     *
     * @param  Worksheet $sheet         Sheet
     * @param  int       $headRow       Row of the head
     * @param  array     $columns       Columns: label, width, format (text, longtext, int, decimal, decimal2, percent, money, date, datetime, month), colours of the head
     * @param  array     $lines         Lines of cells, in the order of the columns
     * @param  int       $frozenColumns Columns kept in view when scrolling to the right
     * @return int                      Last row written
     */
    public function writeTable(Worksheet $sheet, int $headRow, array $columns, array $lines, int $frozenColumns = 0): int
    {
        $columns    = array_values($columns);
        $lastColumn = Coordinate::stringFromColumnIndex(max(1, count($columns)));
        foreach ($columns as $index => $column) {
            $letter = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValueExplicit($letter . $headRow, (string) $column['label'], DataType::TYPE_STRING);
            $sheet->getColumnDimension($letter)->setWidth($column['width'] ?? 14);
        }
        $sheet->getStyle('A' . $headRow . ':' . $lastColumn . $headRow)->applyFromArray($this->getColourStyle(self::HEAD_COLOURS, true) + [
            'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        // A column may take the colour of what it counts, as the legend of a chart would
        foreach ($columns as $index => $column) {
            if (!empty($column['colours'])) {
                $sheet->getStyle(Coordinate::stringFromColumnIndex($index + 1) . $headRow)->applyFromArray($this->getColourStyle($column['colours'], true));
            }
        }

        $row = $headRow;
        foreach ($lines as $line) {
            $row++;
            foreach (array_values($line) as $index => $cell) {
                $this->writeCell($sheet, Coordinate::stringFromColumnIndex($index + 1) . $row, $cell);
            }
        }

        if ($row > $headRow) {
            foreach ($columns as $index => $column) {
                $letter = Coordinate::stringFromColumnIndex($index + 1);
                $this->setFormat($sheet, $letter . ($headRow + 1) . ':' . $letter . $row, $column['format'] ?? 'text');
            }
        }
        $sheet->getStyle('A' . $headRow . ':' . $lastColumn . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(self::BORDER_COLOUR);

        $sheet->setAutoFilter('A' . $headRow . ':' . $lastColumn . $row);
        $sheet->freezePane(Coordinate::stringFromColumnIndex($frozenColumns + 1) . ($headRow + 1));
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($headRow, $headRow);

        foreach ($this->contents as $index => $content) {
            if ($content['sheet'] === $sheet) {
                $this->contents[$index]['lines'] = count($lines);
            }
        }

        return $row;
    }

    /**
     * Write a cell: a value, or an array with its value, its colours and the URL it opens
     *
     * @param  Worksheet $sheet      Sheet
     * @param  string    $coordinate Coordinate of the cell
     * @param  mixed     $cell       Value, or array with value, colours, bold and url
     * @return void
     */
    public function writeCell(Worksheet $sheet, string $coordinate, $cell): void
    {
        if (is_array($cell)) {
            if (!empty($cell['colours'])) {
                $sheet->getStyle($coordinate)->applyFromArray($this->getColourStyle($cell['colours'], $cell['bold'] ?? true));
            } elseif (!empty($cell['bold'])) {
                $sheet->getStyle($coordinate)->getFont()->setBold(true);
            }
            // A reference opens its card in Dolibarr; on a coloured cell, the link keeps the text colour of the cell
            if (!empty($cell['url']) && isset($cell['value']) && $cell['value'] !== '') {
                $sheet->getCell($coordinate)->getHyperlink()->setUrl((string) $cell['url']);
                $sheet->getStyle($coordinate)->getFont()->setUnderline(true);
                if (empty($cell['colours'])) {
                    $sheet->getStyle($coordinate)->getFont()->getColor()->setRGB(self::LINK_COLOUR);
                }
            }
            $cell = $cell['value'] ?? null;
        }
        if ($cell === null || $cell === '') {
            return;
        }
        if (is_int($cell) || is_float($cell)) {
            $sheet->setCellValueExplicit($coordinate, $cell, DataType::TYPE_NUMERIC);
        } else {
            $sheet->setCellValueExplicit($coordinate, (string) $cell, DataType::TYPE_STRING);
            // A text of several lines reads line by line, whatever the format of its column
            if (strpos((string) $cell, "\n") !== false) {
                $sheet->getStyle($coordinate)->getAlignment()->setWrapText(true);
            }
        }
    }

    /**
     * Apply a column format to a range
     *
     * @param  Worksheet $sheet  Sheet
     * @param  string    $range  Range
     * @param  string    $format Format: text, longtext, int, decimal, decimal2, percent, money, date, datetime, month
     * @return void
     */
    public function setFormat(Worksheet $sheet, string $range, string $format): void
    {
        $style = $sheet->getStyle($range);
        if (isset(self::NUMBER_FORMATS[$format])) {
            $style->getNumberFormat()->setFormatCode(self::NUMBER_FORMATS[$format]);
        } elseif ($format === 'date') {
            $style->getNumberFormat()->setFormatCode($this->dateFormat);
        } elseif ($format === 'datetime') {
            $style->getNumberFormat()->setFormatCode($this->dateFormat . ' hh:mm');
        } elseif ($format === 'longtext') {
            $style->getAlignment()->setWrapText(true);
        }
    }

    /**
     * Write the head of the summary on the first sheet: the title, the company, the date and the author of the export
     *
     * @param  string $title    Title of the export
     * @param  string $subtitle Subtitle, printed after the title
     * @return int              Next free row
     */
    public function writeSummaryHead(string $title, string $subtitle = ''): int
    {
        global $langs, $mysoc, $user;

        $sheet = $this->getSummarySheet();
        $this->setSheetTitle($sheet, $langs->transnoentities('XlsxSummary'));
        $sheet->getPageSetup()->setFitToHeight(1);
        $sheet->getColumnDimension('A')->setWidth(42);
        $sheet->getColumnDimension('B')->setWidth(16);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(80);

        $sheet->setCellValueExplicit('A1', $title . ($subtitle !== '' ? ' - ' . $subtitle : ''), DataType::TYPE_STRING);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB(self::HEAD_COLOURS[0]);
        $sheet->setCellValueExplicit('A2', (string) $mysoc->name, DataType::TYPE_STRING);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->setCellValueExplicit('A3', $langs->transnoentities('XlsxExportedOn', dol_print_date($this->now, 'dayhour'), $user->getFullName($langs)), DataType::TYPE_STRING);
        $sheet->getStyle('A3')->getFont()->setItalic(true)->getColor()->setRGB(self::MUTED_COLOUR);

        return 5;
    }

    /**
     * Write the filters of the exported list on the summary
     *
     * @param  int $row First row
     * @return int      Next free row
     */
    public function writeSummaryFilters(int $row): int
    {
        global $langs;

        $sheet = $this->getSummarySheet();
        $this->writeSummarySection($row, $langs->transnoentities('XlsxFilters'));
        if (empty($this->filters)) {
            $row++;
            $sheet->setCellValueExplicit('A' . $row, $langs->transnoentities('XlsxNoFilter'), DataType::TYPE_STRING);
            $sheet->getStyle('A' . $row)->getFont()->setItalic(true)->getColor()->setRGB(self::MUTED_COLOUR);
        }
        foreach ($this->filters as $label => $value) {
            $row++;
            $sheet->setCellValueExplicit('A' . $row, (string) $label, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('B' . $row, (string) $value, DataType::TYPE_STRING);
            $sheet->mergeCells('B' . $row . ':D' . $row);
        }

        return $row + 2;
    }

    /**
     * Write the key figures on the summary, each with its share when it is a part of a whole
     *
     * @param  int    $row     First row
     * @param  array  $figures Figures: label, value, total the share is computed on, format of the value
     * @param  string $title   Title of the section, the key figures by default
     * @return int             Next free row
     */
    public function writeSummaryKeyFigures(int $row, array $figures, string $title = ''): int
    {
        global $langs;

        $sheet = $this->getSummarySheet();
        $this->writeSummarySection($row, $title !== '' ? $title : $langs->transnoentities('XlsxKeyFigures'));
        foreach ($figures as $figure) {
            $row++;
            $sheet->setCellValueExplicit('A' . $row, (string) $figure['label'], DataType::TYPE_STRING);
            if (isset($figure['value'])) {
                $this->writeCell($sheet, 'B' . $row, $figure['value']);
                $this->setFormat($sheet, 'B' . $row, $figure['format'] ?? 'int');
            }
            if (!empty($figure['total']) && is_numeric($figure['value'] ?? null)) {
                $sheet->setCellValueExplicit('C' . $row, $figure['value'] / $figure['total'], DataType::TYPE_NUMERIC);
                $this->setFormat($sheet, 'C' . $row, 'percent');
            }
            if (!empty($figure['help'])) {
                $sheet->setCellValueExplicit('D' . $row, (string) $figure['help'], DataType::TYPE_STRING);
                $sheet->getStyle('D' . $row)->getFont()->setItalic(true)->getColor()->setRGB(self::MUTED_COLOUR);
            }
        }

        return $row + 2;
    }

    /**
     * Write a small table on the summary, under a section title: a breakdown ready for a chart
     *
     * @param  int    $row     First row
     * @param  string $label   Title of the section
     * @param  array  $heads   Heads of the columns, at most four
     * @param  array  $lines   Lines of cells
     * @param  array  $formats Format of each column
     * @return int             Next free row
     */
    public function writeSummaryTable(int $row, string $label, array $heads, array $lines, array $formats = []): int
    {
        $sheet = $this->getSummarySheet();
        $this->writeSummarySection($row, $label);
        $row++;
        foreach (array_values($heads) as $index => $head) {
            $sheet->setCellValueExplicit(Coordinate::stringFromColumnIndex($index + 1) . $row, (string) $head, DataType::TYPE_STRING);
            if ($index > 0 && in_array($formats[$index] ?? 'text', ['int', 'decimal', 'decimal2', 'percent', 'money'])) {
                $sheet->getStyle(Coordinate::stringFromColumnIndex($index + 1) . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }
        }
        $sheet->getStyle('A' . $row . ':D' . $row)->getFont()->setBold(true);
        foreach ($lines as $line) {
            $row++;
            foreach (array_values($line) as $index => $cell) {
                $coordinate = Coordinate::stringFromColumnIndex($index + 1) . $row;
                $this->writeCell($sheet, $coordinate, $cell);
                $this->setFormat($sheet, $coordinate, $formats[$index] ?? 'text');
            }
        }

        return $row + 2;
    }

    /**
     * Write the contents of the workbook on the summary, each sheet a link to it
     *
     * @param  int $row First row
     * @return int      Last row written
     */
    public function writeSummaryContents(int $row): int
    {
        global $langs;

        $sheet = $this->getSummarySheet();
        $this->writeSummarySection($row, $langs->transnoentities('XlsxContents'));
        $row++;
        $sheet->setCellValueExplicit('A' . $row, $langs->transnoentities('XlsxSheet'), DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('B' . $row, $langs->transnoentities('XlsxLines'), DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('D' . $row, $langs->transnoentities('Description'), DataType::TYPE_STRING);
        $sheet->getStyle('A' . $row . ':D' . $row)->getFont()->setBold(true);
        $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        foreach ($this->contents as $content) {
            $row++;
            $title = $content['sheet']->getTitle();
            $sheet->setCellValueExplicit('A' . $row, $title, DataType::TYPE_STRING);
            $sheet->getCell('A' . $row)->getHyperlink()->setUrl("sheet://'" . str_replace("'", "''", $title) . "'!A1");
            $sheet->getStyle('A' . $row)->getFont()->setUnderline(true)->getColor()->setRGB(self::LINK_COLOUR);
            $sheet->setCellValueExplicit('B' . $row, $content['lines'], DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('D' . $row, $content['description'], DataType::TYPE_STRING);
        }
        $sheet->getStyle('D1:D' . $row)->getAlignment()->setWrapText(true)->setIndent(1);

        return $row;
    }

    /**
     * Write the title of a section of the summary, across its four columns
     *
     * @param  int    $row   Row
     * @param  string $label Title of the section
     * @return void
     */
    public function writeSummarySection(int $row, string $label): void
    {
        $sheet = $this->getSummarySheet();
        $sheet->setCellValueExplicit('A' . $row, $label, DataType::TYPE_STRING);
        $sheet->mergeCells('A' . $row . ':D' . $row);
        $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray($this->getColourStyle(self::HEAD_COLOURS, true));
    }

    /**
     * Add a matrix sheet: the count of each pair of a row and a column, coloured by its weight, with the totals
     *
     * @param  string   $title        Title of the sheet
     * @param  string   $description  Description of the sheet, printed in the contents of the summary
     * @param  string   $rowsLabel    Head of the column of the row labels
     * @param  array    $rowLabels    Labels of the rows, by key, in their order
     * @param  array    $columnLabels Labels of the columns, by key, in their order
     * @param  array    $counts       Counts, by row key then column key
     * @param  array    $rowTotals    Total of each row, by key, when a record counts in several columns
     * @param  int|null $grandTotal   Number of records, when a record counts in several rows
     * @return Worksheet              Sheet
     */
    public function addMatrixSheet(string $title, string $description, string $rowsLabel, array $rowLabels, array $columnLabels, array $counts, array $rowTotals = [], ?int $grandTotal = null): Worksheet
    {
        global $langs;

        $sheet   = $this->addSheet($title, $description);
        $columns = [['label' => $rowsLabel, 'width' => 34]];
        foreach ($columnLabels as $columnLabel) {
            $columns[] = ['label' => $columnLabel, 'width' => 12, 'format' => 'int'];
        }
        $columns[] = ['label' => $langs->transnoentities('XlsxTotal'), 'width' => 10, 'format' => 'int'];

        $max = 0;
        foreach ($counts as $rowCounts) {
            $max = max($max, ...array_values($rowCounts ?: [0]));
        }

        $lines        = [];
        $rowsTotal    = 0;
        $columnTotals = array_fill_keys(array_keys($columnLabels), 0);
        foreach ($rowLabels as $rowKey => $rowLabel) {
            $cells = [$rowLabel];
            $total = 0;
            foreach (array_keys($columnLabels) as $columnKey) {
                $count  = (int) ($counts[$rowKey][$columnKey] ?? 0);
                $total += $count;
                $columnTotals[$columnKey] += $count;
                $cells[] = $count > 0 ? ['value' => $count, 'colours' => $this->getHeatColours($count, $max), 'bold' => false] : null;
            }
            $rowTotal   = (int) ($rowTotals[$rowKey] ?? $total);
            $rowsTotal += $rowTotal;
            $cells[]    = ['value' => $rowTotal, 'bold' => true];
            $lines[]    = $cells;
        }
        $lines[] = array_merge([['value' => $langs->transnoentities('XlsxTotal'), 'bold' => true]], array_map(function (int $total): array {
            return ['value' => $total, 'bold' => true];
        }, array_values($columnTotals)), [['value' => $grandTotal ?? $rowsTotal, 'bold' => true]]);

        $sheet->getRowDimension(1)->setRowHeight(150);
        $lastRow = $this->writeTable($sheet, 1, $columns, $lines, 1);
        $sheet->getStyle('B1:' . Coordinate::stringFromColumnIndex(count($columns)) . '1')->getAlignment()->setTextRotation(90)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_BOTTOM);
        $sheet->getStyle('B2:' . Coordinate::stringFromColumnIndex(count($columns)) . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        // The total line is not a row of the matrix: it stays out of the filter and of the count of the contents
        $sheet->setAutoFilter('A1:' . Coordinate::stringFromColumnIndex(count($columns)) . ($lastRow - 1));
        foreach ($this->contents as $index => $content) {
            if ($content['sheet'] === $sheet) {
                $this->contents[$index]['lines'] = count($rowLabels);
            }
        }

        return $sheet;
    }

    /**
     * Group records by key, a record may belong to several groups: by the keys returned for it, in their first order
     *
     * @param  array    $records Records
     * @param  callable $keysOf  Keys of a record, as an array
     * @return array             Records, by key
     */
    public function groupBy(array $records, callable $keysOf): array
    {
        $groups = [];
        foreach ($records as $record) {
            foreach (array_unique((array) $keysOf($record)) as $key) {
                $groups[$key][] = $record;
            }
        }

        return $groups;
    }

    /**
     * Months between two dates, both included, none skipped so that a series reads as a timeline
     *
     * @param  int   $from First date
     * @param  int   $to   Last date
     * @return array       Months, as YYYY-MM keys
     */
    public function getMonthKeys(int $from, int $to): array
    {
        $months = [];
        if (empty($from) || empty($to) || $from > $to) {
            return $months;
        }

        $year    = (int) dol_print_date($from, '%Y');
        $month   = (int) dol_print_date($from, '%m');
        $lastKey = dol_print_date($to, '%Y-%m');
        do {
            $key      = sprintf('%04d-%02d', $year, $month);
            $months[] = $key;
            $month++;
            if ($month > 12) {
                $month = 1;
                $year++;
            }
        } while ($key < $lastKey);

        return $months;
    }

    /**
     * Month of a date, as a YYYY-MM key
     *
     * @param  int|string|null $value Date, as a timestamp or as read from the database
     * @return string                 Month, empty when there is no date
     */
    public function getMonthKey($value): string
    {
        $timestamp = $this->getTimestamp($value);

        return !empty($timestamp) ? dol_print_date($timestamp, '%Y-%m') : '';
    }

    /**
     * Excel date of the first day of a month
     *
     * @param  string         $monthKey Month, as a YYYY-MM key
     * @return float|int|null           Excel date, to print with the month format
     */
    public function getMonthExcelDate(string $monthKey)
    {
        if (!preg_match('/^(\d{4})-(\d{2})$/', $monthKey, $matches)) {
            return null;
        }

        return ExcelDate::formattedPHPToExcel((int) $matches[1], (int) $matches[2], 1);
    }

    /**
     * Excel date of a date, at the day and the time the user reads
     *
     * @param  int|string|null $value    Date, as a timestamp or as read from the database
     * @param  bool            $withTime Keep the time of the day
     * @return float|int|null            Excel date, null when empty
     */
    public function getExcelDate($value, bool $withTime = false)
    {
        $timestamp = $this->getTimestamp($value);
        if (empty($timestamp)) {
            return null;
        }
        // Built from the printed date rather than from the timestamp, which PhpSpreadsheet would read as UTC
        $parts = array_map('intval', explode('-', dol_print_date($timestamp, '%Y-%m-%d-%H-%M-%S')));
        if (!$withTime) {
            return ExcelDate::formattedPHPToExcel($parts[0], $parts[1], $parts[2]);
        }

        return ExcelDate::formattedPHPToExcel($parts[0], $parts[1], $parts[2], $parts[3], $parts[4], $parts[5]);
    }

    /**
     * Timestamp of a date
     *
     * @param  int|string|null $value Date, as a timestamp or as read from the database
     * @return int                    Timestamp, 0 when empty
     */
    public function getTimestamp($value): int
    {
        if (empty($value)) {
            return 0;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }

        return (int) $this->db->jdate($value);
    }

    /**
     * Plain text of a text that may hold HTML, its line breaks kept, cut to what a cell holds
     *
     * @param  string|null $value Text
     * @return string             Plain text
     */
    public function getPlainText(?string $value): string
    {
        $value = (string) $value;
        if (dol_textishtml($value)) {
            // As a browser reads HTML: the line breaks of the source are spaces, a paragraph or a break ends a line
            // and a list item keeps its bullet
            $value = preg_replace('/[ \t\r\n]+/', ' ', $value);
            // Neither a script nor a style is text, and a choice list reads as the option chosen in it, as an
            // editable cell of a list prints it
            $value = preg_replace('/<(script|style)\b.*?<\/\1\s*>/is', '', $value);
            $value = preg_replace_callback('/<select\b.*?<\/select\s*>/is', function (array $matches): string {
                return preg_match('/<option\b[^>]*\sselected\b[^>]*>(.*?)<\/option\s*>/is', $matches[0], $option) ? $option[1] : '';
            }, $value);
            $value = preg_replace(['/<li\b[^>]*>/i', '/<br\s*\/?>|<\/p>|<\/div>|<\/h[1-6]>/i'], ["\n- ", "\n"], $value);
            // A tag goes with all its attributes, a quoted one may hold a >
            $value = preg_replace('/<\/?[a-z](?:[^>"\']|"[^"]*"|\'[^\']*\')*>/i', '', $value);
        }
        $text = str_replace(["\r\n", "\r"], "\n", dol_string_nohtmltag($value, 0));
        // Spaces around a line break and runs of empty lines are not text
        $text = trim(preg_replace(['/[ \t]*\n[ \t]*/', '/\n{3,}/'], ["\n", "\n\n"], $text));

        return dol_strlen($text) > self::CELL_TEXT_MAX_LENGTH ? dol_substr($text, 0, self::CELL_TEXT_MAX_LENGTH) : $text;
    }

    /**
     * Cell of a value printed as HTML: its text, and the URL of its link when it opens a page
     *
     * @param  string $html HTML of the value
     * @return mixed        Text, or array with the text and the URL
     */
    public function getHtmlCell(string $html)
    {
        $text = $this->getPlainText($html);
        $url  = '';
        if ($text !== '' && preg_match('/<a\b[^>]*\shref="([^"]+)"/i', $html, $matches)) {
            $url = $this->getAbsoluteUrl(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
        }

        return $url !== '' ? ['value' => $text, 'url' => $url] : $text;
    }

    /**
     * Full URL of a link of Dolibarr, the way a cell of the workbook opens it from outside Dolibarr
     *
     * @param  string $url URL, as Dolibarr prints it
     * @return string      Full URL, empty when it does not open a page
     */
    public function getAbsoluteUrl(string $url): string
    {
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
     * Colours of a count among others: the higher the count, the darker its cell
     *
     * @param  int   $count Count
     * @param  int   $max   Highest count
     * @return array        Background and text colours
     */
    public function getHeatColours(int $count, int $max): array
    {
        if ($max <= 0) {
            return self::HEAT_COLOURS[0];
        }
        $index = (int) ceil($count / $max * count(self::HEAT_COLOURS)) - 1;

        return self::HEAT_COLOURS[max(0, min(count(self::HEAT_COLOURS) - 1, $index))];
    }

    /**
     * Style array of the given colours
     *
     * @param  array $colours Background and text colours
     * @param  bool  $bold    Bold text
     * @return array          Style array
     */
    public function getColourStyle(array $colours, bool $bold = false): array
    {
        return [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $colours[0]]],
            'font' => ['color' => ['rgb' => $colours[1]], 'bold' => $bold],
        ];
    }

    /**
     * First sheet of the workbook, the summary
     *
     * @return Worksheet Sheet
     */
    protected function getSummarySheet(): Worksheet
    {
        return $this->spreadsheet->getSheet(0);
    }
}
