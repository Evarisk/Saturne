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
     * Load dashboard info.
     *
     * @return array
     * @throws Exception
     */
    public function load_dashboard($moreParams = []): array
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
     * Show dashboard.
     *
     * @return void
     * @throws Exception
     */
    public function show_dashboard($moreParams = [])
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
        print Form::selectarray('boxcombo', $dashboardWidgetsArray, -1, $langs->trans('ChooseBoxToAdd') . '...', 0, 0, '', 1, 0, 0, 'DESC', 'maxwidth150onsmartphone hideonprint add-dashboard-widget', 0, 'hidden selected', 0, 1);
        if (!empty($conf->use_javascript_ajax)) {
            include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
            print ajax_combobox('boxcombo');
        }
        print '</div>';
        print '<div class="fichecenter">';

        if (is_array($dashboards['widgets']) && !empty($dashboards['widgets'])) {
            $widget = '';
            foreach ($dashboards['widgets'] as $dashboardWidgets) {

                foreach ($dashboardWidgets as $key => $dashboardWidget) {
                    if (!isset($disableWidgetList->$key) && is_array($dashboardWidget) && !empty($dashboardWidget)) {
                        if (array_key_exists('label', $dashboardWidget)) {
                            $dashboardWidget = [$dashboardWidget];
                        }

                        foreach ($dashboardWidget as $widgetSingle) {
                            $widget .= '<div class="box-flex-item"><div class="box-flex-item-with-margin">';
                            $widget .= '<div class="info-box">';
                            $widget .= '<span class="info-box-icon">';
                            $widget .= '<i class="' . $widgetSingle['picto'] . '"></i>';
                            $widget .= '</span>';
                            $widget .= '<div class="info-box-content">';
                            $widget .= '<div class="info-box-title" title="' . $langs->trans('Close') . '">';
                            $widget .= '<span class="close-dashboard-widget" data-widgetname="' . $key . '"><i class="fas fa-times"></i></span>';
                            $widget .= '</div>';
                            $widget .= '<div class="info-box-lines">';
                            $widget .= '<div class="info-box-line" style="font-size : 20px;">';
                            for ($i = 0; $i < count($widgetSingle['label']); $i++) {
                                if (!empty($widgetSingle['label'][$i])) {
                                    $widget .= '<span class=""><strong>' . $widgetSingle['label'][$i] . ' : ' . '</strong>';
                                    $widget .= '<span class="classfortooltip badge badge-info" title="' . $widgetSingle['label'][$i] . ' : ' . $widgetSingle['content'][$i] . '" >' . $widgetSingle['content'][$i] . '</span>';
                                    $widget .= (!empty($widgetSingle['tooltip'][$i]) ? $form->textwithpicto('', $langs->transnoentities($widgetSingle['tooltip'][$i])) : '') . '</span>';
                                    $widget .= '<br>';
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
            }
            print '<div class="opened-dash-board-wrap"><div class="box-flex-container">' . $widget . '</div></div>';
        }

        print '<div class="graph-dashboard wpeo-grid grid-2">';

        if (is_array($dashboards['graphs']) && !empty($dashboards['graphs'])) {
            foreach ($dashboards['graphs'] as $objectType => $dashboardGraphs) {

                if (is_array($dashboardGraphs) && !empty($dashboardGraphs)) {
                    foreach ($dashboardGraphs as $dashboardGraph) {
                        $uniqueDashboardKey = uniqid();

                        $nbDataset = 0;
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
                                        $dashboardGraphLegend[$uniqueDashboardKey][] = $langs->trans($dashboardGraphLabel['label']);
                                        $dashboardGraphColor[$uniqueDashboardKey][]  = $langs->trans($dashboardGraphLabel['color']);
                                    }
                                }

                                $arrayKeys = array_keys($dashboardGraph['data']);
                                foreach ($arrayKeys as $key) {
                                    if ($dashboardGraph['dataset'] >= 2) {
                                        $graphData[$uniqueDashboardKey][] = $dashboardGraph['data'][$key];
                                    } else {
                                        $graphData[$uniqueDashboardKey][] = [
                                            0 => $langs->trans($dashboardGraph['labels'][$key]['label']),
                                            1 => $dashboardGraph['data'][$key]
                                        ];
                                    }
                                }

                                $fileName[$uniqueDashboardKey] = $uniqueDashboardKey . '.png';
                                $fileUrl[$uniqueDashboardKey]  = DOL_URL_ROOT . '/viewimage.php?modulepart=' . $moduleNameLowerCase . '&file=' . $uniqueDashboardKey . '.png';

                                $graph = new DolGraph();
                                $graph->SetData($graphData[$uniqueDashboardKey]);

                                if ($dashboardGraph['dataset'] >= 2) {
                                    $graph->SetLegend($dashboardGraphLegend[$uniqueDashboardKey]);
                                }


                                $graph->SetDataColor($dashboardGraphColor[$uniqueDashboardKey]);
                                $graph->SetType([$dashboardGraph['type'] ?? 'pie']);
                                $graph->SetWidth($dashboardGraph['width'] ?? $width);
                                $graph->SetHeight($dashboardGraph['height'] ?? $height);
                                $graph->setShowLegend($dashboardGraph['showlegend'] ?? 2);
                                $graph->draw($fileName[$uniqueDashboardKey], $fileUrl[$uniqueDashboardKey]);
                                print '<div>';
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
                            print '<td class="nowraponall tdoverflowmax200' . (($key != 'Ref') ? ' center' : '') . '">' . $langs->transnoentities($dashboardListLabel) . '</td>';
                        }
                        print '</tr>';
                        foreach ($dashboardList['data'] as $dashboardListDatasets) {
                            print '<tr class="oddeven">';
                            foreach ($dashboardListDatasets as $key => $dashboardGraphDataset) {
                                print '<td class="nowraponall tdoverflowmax200' . (($key != 'Ref') ? ' center ' : '') . $dashboardGraphDataset['morecss'] . '">' . $dashboardGraphDataset['value'] . '</td>';
                            }
                            print '</tr>';
                        }
                        print '</table></div>';
                    }
                }
            }
        }

        print '</div></div>';
        print '</form>';
    }
}
