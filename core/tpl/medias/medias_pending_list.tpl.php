<?php
/* Copyright (C) 2025-2026 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/medias/medias_pending_list.tpl.php
 * \ingroup saturne
 * \brief   Medias of the library, as a list with a preview pane
 *
 * The list shows which records already hold a media and lets several of them be assigned to a
 * Dolibarr record in one go. It carries the description kept in the ECM index.
 *
 * The following vars must be defined:
 * Global     : $conf, $db, $langs, $user, $moduleNameLowerCase
 * Optional   : $pendingOnly (bool, default true) List only the medias no record holds yet
 */

require_once __DIR__ . '/../../../lib/object.lib.php';

// The caller decides whether the whole library or only its pending medias are listed
$pendingOnly     = !isset($pendingOnly) || $pendingOnly;
$pendingMedias   = saturne_get_library_medias($moduleNameLowerCase, $pendingOnly);
$pendingEntity   = $conf->entity ?? 1;
$pendingBaseUrl  = DOL_URL_ROOT . '/document.php?modulepart=ecm&attachment=0&entity=' . $pendingEntity . '&file=';
$pendingRelative = $moduleNameLowerCase . '/medias';
$objectsMetadata = saturne_get_objects_metadata();

// A media folder is named after an element type while the metadata are keyed by module and element:
// resolving them once gives every record badge its label and its picto
$elementsMetadata = [];
foreach ($objectsMetadata as $objectKey => $objectMetadata) {
    $elementKey = preg_replace('/^' . preg_quote($moduleNameLowerCase, '/') . '_/', '', $objectKey);

    $elementsMetadata[$elementKey] = $objectMetadata;
}
?>
<div class="saturne-pending" data-module="<?php echo dol_escape_htmltag($moduleNameLowerCase); ?>">
    <?php if (empty($pendingMedias)) : ?>
        <div class="saturne-pending-empty">
            <i class="fas fa-check-circle"></i>
            <span><?php echo $langs->trans('NoPendingMedia'); ?></span>
        </div>
    <?php else : ?>
        <div class="saturne-pending-toolbar">
            <span class="saturne-pending-modes">
                <span class="saturne-pending-mode active" data-mode="details" title="<?php echo dol_escape_htmltag($langs->trans('ViewDetails')); ?>"><i class="fas fa-list"></i></span>
                <span class="saturne-pending-mode" data-mode="tiles" title="<?php echo dol_escape_htmltag($langs->trans('ViewLargeIcons')); ?>"><i class="fas fa-th-large"></i></span>
                <span class="saturne-pending-mode" data-mode="compact" title="<?php echo dol_escape_htmltag($langs->trans('ViewCompactList')); ?>"><i class="fas fa-bars"></i></span>
            </span>
        </div>
        <div class="saturne-pending-body">
            <div class="saturne-pending-list">
                <div class="saturne-pending-head">
                    <span class="saturne-pending-col-check">
                        <input type="checkbox" id="saturne-pending-select-all" class="saturne-pending-select-all">
                    </span>
                    <span class="saturne-pending-col-thumb"></span>
                    <span class="saturne-pending-col-name"><?php echo $langs->trans('Name'); ?></span>
                    <span class="saturne-pending-col-date"><?php echo $langs->trans('Date'); ?></span>
                    <span class="saturne-pending-col-size"><?php echo $langs->trans('Size'); ?></span>
                    <span class="saturne-pending-col-records"><?php echo $langs->trans('AssignedTo'); ?></span>
                    <span class="saturne-pending-col-description"><?php echo $langs->trans('Description'); ?></span>
                </div>
                <div class="saturne-pending-rows">
                    <?php foreach ($pendingMedias as $pendingMedia) :
                        $pendingThumb = $pendingBaseUrl . urlencode($pendingRelative . '/thumbs/' . saturne_get_thumb_name($pendingMedia['name'], 'small'));
                        $pendingFull  = $pendingBaseUrl . urlencode($pendingRelative . '/' . $pendingMedia['name']);
                        ?>
                        <div class="saturne-pending-row" data-filename="<?php echo dol_escape_htmltag($pendingMedia['name']); ?>" data-url="<?php echo dol_escape_htmltag($pendingFull); ?>">
                            <span class="saturne-pending-col-check">
                                <input type="checkbox" class="saturne-pending-select">
                            </span>
                            <span class="saturne-pending-col-thumb">
                                <img class="saturne-pending-thumb" src="<?php echo dol_escape_htmltag($pendingThumb); ?>" loading="lazy" alt="">
                            </span>
                            <span class="saturne-pending-col-name" title="<?php echo dol_escape_htmltag($pendingMedia['name']); ?>"><?php echo dol_escape_htmltag($pendingMedia['name']); ?></span>
                            <span class="saturne-pending-col-date"><?php echo dol_print_date($pendingMedia['date'], 'dayhour'); ?></span>
                            <span class="saturne-pending-col-size"><?php echo dol_print_size($pendingMedia['size'], 1, 1); ?></span>
                            <span class="saturne-pending-col-records">
                                <?php if (empty($pendingMedia['records'])) : ?>
                                    <span class="saturne-pending-norecord">&mdash;</span>
                                <?php else :
                                    foreach ($pendingMedia['records'] as $pendingRecord) :
                                        $recordMetadata = $elementsMetadata[$pendingRecord['element']] ?? [];
                                        $recordLabel    = !empty($recordMetadata['langs']) ? $langs->trans($recordMetadata['langs']) : ucfirst($pendingRecord['element']);
                                        ?>
                                        <?php $recordLink = saturne_media_record_link($moduleNameLowerCase, $pendingRecord['element'], $pendingRecord['ref']); ?>
                                        <span class="saturne-pending-record" title="<?php echo dol_escape_htmltag($recordLabel . ' - ' . $pendingRecord['ref']); ?>">
                                            <?php if (dol_strlen($recordLink) > 0) {
                                                echo $recordLink;
                                            } else {
                                                // No class answers for that folder: show the reference with a neutral icon
                                                echo img_picto('', $recordMetadata['picto'] ?? 'fontawesome_fa-folder-open_fas_#94a3b8') . ' ' . dol_escape_htmltag($pendingRecord['ref']);
                                            } ?>
                                            <i class="fas fa-unlink saturne-pending-unlink" data-element="<?php echo dol_escape_htmltag($pendingRecord['element']); ?>" data-ref="<?php echo dol_escape_htmltag($pendingRecord['ref']); ?>" title="<?php echo dol_escape_htmltag($langs->trans('UnlinkMediaFromRecord')); ?>"></i>
                                        </span>
                                    <?php endforeach;
                                endif; ?>
                            </span>
                            <span class="saturne-pending-col-description">
                                <input type="text" class="saturne-pending-description" value="<?php echo dol_escape_htmltag($pendingMedia['description']); ?>" placeholder="<?php echo dol_escape_htmltag($langs->trans('AddADescription')); ?>">
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="saturne-pending-preview">
                <div class="saturne-pending-preview-placeholder"><?php echo $langs->trans('SelectAMediaToPreview'); ?></div>
                <img class="saturne-pending-preview-image" alt="">
                <div class="saturne-pending-preview-name"></div>
            </div>
        </div>
        <div class="saturne-pending-footer">
            <span class="saturne-pending-counter" data-label="<?php echo dol_escape_htmltag($langs->trans('SelectedMedias')); ?>" data-total="<?php echo dol_escape_htmltag(count($pendingMedias) . ' ' . $langs->trans($pendingOnly ? 'PendingMedias' : 'AllMedias')); ?>"><?php echo count($pendingMedias) . ' ' . $langs->trans($pendingOnly ? 'PendingMedias' : 'AllMedias'); ?></span>
            <?php // select2 of Dolibarr builds its helper selector from the name: without one it ends up
                  // with input[aria-controls*=] and throws ?>
            <select class="saturne-pending-object-type flat" name="saturne_pending_object_type">
                <option value=""><?php echo $langs->trans('ObjectType'); ?></option>
                <?php foreach ($objectsMetadata as $objectKey => $objectMetadata) :
                    if (!empty($objectMetadata['alias_of'])) {
                        continue;
                    }
                    if (!empty($objectMetadata['langfile'])) {
                        $langs->load($objectMetadata['langfile']);
                    } ?>
                    <option value="<?php echo dol_escape_htmltag($objectKey); ?>" data-picto="<?php echo dol_escape_htmltag(img_picto('', $objectMetadata['picto'])); ?>"><?php echo dol_escape_htmltag($langs->trans($objectMetadata['langs'])); ?></option>
                <?php endforeach; ?>
            </select>
            <select class="saturne-pending-object flat" name="saturne_pending_object" disabled>
                <option value=""><?php echo $langs->trans('SelectAnObjectType'); ?></option>
            </select>
            <div class="wpeo-button button-blue saturne-pending-assign button-disable">
                <i class="fas fa-link"></i> <?php echo $langs->trans('AssignSelectedMedias'); ?>
            </div>
        </div>
    <?php endif; ?>
</div>
