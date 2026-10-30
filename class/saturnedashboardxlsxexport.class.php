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
 * \file    class/saturnedashboardxlsxexport.class.php
 * \ingroup saturne
 * \brief   Excel export of a whole Saturne dashboard: its widgets, its graphs and its lists
 */

require_once __DIR__ . '/saturnexlsxexport.class.php';

/**
 * Class for SaturneDashboardXlsxExport
 *
 * Writes what the dashboard shows: the widgets as key figures on the summary, then one sheet per graph, its
 * categories by row and its series by column, and one sheet per list.
 */
class SaturneDashboardXlsxExport extends SaturneXlsxExport
{
    /**
     * Write the workbook of a dashboard and send it
     *
     * @param  array  $dashboard     Dashboard, from SaturneDashboard::load_dashboard()
     * @param  object $hiddenWidgets Widgets the user closed, by key
     * @param  string $title         Title of the dashboard
     * @param  string $creator       Name of the module of the dashboard
     * @return void
     */
    public function export(array $dashboard, object $hiddenWidgets, string $title, string $creator): void
    {
        $this->start($title, $creator);
        $this->addGraphFilters($dashboard['graphsFilters'] ?? []);

        $row = $this->writeSummaryHead($title);
        $row = $this->writeSummaryFilters($row);
        foreach ($dashboard['widgets'] ?? [] as $widgets) {
            foreach ((array) $widgets as $key => $widget) {
                if (isset($hiddenWidgets->$key) || !is_array($widget) || empty($widget)) {
                    continue;
                }
                $figures = $this->getWidgetFigures($widget);
                if (!empty($figures)) {
                    $row = $this->writeSummaryKeyFigures($row, $figures, $this->getPlainText($widget['title'] ?? ''));
                }
            }
        }

        foreach ($dashboard['graphs'] ?? [] as $graphs) {
            foreach ((array) $graphs as $graph) {
                if (is_array($graph) && $this->hasGraphData($graph)) {
                    $this->addGraphSheet($graph);
                }
            }
        }
        foreach ($dashboard['lists'] ?? [] as $lists) {
            foreach ((array) $lists as $list) {
                if (is_array($list) && !empty($list['data']) && is_array($list['data'])) {
                    $this->addListSheet($list);
                }
            }
        }

        $this->writeSummaryContents($row);
        $this->send($title . '_' . dol_print_date($this->now, 'dayhourlog'));
    }

    /**
     * Add the filters of the graphs of the dashboard, with the value chosen by the user, to the filters printed on
     * the summary
     *
     * @param  array $graphFilters Filters of the graphs, from load_dashboard()
     * @return void
     */
    public function addGraphFilters(array $graphFilters): void
    {
        foreach ($graphFilters as $graphFilter) {
            $value = $graphFilter['currentValue'] ?? '';
            if (!is_scalar($value) || (string) $value === '' || (string) $value === '-1') {
                continue;
            }
            $label = $graphFilter['values'][$value] ?? $value;
            $this->filters[$this->getPlainText((string) ($graphFilter['title'] ?? ''))] = $this->getPlainText(is_array($label) ? ($label['label'] ?? '') : (string) $label);
        }
    }

    /**
     * Key figures of a widget: each line with its value typed, its tooltip and what the widget prints beside it
     *
     * @param  array $widget Widget, from load_dashboard()
     * @return array         Figures, for writeSummaryKeyFigures()
     */
    public function getWidgetFigures(array $widget): array
    {
        global $langs;

        $figures = [];
        foreach ((array) ($widget['label'] ?? []) as $index => $label) {
            if (empty($label)) {
                continue;
            }
            $content = $widget['content'][$index] ?? '';
            if ((is_scalar($content) ? (string) $content : '') === '') {
                $content = $widget['customContent'][$index] ?? '';
            }
            [$value, $format] = $this->getTypedValue($this->getPlainText(is_scalar($content) ? (string) $content : ''));

            $help = [];
            if (!empty($widget['moreContent'][$index]) && is_string($widget['moreContent'][$index])) {
                $help[] = $this->getPlainText($widget['moreContent'][$index]);
            }
            if (!empty($widget['tooltip'][$index])) {
                $help[] = $this->getPlainText($langs->transnoentities($widget['tooltip'][$index]));
            }
            $figures[] = ['label' => $this->getPlainText((string) $label), 'value' => $value, 'format' => $format, 'help' => implode(' - ', array_filter($help))];
        }

        return $figures;
    }

