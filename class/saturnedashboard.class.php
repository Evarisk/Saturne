<?php
/* Copyright (C) 2021-2024 EVARISK <technique@evarisk.com>
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
 * \file    class/saturnedashboard.class.php
 * \ingroup saturne
 * \brief   Class file for manage SaturneDashboard
 */

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/class/dolgraph.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

/**
 * Class for SaturneDashboard
 */
class SaturneDashboard
{
    /**
     * @var DoliDB Database handler
     */
    public DoliDB $db;

    /**
     * @var string Module name
     */
    public string $module = 'saturne';

    /**
     * Constructor
     *
     * @param DoliDB $db                  Database handler
     * @param string $moduleNameLowerCase Module name
     */
    public function __construct(DoliDB $db, string $moduleNameLowerCase = 'saturne')
    {
        $this->db     = $db;
        $this->module = $moduleNameLowerCase;
    }

    /**
     * Load dashboard info
     *
     * @param array|null  $moreParams Parameters for load dashboard info
     *
     * @return array
     */
    public function load_dashboard(?array $moreParams = []): array
    {
        require_once __DIR__ . '/../../' . $this->module . '/class/' . $this->module . 'dashboard.class.php';

        $className      = ucfirst($this->module) . 'Dashboard';
        $dashboard      = new $className($this->db);
        $dashboardDatas = $dashboard->load_dashboard($moreParams);

        $dashboardInfos = [];
        if (is_array($dashboardDatas) && !empty($dashboardDatas)) {
            foreach ($dashboardDatas as $key => $dashboardData) {
                if (key_exists('widgets', $dashboardData)) {
                    $dashboardInfos['widgets'][$key] = $dashboardData['widgets'];
                }
                if (key_exists('lists', $dashboardData)) {
                    $dashboardInfos['lists'][$key] = $dashboardData['lists'];
                }
                if (key_exists('graphs', $dashboardData)) {
                    $dashboardInfos['graphs'][$key] = $dashboardData['graphs'];
                }
                if (key_exists('disabledGraphs', $dashboardData)) {
                    $dashboardInfos['disabledGraphs'] = array_merge($dashboardInfos['disabledGraphs'] ?? [], $dashboardData['disabledGraphs']);
                }
                if (key_exists('graphsFilters', $dashboardData)) {
                    $dashboardInfos['graphsFilters'] = array_merge($dashboardInfos['graphsFilters'] ?? [], $dashboardData['graphsFilters']);
                }
            }
        }

        return $dashboardInfos;
    }

