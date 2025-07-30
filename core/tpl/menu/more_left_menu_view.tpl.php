<?php
/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/menu/more_left_menu_view.tpl.php
 * \ingroup saturne
 * \brief   Template page for more left menu view
 */

/**
 * The following vars must be defined :
 * Globals   : $langs, $user
 * Variables : $moreParams, $sideBarSecondaryNavigationTitle, $sideBarSecondaryTitle
 */

?>

<div class="sidebar-secondary <?php echo $moreParams['objectElement']; ?>-sidebar-secondary">
    <div class="sidebar-secondary_responsive">
        <i class="fas fa-bars pictofixedwidth"></i><?php echo $moreParams['sideBarSecondaryNavigationTitle'] ?? $langs->trans('SideBarSecondaryNavigationTitle'); ?>
    </div>
    <div class="sidebar-secondary__container">
        <div class="sidebar-secondary__header">
            <div class="sidebar-secondary__header-top">
                <?php
                    echo saturne_get_button_component_html([
                        'className' => 'linkElement', //@tod meilleur nom de classe
                        'href'      => dol_buildpath('custom/' . $moreParams['moduleNameLowerCase'] . '/view/' . $moreParams['moduleNameLowerCase'] . 'standard/' . $moreParams['moduleNameLowerCase'] . 'standard_card.php?id=' . getDolGlobalInt(dol_strtoupper($moreParams['moduleNameLowerCase']) . '_ACTIVE_STANDARD'), 1),
                        'iconClass' => 'fas fa-sitemap pictofixedwidth',
                        'spans'     => [
                            [
                                'label' => $moreParams['sideBarSecondaryTitle'] ?? $langs->trans('SideBarSecondaryTitle')
                            ]
                        ]
                    ]);
                ?>
                <?php if ($user->hasRight($moreParams['moduleNameLowerCase'], $moreParams['objectElement'], 'write')) : ?>
                    <div class="add-container">
                        <?php
                        echo saturne_get_button_component_html([
                            'className' => 'wpeo-button button-square-40 button-secondary wpeo-tooltip-event',
                            'href'      => dol_buildpath('custom/' . $moreParams['moduleNameLowerCase'] . '/view/' . $moreParams['objectElement'] . '/' . $moreParams['objectElement'] . '_card.php?action=create&element_type=0', 1),
                            'moreAttr'  => [
                                'data-direction' => 'bottom',
                                'data-color'     => 'light',
                                'aria-label'     => $langs->trans($moreParams['objectFields']['element_type']['arrayofkeyval'][0])
                            ],
                            'iconClass' => 'button-add fas fa-plus-circle',
                            'spans'     => [
                                [
                                    'className' => 'button-label',
                                    'label'     => $moreParams['objectFields']['element_type']['prefix'][0]
                                ]
                            ]
                        ]);

                        echo saturne_get_button_component_html([
                            'className' => 'wpeo-button button-square-40 wpeo-tooltip-event',
                            'href'      => dol_buildpath('custom/' . $moreParams['moduleNameLowerCase'] . '/view/' . $moreParams['objectElement'] . '/' . $moreParams['objectElement'] . '_card.php?action=create&element_type=1', 1),
                            'moreAttr'  => [
                                'data-direction' => 'bottom',
                                'data-color'     => 'light',
                                'aria-label'     => $langs->trans($moreParams['objectFields']['element_type']['arrayofkeyval'][1])
                            ],
                            'iconClass' => 'button-add fas fa-plus-circle',
                            'spans'     => [
                                [
                                    'className' => 'button-label',
                                    'label'     => $moreParams['objectFields']['element_type']['prefix'][1]
                                ]
                            ]
                        ]);
                        ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (!empty($objectElements)) : ?>
                <div class="sidebar-secondary__header-toolbar">
                    <div class="toggle-all toggle-minus" aria-label="<?php echo $langs->trans('WrapAll'); ?>"><span class="toggle-all-icon fas fa-caret-square-down"></span></div>
                </div>
            <?php endif; ?>
        </div>
        <ul class="workunit-list">
            <?php saturne_display_recurse_tree($moreParams, $objectElementTree); ?>
        </ul>
    </div>
</div>
