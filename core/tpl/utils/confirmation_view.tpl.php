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
 * \file    core/tpl/utils/confirmation_view.tpl.php
 * \ingroup saturne
 * \brief   Template page for confirmation view
 */

/**
 * The following vars must be defined :
 * Global     : $langs
 * Parameters : $confirmationParams
 * options    : $confirmationParams[picto, moreCSS, confirmationTitle, buttonLabels]
 */ ?>

<div class="card__confirmation" style="display: none;">
    <div class="confirmation-container">
        <?php
        print $confirmationParams['picto'] ? img_picto('', $confirmationParams['picto'], 'class="confirmation-icon"') : '';
        print $confirmationParams['confirmationTitle'] ? '<div style="color: ' . $confirmationParams['color'] . ';" class="confirmation-title"> ' . $langs->transnoentities($confirmationParams['confirmationTitle']) . ' </div>' : '';
        if (is_array($confirmationParams['buttonParams']) && !empty($confirmationParams['buttonParams'])) {
            foreach ($confirmationParams['buttonParams'] as $buttonLabel => $CSSButton) {
                print '<button type="submit" class="wpeo-button ' . $CSSButton . '">' . $langs->transnoentities($buttonLabel) . '</button>';
            }
        } ?>
    </div>
</div>
