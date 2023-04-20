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
    public string $module = 'saturne';

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
    public function load_dashboard(): array
    {
        require_once __DIR__ . '/../../' . $this->module . '/class/' . $this->module . 'dashboard.class.php';

        $className      = ucfirst($this->module) . 'Dashboard';
        $dashboard      = new $className($this->db);
        $dashboardDatas = $dashboard->load_dashboard();
        $dashboardInfos = [];
        if (is_array($dashboardDatas) && !empty($dashboardDatas)) {
            foreach ($dashboardDatas as $key => $dashboardData) {
                if (key_exists('widgets', $dashboardData)) {
                    $dashboardInfos['widgets'][$key] = $dashboardData['widgets'];
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
    public function show_dashboard()
    {
        global $conf, $form, $langs, $moduleNameLowerCase, $user;

        $width  = DolGraph::getDefaultGraphSizeForStats('width');
        $height = DolGraph::getDefaultGraphSizeForStats('height');

        $dashboardDatas = $this->load_dashboard();

        print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '" class="dashboard" id="dashBoardForm">';
        print '<input type="hidden" name="token" value="' . newToken() . '">';
        print '<input type="hidden" name="action" value="view">';

        $confName            = strtoupper($moduleNameLowerCase) . '_DISABLED_DASHBOARD_INFO';
        $disableWidgetList   = json_decode($user->conf->$confName);
        $dashboardLinesArray = [];
        if (is_array($dashboardDatas['widgets']) && !empty($dashboardDatas['widgets'])) {
            foreach ($dashboardDatas['widgets'] as $dashboardLines) {
                foreach ($dashboardLines as $key => $dashboardLine) {
                    if (isset($disableWidgetList->$key) && $disableWidgetList->$key == 0) {
                        $dashboardLinesArray[$key] = $dashboardLine['widgetName'];
                    }
                }
            }
        }

        print '<div class="add-widget-box" style="' . (!empty((array)$disableWidgetList) ? '' : 'display:none') . '">';
        print Form::selectarray('boxcombo', $dashboardLinesArray, -1, $langs->trans('ChooseBoxToAdd') . '...', 0, 0, '', 1, 0, 0, 'DESC', 'maxwidth150onsmartphone hideonprint add-dashboard-widget', 0, 'hidden selected', 0, 1);
        if (!empty($conf->use_javascript_ajax)) {
            include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
            print ajax_combobox('boxcombo');
        }
        print '</div>';
        print '<div class="fichecenter">';

        if (is_array($dashboardDatas['widgets']) && !empty($dashboardDatas['widgets'])) {
            $openedDashBoard = '';
            foreach ($dashboardDatas['widgets'] as $dashboardLines) {
                foreach ($dashboardLines as $key => $dashboardLine) {
                    if (!isset($disableWidgetList->$key) && is_array($dashboardLine) && !empty($dashboardLine)) {
                        $openedDashBoard .= '<div class="box-flex-item"><div class="box-flex-item-with-margin">';
                        $openedDashBoard .= '<div class="info-box info-box-sm">';
                        $openedDashBoard .= '<span class="info-box-icon">';
                        $openedDashBoard .= '<i class="' . $dashboardLine['picto'] . '"></i>';
                        $openedDashBoard .= '</span>';
                        $openedDashBoard .= '<div class="info-box-content">';
                        $openedDashBoard .= '<div class="info-box-title" title="' . $langs->trans('Close') . '">';
                        $openedDashBoard .= '<span class="close-dashboard-widget" data-widgetname="' . $key . '"><i class="fas fa-times"></i></span>';
                        $openedDashBoard .= '</div>';
                        $openedDashBoard .= '<div class="info-box-lines">';
                        $openedDashBoard .= '<div class="info-box-line" style="font-size : 20px;">';
                        for ($i = 0; $i < count($dashboardLine['label']); $i++) {
                            $openedDashBoard .= '<span class=""><strong>' . $dashboardLine['label'][$i] . ' : ' . '</strong>';
                            $openedDashBoard .= '<span class="classfortooltip badge badge-info" title="' . $dashboardLine['label'][$i] . ' : ' . $dashboardLine['content'][$i] . '" >' . $dashboardLine['content'][$i] . '</span>';
                            $openedDashBoard .= (!empty($dashboardLine['tooltip'][$i]) ? $form->textwithpicto('', $langs->transnoentities($dashboardLine['tooltip'][$i])) : '') . '</span>';
                            $openedDashBoard .= '<br>';
                        }
                        $openedDashBoard .= '</div>';
                        $openedDashBoard .= '</div><!-- /.info-box-lines --></div><!-- /.info-box-content -->';
                        $openedDashBoard .= '</div><!-- /.info-box -->';
                        $openedDashBoard .= '</div><!-- /.box-flex-item-with-margin -->';
                        $openedDashBoard .= '</div>';
                    }
                }
            }
            print '<div class="opened-dash-board-wrap"><div class="box-flex-container">' . $openedDashBoard . '</div></div>';
        }

        print '<div class="graph-dashboard wpeo-gridlayout grid-2">';

        if (is_array($dashboardDatas['graphs']) && !empty($dashboardDatas['graphs'])) {
            foreach ($dashboardDatas['graphs'] as $dashboardData) {
                if (is_array($dashboardData) && !empty($dashboardData)) {
                    foreach ($dashboardData as $keyElement => $GraphData) {
                        $nbData = 0;
                        if (is_array($GraphData['data']) && !empty($GraphData['data'])) {
                            if ($GraphData['dataset'] >= 2) {
                                foreach ($GraphData['data'] as $arrayGraphData) {
                                    unset($arrayGraphData[0]);
                                    foreach ($arrayGraphData as $dataGraphSingle) {
                                        if (!empty($dataGraphSingle)) {
                                            $nbData = 1;
                                        }
                                    }
                                }
                            } else {
                                foreach ($GraphData['data'] as $dataGraphSingle) {
                                    $nbData += $dataGraphSingle;
                                }
                            }
                            if ($nbData > 0) {
                                if (is_array($GraphData['labels']) && !empty($GraphData['labels'])) {
                                    foreach ($GraphData['labels'] as $label) {
                                        $dataLegend[$keyElement][] = $langs->trans($label['label']);
                                        $dataColor[$keyElement][]  = $langs->trans($label['color']);
                                    }
                                }

                                $arrayKeys = array_keys($GraphData['data']);
                                foreach ($arrayKeys as $key) {
                                    if ($GraphData['dataset'] >= 2) {
                                        $data[$keyElement][] = $GraphData['data'][$key];
                                    } else {
                                        $data[$keyElement][] = [
                                            0 => $langs->trans($GraphData['labels'][$key]['label']),
                                            1 => $GraphData['data'][$key]
                                        ];
                                    }
                                }

                                $filename[$keyElement] = $keyElement . '.png';
                                $fileurl[$keyElement]  = DOL_URL_ROOT . '/viewimage.php?modulepart=' . $moduleNameLowerCase . '&file=' . $keyElement . '.png';

                                $graph = new DolGraph();
                                $graph->SetData($data[$keyElement]);

                                if ($GraphData['dataset'] >= 2) {
                                    $graph->SetLegend($dataLegend[$keyElement]);
                                }
                                $graph->SetDataColor($dataColor[$keyElement]);
                                $graph->SetType([$GraphData['type'] ?? 'pie']);
                                $graph->SetWidth($GraphData['width'] ?? $width);
                                $graph->SetHeight($GraphData['height'] ?? $height);
                                $graph->setShowLegend(2);
                                $graph->draw($filename[$keyElement], $fileurl[$keyElement]);
                                print '<div>';
                                print load_fiche_titre($GraphData['title'], $GraphData['morehtmlright'], $GraphData['picto']);
                                print $graph->show();
                                print '</div>';
                            }
                        }
                    }
                }
            }
        }

        print '</div></div></div>';
        print '</form>';
    }
}