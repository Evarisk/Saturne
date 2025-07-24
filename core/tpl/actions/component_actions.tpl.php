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
 * \file    core/tpl/actions/component_actions.tpl.php
 * \ingroup saturne
 * \brief   Template page for component actions
 */

/**
 * The following vars must be defined:
 * Global     : $conf, $langs, $user
 * Parameters : $action, $documentType, $moduleName, $moduleNameLowerCase, $objectType, $trackID
 * Objects    : $document, $object, $signatory
 * Variable   : $upload_dir
 */

// Action to update the badge component
if ($action == 'update_badge_component') {
    $data           = json_decode(file_get_contents('php://input'), true);
    $badgeComponent = $data['label'] ?? null;

    if ($badgeComponent) {
        // Assuming you have a function to update the badge component
        //$result = updateBadgeComponent($badgeComponent);

        $activity = new Activity($db);
        $activity->fetch($data['objectLine_id']);
        $activity->{$data['field']} = $badgeComponent;
        $activity->update($user);

        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'Badge component updated successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update badge component.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid badge component data.']);
    }
}