    /**
     * Whether the dashboard draws a graph: it does not when every value is empty
     *
     * @param  array $graph Graph, from load_dashboard()
     * @return bool         True when the graph has data
     */
    public function hasGraphData(array $graph): bool
    {
        if (empty($graph['data']) || !is_array($graph['data'])) {
            return false;
        }
        foreach ($graph['data'] as $data) {
            // A row of a graph of several series starts with its label
            $values = ($graph['dataset'] ?? 1) >= 2 ? array_slice(array_values((array) $data), 1) : [$data];
            foreach ($values as $value) {
                if (!empty($value)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Sheet of a graph: one row per category, the colours of the legend on the heads of the series, and each value
     * linked to the list the graph opens on a click
     *
     * @param  array $graph Graph, from load_dashboard()
     * @return void
     */
    public function addGraphSheet(array $graph): void
    {
        global $langs;

        $series = [];
        foreach ((array) ($graph['labels'] ?? []) as $key => $label) {
            $series[$key] = [
                'label'  => $this->getPlainText(is_array($label) ? (string) ($label['label'] ?? '') : (string) $label),
                'colour' => is_array($label) ? (string) ($label['color'] ?? '') : '',
            ];
        }
        $options = $this->getGraphOptions((string) ($graph['morehtmlright'] ?? ''));
        $links   = (array) ($options['links'] ?? []);
        $format  = $this->getGraphNumberFormat($graph);

        $lines = [];
        if (($graph['dataset'] ?? 1) >= 2) {
            $seriesList = array_values($series);
            $nbSeries   = 0;
            foreach ($graph['data'] as $data) {
                $nbSeries = max($nbSeries, count((array) $data) - 1);
            }
            $columns = [['label' => $langs->transnoentities('XlsxCategory'), 'width' => 24]];
            for ($index = 0; $index < $nbSeries; $index++) {
                $columns[] = ['label' => $seriesList[$index]['label'] ?? '', 'width' => 14, 'format' => $format, 'colours' => $this->getColoursOf($seriesList[$index]['colour'] ?? '')];
            }
            if ($nbSeries > 1) {
                $columns[] = ['label' => $langs->transnoentities('XlsxTotal'), 'width' => 12, 'format' => $format];
            }

            foreach (array_values($graph['data']) as $rowIndex => $data) {
                $data  = array_values((array) $data);
                $line  = [$this->getLinkedCell($this->getPlainText((string) ($data[0] ?? '')), $links[$rowIndex] ?? '')];
                $total = 0;
                for ($index = 0; $index < $nbSeries; $index++) {
                    $value   = is_numeric($data[$index + 1] ?? null) ? $data[$index + 1] + 0 : null;
                    $total  += (float) $value;
                    $line[]  = $this->getLinkedCell($value, $options['datasetLinks'][$index][$rowIndex] ?? '');
                }
                if ($nbSeries > 1) {
                    $line[] = ['value' => $total + 0, 'bold' => true];
                }
                $lines[] = $line;
            }
            $description = $langs->transnoentities('XlsxDashboardSeriesSheetDescription');
        } else {
            $columns = [
                ['label' => $langs->transnoentities('XlsxCategory'), 'width' => 34],
                ['label' => $langs->transnoentities('XlsxValue'), 'width' => 12, 'format' => $format],
                ['label' => $langs->transnoentities('XlsxShare'), 'width' => 10, 'format' => 'percent'],
            ];
            $total = 0;
            foreach ($graph['data'] as $value) {
                $total += is_numeric($value) ? (float) $value : 0;
            }
            $rowIndex = 0;
            foreach ($graph['data'] as $key => $value) {
                $value   = is_numeric($value) ? $value + 0 : null;
                $label   = $series[$key]['label'] ?? (string) $key;
                $colours = $this->getColoursOf($series[$key]['colour'] ?? '');
                $lines[] = [
                    $this->getLinkedCell($label, $links[$rowIndex] ?? '', $colours),
                    $value,
                    ($value !== null && $total > 0) ? $value / $total : null,
                ];
                $rowIndex++;
            }
            $description = $langs->transnoentities('XlsxDashboardValuesSheetDescription');
        }

        $sheet = $this->addSheet($this->getPlainText((string) ($graph['title'] ?? '')), $description);
        $this->writeTable($sheet, 1, $columns, $lines, 1);
    }

    /**
     * Format of the values of a graph: whole numbers when all of them are, such as counts, two decimals otherwise
     *
     * @param  array  $graph Graph, from load_dashboard()
     * @return string        Format: int or decimal2
     */
    public function getGraphNumberFormat(array $graph): string
    {
        foreach ($graph['data'] as $data) {
            $values = ($graph['dataset'] ?? 1) >= 2 ? array_slice(array_values((array) $data), 1) : [$data];
            foreach ($values as $value) {
                if (is_numeric($value) && floor((float) $value) != (float) $value) {
                    return 'decimal2';
                }
            }
        }

        return 'int';
    }

    /**
     * Sheet of a list of the dashboard, with its columns
     *
     * @param  array $list List, from load_dashboard()
     * @return void
     */
    public function addListSheet(array $list): void
    {
        global $langs;

        // As the dashboard prints it, the values of a line go under the heads by their position, not by their key
        $labels    = array_values((array) ($list['labels'] ?? []));
        $nbColumns = count($labels);
        foreach ($list['data'] as $data) {
            $nbColumns = max($nbColumns, count((array) $data));
        }
        $columns = [];
        for ($index = 0; $index < $nbColumns; $index++) {
            $columns[$index] = ['label' => $this->getPlainText($langs->transnoentities((string) ($labels[$index] ?? ''))), 'width' => $index == 0 ? 30 : 16];
        }

        // A column keeps its numbers only when all its values read as numbers of the same kind
        $cells   = [];
        $formats = [];
        foreach (array_values($list['data']) as $rowIndex => $data) {
            $values = array_values((array) $data);
            for ($index = 0; $index < $nbColumns; $index++) {
                $html = is_array($values[$index] ?? null) ? ($values[$index]['value'] ?? '') : '';
                $cell = $this->getHtmlCell(is_scalar($html) ? (string) $html : '');
                if (!is_array($cell) && $cell !== '') {
                    [$value, $format] = $this->getTypedValue($cell);
                    $formats[$index][$format] = true;
                    $cell = ['value' => $value, 'text' => $cell];
                }
                $cells[$rowIndex][$index] = $cell;
            }
        }
        for ($index = 0; $index < $nbColumns; $index++) {
            $format = array_keys($formats[$index] ?? []);
            if (count($format) == 1 && $format[0] != 'text') {
                $columns[$index]['format'] = $format[0];
            }
        }

        $lines = [];
        foreach ($cells as $rowCells) {
            $line = [];
            foreach ($rowCells as $index => $cell) {
                $line[] = (is_array($cell) && array_key_exists('text', $cell)) ? (isset($columns[$index]['format']) ? $cell['value'] : $cell['text']) : $cell;
            }
            $lines[] = $line;
        }

        $sheet = $this->addSheet($this->getPlainText((string) ($list['title'] ?? '')), $langs->transnoentities('XlsxDashboardListSheetDescription'));
        $this->writeTable($sheet, 1, $columns, $lines, 1);
    }

    /**
     * Value of a text read as a number when it is one: an integer, a decimal written the way the user writes it, or
     * a percentage
     *
     * @param  string $text Text
     * @return array        Value and its format: int, decimal2, percent or text
     */
    public function getTypedValue(string $text): array
    {
        $number    = trim(str_replace(["\u{00A0}", "\u{202F}"], ' ', $text));
        $isPercent = substr($number, -1) === '%';
        if ($isPercent) {
            $number = trim(substr($number, 0, -1));
        }
        // An amount printed with its currency, by price() for instance
        $isAmount = false;
        if (!$isPercent && preg_match('/^(.*\d)\s*(€|\$|£|[A-Z]{3})$/u', $number, $matches)) {
            $isAmount = true;
            $number   = $matches[1];
        }
        // An identifier, such as a SIRET number, is a code: its leading zeros and its digits are kept as written
        if ($number === '' || !preg_match('/^-?[\d .,]*\d$/', $number) || preg_match('/^0\d|^\d{13,}$/', $number)) {
            return [$text, 'text'];
        }
        $number = price2num($number);
        if (!is_numeric($number)) {
            return [$text, 'text'];
        }
        if ($isPercent) {
            return [(float) $number / 100, 'percent'];
        }
        if ($isAmount) {
            return [(float) $number, 'money'];
        }

        return strpos((string) $number, '.') === false ? [(int) $number, 'int'] : [(float) $number, 'decimal2'];
    }

    /**
     * Options of a graph for the dashboard JS, read back from the hidden input of SaturneDashboard::getGraphOptionsInput()
     *
     * @param  string $html HTML printed beside the title of the graph
     * @return array        Options: links, datasetLinks
     */
    public function getGraphOptions(string $html): array
    {
        if (!preg_match('/class="dashboard-graph-options"\s+value="([^"]*)"/', $html, $matches)) {
            return [];
        }
        $options = json_decode(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'), true);

        return is_array($options) ? $options : [];
    }

    /**
     * Cell opening a page of Dolibarr, when it has one
     *
     * @param  mixed  $value   Value of the cell
     * @param  string $url     URL, as the dashboard links it
     * @param  array  $colours Background and text colours
     * @return mixed           Cell
     */
    public function getLinkedCell($value, string $url, array $colours = [])
    {
        $url = $url !== '' ? $this->getAbsoluteUrl($url) : '';
        if ($url === '' && empty($colours)) {
            return $value;
        }

        return ['value' => $value, 'url' => $url, 'colours' => $colours, 'bold' => false];
    }

    /**
     * Background and text colours of a colour of the dashboard: a dark text on a light background, a white one
     * on a dark background
     *
     * @param  string $colour Colour, as #RRGGBB
     * @return array          Background and text colours, empty when the colour is not one
     */
    public function getColoursOf(string $colour): array
    {
        if (!preg_match('/^#?([0-9a-f]{6})$/i', trim($colour), $matches)) {
            return [];
        }
        [$red, $green, $blue] = array_map('hexdec', str_split($matches[1], 2));

        return [strtoupper($matches[1]), ($red * 299 + $green * 587 + $blue * 114) / 1000 > 150 ? '1E293B' : 'FFFFFF'];
    }
}
