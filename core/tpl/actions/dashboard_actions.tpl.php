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
 * \file    core/tpl/actions/dashboard_actions.tpl.php
 * \ingroup saturne
 * \brief   Template page for dashboard actions
 */

/**
 * The following vars must be defined :
 * Global     : $conf, $db, $langs, $moduleName, $moduleNameLowerCase, $user
 * Parameters : $action
 * Variable   : $upload_dir
 */

if ($action == 'adddashboardinfo' || $action == 'closedashboardinfo') {
    $data                = json_decode(file_get_contents('php://input'), true);
    $dashboardWidgetName = $data['dashboardWidgetName'];
    $confName            = dol_strtoupper($moduleName) . '_DISABLED_DASHBOARD_INFO';
    $visible             = json_decode($user->conf->$confName);

    if ($action == 'adddashboardinfo') {
        unset($visible->$dashboardWidgetName);
    } else {
        $visible->$dashboardWidgetName = 0;
    }

    $tabparam[$confName] = json_encode($visible);

    dol_set_user_param($db, $conf, $user, $tabparam);
    $action = '';
}

if ($action == 'dashboardfilter') {
    $data     = json_decode(file_get_contents('php://input'), true);
    $confName = strtoupper($moduleName) . '_DASHBOARD_CONFIG';
    $config   = json_decode($user->conf->$confName) ?? new stdClass();

    $widget = $data['widget'] ?? null;
    if (!isset($config->widgets) && $widget != null) {
        $config->widgets = new stdClass();
    }
    $graph  = $data['graph'] ?? null;
    if (!isset($config->graphs) && $graph != null) {
        $config->graphs = new stdClass();
    }
    $filters = $data['graphFilters'] ?? [];
    if (!isset($config->filters)) {
        $config->filters = new stdClass();
    }

    if ($widget != null) {
        if (isset($config->widgets->$widget)) {
            unset($config->widgets->$widget);
        } else {
            $config->widgets->$widget = false;
        }
    }
    if ($graph != null) {
        if (isset($config->graphs->$graph->hide)) {
            unset($config->graphs->$graph);
        } else {
            if (!isset($config->graphs->$graph)) {
                $config->graphs->$graph = new stdClass();
            }
            $config->graphs->$graph->hide = true;
        }
    }
    foreach ($filters as $filter => $filterValue) {
        $config->filters->$filter = $filterValue;
    }

    $tabparam[$confName] = json_encode($config);
    dol_set_user_param($db, $conf, $user, $tabparam);
    $action = '';
}

if ($action == 'generate_csv') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!empty($data)) {
        $now   = dol_now();
        $title = $data['title'];

        $title     = strip_tags($title);
        $titleName = str_replace(' ', '_', $title);
        $titleName = dol_sanitizeFileName(dol_strtolower($titleName));
        $fileName  = dol_print_date($now, 'dayxcard') . '_' . $titleName . '.csv';

        $labels  = $data['labels'];
        $dataset = $data['data'];

        $mode = 0; // Two-dimension graph
        $line = 1;

        $fp = fopen($upload_dir . '/temp/' . $fileName, 'w');

        // Empty line and title
        fputcsv($fp, []);
        fputcsv($fp, [1 => $title]);
        fputcsv($fp, []);

        $header = [1 => ''];
        if (is_array($labels) && !empty($labels)) {
            foreach ($labels as $label) {
                $line++;
                $header[$line] = $label['label'];
            }
            if (!empty($data['type']) && $data['type'] == 'bar') {
                $mode = 1;
                fputcsv($fp, $header);
            }
        }

        if ($mode == 1 && !empty($dataset) && !empty($labels)) {
            foreach ($dataset as $values) {
                $row = 0;
                if (!empty($values[0])) {
                    $content[$row] = $values[0];
                }
                foreach ($labels as $labelArray) {
                    $row++;
                    if (!empty($values['y_combined_' . $labelArray['label']])) {
                        $content[$row] = $values['y_combined_' . $labelArray['label']];
                    } elseif (!empty($values[$row])) {
                        $content[$row] = $values[$row];
                    } else {
                        $content[$row] = 0;
                    }
                }
                fputcsv($fp, $content);
            }
        } elseif (!empty($dataset)) {
            $labelIndex = 2;
            foreach ($dataset as $value) {
                $content = [0 => $header[$labelIndex], 1 => $value];
                $labelIndex++;
                fputcsv($fp, $content);
            }
        }

        fputcsv($fp, []);
        fclose($fp);

        $documentUrl = DOL_URL_ROOT . '/document.php';
        header("Location: " . $documentUrl . '?modulepart=' . $moduleNameLowerCase . '&file=' . urlencode('temp/' . $fileName) . '&entity=' . $conf->entity);
        exit;
    } else {
        setEventMessages($langs->trans('ErrorMissingData'), [], 'errors');
    }
    $action = '';
}
