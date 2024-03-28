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
 * \file    core/tpl/public/public_confirmation_view.tpl.php
 * \ingroup saturne
 * \brief   Template page for public confirmation view
 */

/**
 * The following vars must be defined :
 * Parameters : $varArray[]
 * options : icon, moreCss[], className[], confirmationTitle, buttons[]
 */ ?>

<?php $index = 0 ?>

<div class="public-card__confirmation" style="display: none;">
    <div class="confirmation-container">
        <?php
        print '<i style="color : ' . $confirmationParams['moreCss'][$index] . ';" class="confirmation-icon ' . $confirmationParams['icon'] . '"></i>';
        print '<div style="color: ' . $confirmationParams['moreCss'][$index] .';" class="confirmation-title"> ' . $langs->transnoentities($confirmationParams['confirmationTitle']) . ' </div>';
        if (isset($confirmationParams['buttons'][$index + 1])) {
            foreach ($confirmationParams['buttons'] as $button) {
                print '<button type="submit" class="confirmation-' . $confirmationParams['className'][$index] .' wpeo-button button-' . $confirmationParams['moreCss'][$index] . ' marginrightonly"> '. $langs->transnoentities($button, $count) . ' </button>';
                $index++;
            }
        } else {
            print '<button type="submit" class="confirmation-close wpeo-button button-' . $confirmationParams['moreCss'][1] . '"> '. $langs->transnoentities($confirmationParams['buttons'][0], $count) . ' </button>';
        }
        ?>
    </div>
</div>
