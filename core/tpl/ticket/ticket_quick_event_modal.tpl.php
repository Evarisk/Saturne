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
 * \file    core/tpl/ticket/ticket_quick_event_modal.tpl.php
 * \ingroup saturne
 * \brief   Template of the modal tracing an event on the ticket card, opened by the "Add an event" button
 */

/**
 * The following vars must be defined :
 * Global   : $langs
 * Objects  : $form, $object (Ticket)
 * Variable : $quickEventTypes (saturne_ticket_get_quick_event_types(), the first one is picked by default)
 */ ?>

<!-- The form wraps the modal: the layout of .modal-container relies on its direct children. js/modules/ticket.js posts it -->
<form method="POST" action="<?php echo DOL_URL_ROOT . '/ticket/card.php?track_id=' . urlencode($object->track_id); ?>" class="saturne-ticket-quick-event-form" data-error="<?php echo dol_escape_htmltag($langs->trans('Error')); ?>">
    <input type="hidden" name="token" value="<?php echo newToken(); ?>">
    <input type="hidden" name="action" value="<?php echo SATURNE_TICKET_QUICK_EVENT_ACTION; ?>">

    <div class="wpeo-modal modal-flex modal-close-only-with-button saturne-ticket-quick-event" id="saturne-ticket-quick-event">
        <div class="modal-container wpeo-modal-event">
            <div class="modal-header">
                <h2 class="modal-title"><?php echo $langs->trans('TicketQuickEvent') . ' - ' . dol_escape_htmltag($object->ref); ?></h2>
                <div class="modal-close"><i class="fas fa-times"></i></div>
            </div>

            <div class="modal-content">
                <div class="saturne-ticket-quick-event__field">
                    <span class="saturne-ticket-quick-event__label fieldrequired"><?php echo $langs->trans('Type'); ?></span>
                    <div class="saturne-ticket-quick-event__types" role="radiogroup" aria-label="<?php echo dol_escape_htmltag($langs->trans('Type')); ?>">
                        <?php foreach ($quickEventTypes as $typeCode => $type) : ?>
                            <label class="saturne-ticket-quick-event__type">
                                <input type="radio" name="quickeventtype" value="<?php echo dol_escape_htmltag($typeCode); ?>"<?php echo $typeCode === array_key_first($quickEventTypes) ? ' checked' : ''; ?>>
                                <span><i class="<?php echo $type['icon']; ?>"></i><?php echo dol_escape_htmltag($type['label']); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="saturne-ticket-quick-event__field">
                    <span class="saturne-ticket-quick-event__label fieldrequired"><?php echo $langs->trans('Date'); ?></span>
                    <!-- Shown and read back in the timezone of the user, as on the core event card; "Now" takes the time of the computer -->
                    <div><?php echo $form->selectDate(dol_now(), 'quickeventdate', 1, 1, 0, '', 1, 2, 0, '', '', '', '', 1, '', '', 'tzuserrel'); ?></div>
                </div>

                <div class="saturne-ticket-quick-event__field">
                    <label class="saturne-ticket-quick-event__label fieldrequired" for="quickeventlabel"><?php echo $langs->trans('Label'); ?></label>
                    <input type="text" id="quickeventlabel" name="quickeventlabel" maxlength="128" required placeholder="<?php echo dol_escape_htmltag($langs->trans('TicketQuickEventLabelPlaceholder')); ?>">
                </div>

                <div class="saturne-ticket-quick-event__field">
                    <label class="saturne-ticket-quick-event__label" for="quickeventdescription"><?php echo $langs->trans('Description'); ?></label>
                    <textarea id="quickeventdescription" name="quickeventdescription" rows="8" placeholder="<?php echo dol_escape_htmltag($langs->trans('TicketQuickEventDescriptionPlaceholder')); ?>"></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <div class="wpeo-button button-grey modal-close"><span><?php echo $langs->trans('Cancel'); ?></span></div>
                <button type="submit" class="wpeo-button button-blue no-load"><i class="fas fa-plus pictofixedwidth"></i><span><?php echo $langs->trans('Add'); ?></span></button>
            </div>
        </div>
    </div>
</form>
