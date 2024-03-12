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
 * Parameters : $confirmationTitle
 */ ?>

<div class="public-card__confirmation" style="display: none;">
    <div class="confirmation-container">
        <i class="confirmation-icon fas fa-check-circle"></i>
        <div class="confirmation-title"><?php echo $confirmationTitle; ?></div>
        <button type="submit" class="confirmation-close wpeo-button button-primary" onclick="window.close();"><?php echo $langs->trans('CloseModal'); ?></button>
    </div>
</div>
