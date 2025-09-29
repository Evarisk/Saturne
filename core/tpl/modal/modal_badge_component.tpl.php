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
 * \file    core/tpl/modal/modal_badge_component.tpl.php
 * \ingroup saturne
 * \brief   Template page for modal badge component
 */

/**
 * The following vars must be defined:
 * Global   : $langs
 * Objects  : $object
 * Variable : $taskNextValue
 */ ?>

<div class="wpeo-modal modal-badge-component" id="badge_component">
    <div class="modal-container wpeo-modal-event">
        <!-- Modal-Header -->
        <div class="modal-header">
            <h2 class="modal-title"></h2>
            <?php
                echo saturne_get_modal_header_recap_html([
                    'iconClass' => 'fa-solid fa-user',   // L'icône que vous voulez pour le header
                    'title'     => $supplierData->name,  // Le nom du fournisseur/entité
                    'details'   => [$supplierData->ref], // La référence/ID
                ]);
            ?>
            <div class="modal-close"><i class="fas fa-2x fa-times"></i></div>
        </div>
        <!-- Modal-Content -->
        <div class="modal-content">
            <div class="answer-task-container">
                <textarea id="myTextarea" name="myTextarea" rows="4" cols="50"></textarea>
            </div>
        </div>
        <!-- Modal-Footer -->
        <div class="modal-footer">
            <div class="wpeo-button modal-close" id="update_badge_component">
                <i class="fas fa-save pictofixedwidth"></i><?php echo $langs->trans('Save'); ?>
            </div>
        </div>
    </div>
</div>
