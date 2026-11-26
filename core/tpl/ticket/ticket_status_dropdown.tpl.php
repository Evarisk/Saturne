<?php

/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/ticket/ticket_status_dropdown.tpl.php
 * \ingroup saturne
 * \brief   Template of the status badge of the ticket card, turned into the list of the statuses the ticket can switch to
 *
 * js/modules/ticket.js posts the picked status, then updates the card in place.
 */

/**
 * The following vars must be defined :
 * Global   : $langs
 * Objects  : $object (Ticket)
 * Variable : $statusHtml (status shown by the core banner), $statusChoices (saturne_ticket_get_status_choices())
 */

$statusFormUrl = DOL_URL_ROOT . '/ticket/card.php?track_id=' . urlencode($object->track_id); ?>

<div class="wpeo-dropdown dropdown-right saturne-ticket-status" data-error="<?php echo dol_escape_htmltag($langs->trans('Error')); ?>">
    <button type="button" class="dropdown-toggle saturne-ticket-status__toggle" title="<?php echo dol_escape_htmltag($langs->trans('TicketStatusChange')); ?>" aria-haspopup="true">
        <?php echo $statusHtml; ?>
    </button>
    <ul class="saturne-dropdown-content saturne-ticket-status__list">
        <?php foreach ($statusChoices as $status => $choice) : ?>
            <li>
                <form method="POST" action="<?php echo $statusFormUrl; ?>">
                    <input type="hidden" name="token" value="<?php echo newToken(); ?>">
                    <input type="hidden" name="action" value="<?php echo SATURNE_TICKET_STATUS_ACTION; ?>">
                    <input type="hidden" name="new_status" value="<?php echo (int) $status; ?>">
                    <button type="submit" class="dropdown-item saturne-ticket-status__item">
                        <?php echo $choice['picto']; ?>
                        <span><?php echo dol_escape_htmltag($choice['label']); ?></span>
                    </button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