    /**
     * Show dashboard
     *
     * @param array|null  $moreParams    Parameters for load dashboard info
     *
     * @return void
     */
    public function show_dashboard(?array $moreParams = []): void
    {
        global $conf, $form, $langs, $moduleNameLowerCase, $user;

        $width  = DolGraph::getDefaultGraphSizeForStats('width');
        $height = DolGraph::getDefaultGraphSizeForStats('height');

        $conf->global->MAIN_DISABLE_TRUNC = 1;

        $dashboards = $this->load_dashboard($moreParams);

        print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '" class="dashboard" id="dashBoardForm">';
        print '<input type="hidden" name="token" value="' . newToken() . '">';
        print '<input type="hidden" name="action" value="view">';

        $confName            = dol_strtoupper($moduleNameLowerCase) . '_DASHBOARD_CONFIG';
        $disableWidgetList   = json_decode($user->conf->$confName ?? '');
        $disableWidgetList   = $disableWidgetList->widgets ?? new stdClass();
        $dashboardWidgetsArray = [];
        if (isset($dashboards['widgets']) && is_array($dashboards['widgets']) && !empty($dashboards['widgets'])) {
            foreach ($dashboards['widgets'] as $dashboardWidgets) {
                foreach ($dashboardWidgets as $key => $dashboardWidget) {
                    if (isset($disableWidgetList->$key) && $disableWidgetList->$key == 0) {
                        $dashboardWidgetsArray[$key] = $dashboardWidget['widgetName'];
                    }
                }
            }
        }

        print '<div class="add-widget-box" id="add-widget-box" style="' . (!empty((array)$disableWidgetList) ? '' : 'display:none') . '">';
        print Form::selectarray('disabledWidget', $dashboardWidgetsArray, -1, $langs->trans('ChooseBoxToAddWidget'), 0, 0, 'data-item-type="widget" data-id-refresh="widget-dashboard" data-id-load="add-widget-box"', 1, 0, 0, 'DESC', 'maxwidth300 widthcentpercentminusx hideonprint add-dashboard-widget', 0, 'hidden selected', 0, 1);
        if (!empty($conf->use_javascript_ajax)) {
            include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
            print ajax_combobox('disabledWidget');
        }
        print '</div>';

        if (isset($dashboards['widgets']) && is_array($dashboards['widgets']) && !empty($dashboards['widgets'])) {
            $widget = '';
            foreach ($dashboards['widgets'] as $dashboardWidgets) {
                foreach ($dashboardWidgets as $key => $dashboardWidget) {
                    if (!isset($disableWidgetList->$key) && is_array($dashboardWidget) && !empty($dashboardWidget)) {

                        $widget .= '<div class="wpeo-infobox" id="widget-' . $key . '">';

                            $widget .= '<div class="wpeo-infobox__header">';
                                $widget .= '<div class="header__icon-container">';
                                    $widget .= '<span class="header__icon-background" style="background: ' . ($dashboardWidget['pictoColor'] ?? '#0D8AFF') . ';"></span>';
                                    $widget .= '<i class="header__icon ' . $dashboardWidget['picto'] . '" style="color: ' . ($dashboardWidget['pictoColor'] ?? '#0D8AFF') . ';"></i>';
                                $widget .= '</div>';
                                $widget .= '<div class="header__title">' . ($dashboardWidget['title'] ?? $langs->transnoentities('Title')) . '</div>';
                                $widget .= '<i class="close-dashboard-widget header__close fas fa-times" data-item-type="widget" data-item-name="' . $key . '" data-item-suppress="widget-' . $key . '" data-item-refresh="add-widget-box" id="dashboard-close-item"></i>';
                            $widget .= '</div>';

                        $widget .= '<div class="wpeo-infobox__body">';
                            $widget .= '<ul class="body__row-container">';
                            for ($i = 0; $i < count($dashboardWidget['label']); $i++) {
                                if (!empty($dashboardWidget['label'][$i])) {
                                    $widget .= '<li class="body__row">';
                                        $widget .= '<span class="row__libelle">';
                                            $widget .= $dashboardWidget['label'][$i];
                                            $widget .= (!empty($dashboardWidget['tooltip'][$i]) ? $form->textwithpicto('', $langs->transnoentities($dashboardWidget['tooltip'][$i])) : '');
                                        $widget .= '</span>';
                                        $widget .= '<span class="row__data-container">';
                                            $widget .= '<span class="row__data">'; // @TODO Boucle ici pour avoir plusieurs badges
                                                if (isset($dashboardWidget['content'][$i]) && $dashboardWidget['content'][$i] !== '') {
                                                    $widget .= '<span class="row__data-libelle">' . $dashboardWidget['content'][$i] . '</span>';

                                                    if (isset($dashboardWidget['moreContent'][$i]) && $dashboardWidget['moreContent'][$i] !== '') {
                                                        $widget .= $dashboardWidget['moreContent'][$i];
                                                    }
                                                } else {
                                                    $widget .= $dashboardWidget['customContent'][$i];
                                                }
                                            $widget .= '</span>';
                                        $widget .= '</span>';
                                    $widget .= '</li>';
                                }
                            }
                            $widget .= '</ul>';
                            if (isset($dashboardWidget['moreParams']) && is_array($dashboardWidget['moreParams']) && (!empty($dashboardWidget['moreParams']))) {
                                $widget .= '<div class="body__content">';
                                foreach ($dashboardWidget['moreParams'] as $dashboardWidgetMoreParamsKey => $dashboardWidgetMoreParams) {
                                    switch ($dashboardWidgetMoreParamsKey) {
                                        case 'links' :
                                            if (is_array($dashboardWidget['moreParams']['links']) && (!empty($dashboardWidget['moreParams']['links']))) {
                                                foreach ($dashboardWidget['moreParams']['links'] as $dashboardWidgetMoreParamsLink) {
                                                    $widget .= '<a class="' . $dashboardWidgetMoreParamsLink['moreCSS'] . '" href="' . dol_buildpath($dashboardWidgetMoreParamsLink['url'], 1) . '" target="_blank">' . img_picto($langs->trans('Url'), 'globe', 'class="paddingrightonly"') . $langs->transnoentities($dashboardWidgetMoreParamsLink['linkName']) . '</a><br>';
                                                }
                                            }
                                            break;
                                        default :
                                            $widget .= $dashboardWidgetMoreParams;
                                            break;
                                    }
                                }
                                $widget .= '</div>';
                            }
                        $widget .= '</div>';

                        $widget .= '</div>';
                    }
                }
            }
            print '<div class="opened-dash-board-wrap"><div class="wpeo-infobox-container box-flex-container" id="widget-dashboard">' . $widget . '</div></div>';
        }

        if (!empty($dashboards['graphsFilters']) && is_array($dashboards['graphsFilters'])) {
            print '<div class="graph-filter-container">';

            print '<button class="wpeo-button button-grey" type="button" id="dashboard-graph-filter" data-ref-id="graph-filters" >';
            print img_picto($langs->transnoentities('Filter'), 'fontawesome_filter_fas_grey_1em');
            print '<span class="marginleftonly">' . $langs->transnoentities('Filters') . '</span>';
            print '</button>';

            print '<div class="flex-row" id="graph-filters" style="display: none; margin-top: 1em;">';

            print '<div class="flex flex-col">';
            foreach ($dashboards['graphsFilters'] as $key => $dashboardGraphFilter) {
                switch ($dashboardGraphFilter['type']) {
                    case 'selectarray':
                        print '<div class="flex flex-row">';
                        print '<span class="marginrightonly">' . $dashboardGraphFilter['title'] . '</span>';
                        print Form::selectarray($dashboardGraphFilter['filter'], $dashboardGraphFilter['values'], $dashboardGraphFilter['currentValue'], $dashboardGraphFilter['title'], 0, 0, '', 1, 0, 0, 'DESC', 'maxwidth300 widthcentpercentminusx hideonprint', 0, 'hidden selected', 0, 1);
                        print '</div>';
                        break;
                    default :
                        break;
                }
            }
            print '</div>';

            print '<button class="marginleftonly button_search self-end" type="button" id="dashboard-graph-filter-submit" data-ref-id="graph-filters" data-item-refresh="graph-dashboard">';
            print img_picto($langs->transnoentities('Reload'), 'fontawesome_redo_fas_grey_1em');
            print '</button>';
            print '</div>';
            print '</div>';
        }

        print '<div class="add-graph-box" id="add-graph-box" style="margin-top: 10px; ' . (!empty($dashboards['disabledGraphs']) ? '' : 'display:none') . '">';
        // Ensure the 'disabledGraphs' index exists in $dashboards array to avoid undefined index warnings
        $disabledGraphs = isset($dashboards['disabledGraphs']) && is_array($dashboards['disabledGraphs']) ? $dashboards['disabledGraphs'] : array();

        // Print the select box for graphs with proper attributes and default values
        print Form::selectarray(
            'disabledGraph',                                  // Field name
            $disabledGraphs,                                  // Options array
            -1,                                               // Default selected value
            $langs->trans('ChooseBoxToAddGraph'),             // Translated prompt message
            0,                                                // Optional parameter (unused, default: 0)
            0,                                                // Optional parameter (unused, default: 0)
            'data-item-type="graph" data-id-refresh="graph-dashboard" data-id-load="add-graph-box"', // HTML attributes
            1,                                                // Optional parameter (default: 1)
            0,                                                // Optional parameter (default: 0)
            0,                                                // Optional parameter (default: 0)
            'DESC',                                           // Sorting order
            'maxwidth300 widthcentpercentminusx hideonprint',  // CSS classes for styling
            0,                                                // Optional parameter (default: 0)
            'hidden selected',                                // Additional attributes or CSS classes
            0,                                                // Optional parameter (default: 0)
            1                                                 // Optional parameter (default: 1)
        );

        if (!empty($conf->use_javascript_ajax)) {
            include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
            print ajax_combobox('disabledGraph');
        }
        print '</div>';

        print '<div class="graph-dashboard wpeo-grid grid-2" id="graph-dashboard">';

        if (isset($dashboards['graphs']) && is_array($dashboards['graphs']) && !empty($dashboards['graphs'])) {
            foreach ($dashboards['graphs'] as $dashboardGraphs) {
                if (is_array($dashboardGraphs) && !empty($dashboardGraphs)) {
                    foreach ($dashboardGraphs as $keyElement => $dashboardGraph) {
                        $nbDataset = 0;
                        $uniqueKey = strip_tags($dashboardGraph['title']) . $keyElement;
                        if (isset($dashboardGraph['data']) && is_array($dashboardGraph['data']) && !empty($dashboardGraph['data'])) {
                            if ($dashboardGraph['dataset'] >= 2) {
                                foreach ($dashboardGraph['data'] as $dashboardGraphDatasets) {
                                    unset($dashboardGraphDatasets[0]);
                                    foreach ($dashboardGraphDatasets as $dashboardGraphDataset) {
                                        if (!empty($dashboardGraphDataset)) {
                                            $nbDataset = 1;
                                        }
                                    }
                                }
                            } else {
                                foreach ($dashboardGraph['data'] as $dashboardGraphDatasets) {
                                    $nbDataset += $dashboardGraphDatasets;
                                }
                            }
                            if ($nbDataset > 0) {
                                if (is_array($dashboardGraph['labels']) && !empty($dashboardGraph['labels'])) {
                                    foreach ($dashboardGraph['labels'] as $key => $dashboardGraphLabel) {
                                        $dashboardGraphLegend[$uniqueKey][] = $dashboardGraphLabel['label'];
                                        if (isset($dashboardGraphLabel['color'])) {
                                            if (dol_strlen($dashboardGraphLabel['color']) > 0) {
                                                $dashboardGraphColor[$uniqueKey][] = $dashboardGraphLabel['color'];
                                            } else {
                                                // If only one color is defined in category, the others will be black
                                                // If no color is defined, all the colors will be defined by global $theme_datacolor
                                                // To avoid black color we better define a color instead of empty
                                                $dashboardGraphColor[$uniqueKey][] = $this->getColorRange($key);
                                            }
                                        }
                                    }
                                }

                                $arrayKeys = array_keys($dashboardGraph['data']);
                                foreach ($arrayKeys as $key) {
                                    if ($dashboardGraph['dataset'] >= 2) {
                                        $graphData[$uniqueKey][] = $dashboardGraph['data'][$key];
                                    } else {
                                        $graphData[$uniqueKey][] = [
                                            0 => $dashboardGraph['labels'][$key]['label'],
                                            1 => $dashboardGraph['data'][$key]
                                        ];
                                    }
                                }

                                $fileName[$uniqueKey] = $uniqueKey . '.png';
                                $fileUrl[$uniqueKey]  = DOL_URL_ROOT . '/viewimage.php?modulepart=' . $moduleNameLowerCase . '&file=' . $uniqueKey . '.png';

                                $graph = new DolGraph();
                                $graph->SetData($graphData[$uniqueKey]);

                                if ($dashboardGraph['dataset'] >= 2) {
                                    $graph->SetLegend($dashboardGraphLegend[$uniqueKey]);
                                }
                                if (isset($dashboardGraphColor[$uniqueKey])) {
                                    $graph->SetDataColor($dashboardGraphColor[$uniqueKey]);
                                }
                                $graph->SetType([$dashboardGraph['type'] ?? 'pie']);
                                $graph->SetWidth($dashboardGraph['width'] ?? $width);
                                $graph->SetHeight($dashboardGraph['height'] ?? $height);
                                $graph->setShowLegend($dashboardGraph['showlegend'] ?? 2);
                                $graph->draw($fileName[$uniqueKey], $fileUrl[$uniqueKey]);
                                // Use null coalescing operator to ensure indices exist and avoid undefined index warnings in PHP 8 and Dolibarr V20.
                                print '<div class="' . ($dashboardGraph['moreCSS'] ?? '') . '" id="graph-' . ($dashboardGraph['name'] ?? '') . '">';


                                $downloadCSV  = '<div class="flex flex-row justify-end">';
                                $downloadCSV .= '<input type="hidden" name="graph" value="' . dol_escape_htmltag(json_encode($dashboardGraph, JSON_UNESCAPED_UNICODE)) . '">';
                                $downloadCSV .= '<button class="wpeo-button no-load button-grey" id="export-csv" data-graph-name="' . dol_sanitizeFileName(dol_strtolower($dashboardGraph['title'])) . '">';
                                $downloadCSV .= img_picto($langs->transnoentities('ExportGraphCSV'), 'fontawesome_file-csv_fas_#31AD29_1em');
                                $downloadCSV .= '</button>';
                                if (!empty($dashboardGraph['name'])) {
                                    $downloadCSV .= '<button class="wpeo-button button-transparent" type="button" data-item-type="graph" data-item-name="' . $dashboardGraph['name'] . '" data-item-suppress="graph-' . $dashboardGraph['name'] . '" data-item-refresh="add-graph-box" id="dashboard-close-item">';
                                    $downloadCSV .= img_picto('Close', 'fontawesome_times_fas_light-grey_1em', '', '', '', '', '', 'close-dashboard-widget');
                                    $downloadCSV .= '</button>';
                                }
                                $downloadCSV .= '</div>';
                                // Ensure $dashboardGraph is an array to avoid undefined variable warnings
                                if (!isset($dashboardGraph) || !is_array($dashboardGraph)) {
                                    $dashboardGraph = [];
                                }

                                // Initialize 'morehtmlright' key if not already set
                                if (!isset($dashboardGraph['morehtmlright'])) {
                                    $dashboardGraph['morehtmlright'] = '';
                                }

                                // Append $downloadCSV content if defined, otherwise append an empty string
                                $dashboardGraph['morehtmlright'] .= isset($downloadCSV) ? $downloadCSV : '';

                                print load_fiche_titre($dashboardGraph['title'], $dashboardGraph['morehtmlright'], $dashboardGraph['picto']);
                                print $graph->show();
                                print '</div>';
                            }
                        }
                    }
                }
            }
        }

        if (isset($dashboards['lists']) && is_array($dashboards['lists']) && !empty($dashboards['lists'])) {
            foreach ($dashboards['lists'] as $dashboardLists) {
                foreach ($dashboardLists as $dashboardList) {
                    if (is_array($dashboardList['data']) && !empty($dashboardList['data'])) {
                        print '<div id="graph-' . $dashboardList['name'] . '" style="width: 100%">';

                        if (!empty($dashboardList['name'])) {
                            $dashboardList['morehtmlright'] = '<button class="wpeo-button button-transparent" type="button" data-item-type="graph" data-item-name="' . $dashboardList['name'] . '" data-item-suppress="graph-' . $dashboardList['name'] . '" data-item-refresh="add-graph-box" id="dashboard-close-item">';
                            $dashboardList['morehtmlright'] .= img_picto('Close', 'fontawesome_times_fas_light-grey_1em', '', '', '', '', '', 'close-dashboard-widget');
                            $dashboardList['morehtmlright'] .= '</button>';
                        }

                        print load_fiche_titre($dashboardList['title'], $dashboardList['morehtmlright'], $dashboardList['picto']);
                        print '<table class="noborder centpercent">';
                        print '<tr class="liste_titre">';
                        foreach ($dashboardList['labels'] as $key => $dashboardListLabel) {
                            print '<td class="' . ($conf->browser->layout == 'classic' ? 'nowraponall tdoverflowmax200 ' : '') . (($key != 'Ref') ? 'center' : '') . '">' . $langs->transnoentities($dashboardListLabel) . '</td>';
                        }
                        print '</tr>';
                        foreach ($dashboardList['data'] as $dashboardListDatasets) {
                            print '<tr class="oddeven">';
                            foreach ($dashboardListDatasets as $key => $dashboardGraphDataset) {
                                // Retrieve optional attributes with fallback to empty string if not defined
                                $morecss = $dashboardGraphDataset['morecss'] ?? '';
                                $moreAttr = $dashboardGraphDataset['moreAttr'] ?? '';
                                $value   = $dashboardGraphDataset['value'] ?? '';
                                
                                // Use layout setting if available; fallback to empty string if not defined
                                $layout = $conf->browser->layout ?? '';
                                
                                // Build the class string conditionally
                                $class = ($layout === 'classic' ? 'nowraponall tdoverflowmax200 ' : '')
                                       . (($key != 'Ref') ? 'center ' : '')
                                       . $morecss;
                                
                                // Print the table cell with all attributes
                                print '<td class="' . $class . '" ' . $moreAttr . '>' . $value . '</td>';
                            }
                            
                            print '</tr>';
                        }
                        print '</table></div>';
                    }
                }
            }
        }
        print '</div>';

        print '</form>';
    }

    /**
     * get color range for key
     *
     * @param  int    $key Key to find in color array
     * @return string
     */
    public static function getColorRange(int $key): string
    {
        $colorArray = ['#f44336', '#e81e63', '#9c27b0', '#673ab7', '#3f51b5', '#2196f3', '#03a9f4', '#00bcd4', '#009688', '#4caf50', '#8bc34a', '#cddc39', '#ffeb3b', '#ffc107', '#ff9800', '#ff5722', '#795548', '#9e9e9e', '#607d8b'];
        return $colorArray[$key % count($colorArray)];
    }
}
