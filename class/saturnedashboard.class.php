<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
 * \brief   Class file for manage SaturneDashboard.
 */

// Load Dolibarr libraries.
require_once DOL_DOCUMENT_ROOT . '/core/class/dolgraph.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

/**
 * Class for SaturneDashboard.
 */
class SaturneDashboard
{
    /**
     * @var DoliDB Database handler.
     */
    public DoliDB $db;

    /**
     * @var string Module name.
     */
    public $module = 'saturne';

    /**
     * Constructor.
     *
     * @param DoliDB $db                  Database handler.
     * @param string $moduleNameLowerCase Module name.
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
            }
        }

        return $dashboardInfos;
    }

    /**
     * Show dashboard
     *
     * @param array|null      $moreParams    Parameters for load dashboard info
     *
     * @return void
     * @throws Exception
     */
    public function show_dashboard(?array $moreParams = [])
    {
        global $conf, $form, $langs, $moduleNameLowerCase, $user;

        $width  = DolGraph::getDefaultGraphSizeForStats('width');
        $height = DolGraph::getDefaultGraphSizeForStats('height');

        $conf->global->MAIN_DISABLE_TRUNC = 1;

        $dashboards = $this->load_dashboard($moreParams);

        print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '" class="dashboard" id="dashBoardForm">';
        print '<input type="hidden" name="token" value="' . newToken() . '">';
        print '<input type="hidden" name="action" value="view">';

        $confName            = strtoupper($moduleNameLowerCase) . '_DISABLED_DASHBOARD_INFO';
        $disableWidgetList   = json_decode($user->conf->$confName);
        $dashboardWidgetsArray = [];
        if (is_array($dashboards['widgets']) && !empty($dashboards['widgets'])) {
            foreach ($dashboards['widgets'] as $dashboardWidgets) {
                foreach ($dashboardWidgets as $key => $dashboardWidget) {
                    if (isset($disableWidgetList->$key) && $disableWidgetList->$key == 0) {
                        $dashboardWidgetsArray[$key] = $dashboardWidget['widgetName'];
                    }
                }
            }
        }

        print '<div class="add-widget-box" style="' . (!empty((array)$disableWidgetList) ? '' : 'display:none') . '">';
        print Form::selectarray('boxcombo', $dashboardWidgetsArray, -1, $langs->trans('ChooseBoxToAdd'), 0, 0, '', 1, 0, 0, 'DESC', 'maxwidth300 widthcentpercentminusx hideonprint add-dashboard-widget', 0, 'hidden selected', 0, 1);
        if (!empty($conf->use_javascript_ajax)) {
            include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
            print ajax_combobox('boxcombo');
        }
        print '</div>';

        if (is_array($dashboards['widgets']) && !empty($dashboards['widgets'])) {
            $widget = '';
            foreach ($dashboards['widgets'] as $dashboardWidgets) {
                foreach ($dashboardWidgets as $key => $dashboardWidget) {
                    if (!isset($disableWidgetList->$key) && is_array($dashboardWidget) && !empty($dashboardWidget)) {
                        $widget .= '<div class="box-flex-item"><div class="box-flex-item-with-margin">';
                        $widget .= '<div class="info-box">';
                        $widget .= '<span class="info-box-icon">';
                        $widget .= '<i class="' . $dashboardWidget['picto'] . '"></i>';
                        $widget .= '</span>';
                        $widget .= '<div class="info-box-content">';
                        $widget .= '<div class="info-box-title" title="' . $langs->trans('Close') . '">';
                        $widget .= '<span class="close-dashboard-widget" data-widgetname="' . $key . '"><i class="fas fa-times"></i></span>';
                        $widget .= '</div>';
                        $widget .= '<div class="info-box-lines">';
                        $widget .= '<div class="info-box-line" style="font-size : 20px;">';
                        for ($i = 0; $i < count($dashboardWidget['label']); $i++) {
                            if (!empty($dashboardWidget['label'][$i])) {
                                $widget .= '<span class=""><strong>' . $dashboardWidget['label'][$i] . ' : ' . '</strong>';
                                if (!empty($dashboardWidget['content'][$i])) {
                                    $widget .= '<span class="classfortooltip badge badge-info">' . $dashboardWidget['content'][$i] . '</span>';
                                    $widget .= (!empty($dashboardWidget['tooltip'][$i]) ? $form->textwithpicto('', $langs->transnoentities($dashboardWidget['tooltip'][$i])) : '') . '</span>';
                                } else {
                                    $widget .= $dashboardWidget['customContent'][$i];
                                }
                                $widget .= '<br>';
                            }
                        }
                        if (is_array($dashboardWidget['moreParams']) && (!empty($dashboardWidget['moreParams']))) {
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
                        }
                        $widget .= '</div>';
                        $widget .= '</div><!-- /.info-box-lines --></div><!-- /.info-box-content -->';
                        $widget .= '</div><!-- /.info-box -->';
                        $widget .= '</div><!-- /.box-flex-item-with-margin -->';
                        $widget .= '</div>';
                    }
                }
            }
            print '<div class="opened-dash-board-wrap"><div class="box-flex-container">' . $widget . '</div></div>';
        }

        print '<div class="graph-dashboard wpeo-grid grid-2">';

        if (is_array($dashboards['graphs']) && !empty($dashboards['graphs'])) {
            foreach ($dashboards['graphs'] as $dashboardGraphs) {
                if (is_array($dashboardGraphs) && !empty($dashboardGraphs)) {
                    foreach ($dashboardGraphs as $keyElement => $dashboardGraph) {
                        $nbDataset = 0;
                        $uniqueKey = strip_tags($dashboardGraph['title']) . $keyElement;
                        if (is_array($dashboardGraph['data']) && !empty($dashboardGraph['data'])) {
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
                                    foreach ($dashboardGraph['labels'] as $dashboardGraphLabel) {
                                        $dashboardGraphLegend[$uniqueKey][] = $dashboardGraphLabel['label'];
                                        $dashboardGraphColor[$uniqueKey][]  = $dashboardGraphLabel['color'];
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
                                $graph->SetDataColor($dashboardGraphColor[$uniqueKey]);
                                $graph->SetType([$dashboardGraph['type'] ?? 'pie']);
                                $graph->SetWidth($dashboardGraph['width'] ?? $width);
                                $graph->SetHeight($dashboardGraph['height'] ?? $height);
                                $graph->setShowLegend($dashboardGraph['showlegend'] ?? 2);
                                $graph->draw($fileName[$uniqueKey], $fileUrl[$uniqueKey]);
                                print '<div class="' . $dashboardGraph['moreCSS'] . '">';

                                $downloadCSV  = '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
                                $downloadCSV .= '<input type="hidden" name="token" value="' . newToken() . '">';
                                $downloadCSV .= '<input type="hidden" name="action" value="generate_csv">';
                                $downloadCSV .= '<input type="hidden" name="graph" value="' . http_build_query($dashboardGraph) . '">';
                                $downloadCSV .= '<button class="wpeo-button no-load button-grey">';
                                $downloadCSV .= img_picto('ExportCSV', 'fontawesome_file-csv_fas_#31AD29_15px');
                                $downloadCSV .= '</button></form>';
                                $dashboardGraph['morehtmlright'] .= $downloadCSV;

                                print load_fiche_titre($dashboardGraph['title'], $dashboardGraph['morehtmlright'], $dashboardGraph['picto']);
                                print $graph->show();
                                print '</div>';
                            }
                        }
                    }
                }
            }
        }

        print '</div>';

        if (is_array($dashboards['lists']) && !empty($dashboards['lists'])) {
            foreach ($dashboards['lists'] as $dashboardLists) {
                foreach ($dashboardLists as $dashboardList) {
                    if (is_array($dashboardList['data']) && !empty($dashboardList['data'])) {
                        print '<div>';
                        print load_fiche_titre($dashboardList['title'], $dashboardList['morehtmlright'], $dashboardList['picto']);
                        print '<table class="noborder centpercent">';
                        print '<tr class="liste_titre">';
                        foreach ($dashboardList['labels'] as $key => $dashboardListLabel) {
                            print '<td class="nowraponall tdoverflowmax200 ' . (($key != 'Ref') ? 'center' : '') . '">' . $langs->transnoentities($dashboardListLabel) . '</td>';
                        }
                        print '</tr>';
                        foreach ($dashboardList['data'] as $dashboardListDatasets) {
                            print '<tr class="oddeven">';
                            foreach ($dashboardListDatasets as $key => $dashboardGraphDataset) {
                                print '<td class="nowraponall tdoverflowmax200 ' . (($key != 'Ref') ? 'center ' : '') . $dashboardGraphDataset['morecss'] . '"' . $dashboardGraphDataset['moreAttr'] . '>' . $dashboardGraphDataset['value'] . '</td>';
                            }
                            print '</tr>';
                        }
                        print '</table></div>';
                    }
                }
            }
        }

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
