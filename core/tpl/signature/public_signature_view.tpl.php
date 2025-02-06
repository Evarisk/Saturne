<?php
/* Copyright (C) 2021-2024 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/signature/public_signature_view.tpl.php
 * \ingroup saturne
 * \brief   Template page for public signature view
 */

/**
 * The following vars must be defined :
 * Global     : $conf, $langs
 * Parameters : $objectType, $trackID
 * Objects    : $object, $signatory
 * Variable   : $fileExists, $moduleNameLowerCase, $moreParams
 */ ?>

<div class="public-card__container" data-public-interface="true">
    <?php if (getDolGlobalInt('SATURNE_ENABLE_PUBLIC_INTERFACE')) : ?>
        <input type="hidden" name="token" value="<?php echo newToken(); ?>">

        <div class="public-card__header wpeo-gridlayout grid-2 grid-gap-2">
            <div class="header-information">
                <div class="<?php echo $moreParams['moreCSS'] ?? ''; ?>"><a href="#" onclick="window.close();" class="information-back">
                    <i class="fas fa-sm fa-chevron-left"></i>
                    <?php echo $langs->trans('Back'); ?>
                </a></div>
                <div class="information-title"><?php echo $langs->trans('ElectronicSignature'); ?></div>
                <div class="information-user"><?php echo dol_strtoupper($signatory->lastname) . ' ' . ucfirst($signatory->firstname); ?></div>
            </div>

            <div class="header-objet">
                <div class="objet-container">
                    <div class="objet-info">
                        <div class="objet-type"><?php echo $langs->trans(ucfirst($objectType)); ?></div>
                        <div class="objet-label"><?php echo $object->ref . ' ' . $object->label; ?></div>
                    </div>
                    <div class="objet-actions file-generation">
                        <?php $path = DOL_MAIN_URL_ROOT . '/custom/' . $moduleNameLowerCase . '/documents/temp/'; ?>
                        <input type="hidden" class="specimen-name" data-specimen-name="<?php echo $objectType . '_specimen_' . $trackID . '.odt'; ?>">
                        <input type="hidden" class="specimen-path" data-specimen-path="<?php echo $path; ?>">
                        <?php if (GETPOSTISSET('document_type') && $fileExists) : ?>
                            <div class="wpeo-button button-square-40 button-rounded button-blue auto-download"><i class="fas fa-download"></i></div>
                        <?php else : ?>
                            <div class="wpeo-button button-square-40 button-rounded button-grey"><i class="fas fa-download"></i></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="public-card__content signature">
            <div class="signature-element">
                <?php if (empty($signatory->signature) && ((defined(get_class($object) . '::STATUS_VALIDATED') && $object->status == $object::STATUS_VALIDATED) || $object->status == 1) && $signatory->attendance != SaturneSignature::ATTENDANCE_ABSENT) : ?>
                    <canvas class="canvas-container editable canvas-signature"></canvas>
                    <div class="signature-erase wpeo-button button-square-40 button-rounded button-grey"><span><i class="fas fa-eraser"></i></span></div>
                <?php else : ?>
                    <div class="canvas-container">
                        <img src='<?php echo $signatory->signature ?>' alt="">
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="public-card__footer">
            <?php if (empty($signatory->signature) && ((defined(get_class($object) . '::STATUS_VALIDATED') && $object->status == $object::STATUS_VALIDATED) || $object->status == 1) && $signatory->attendance != SaturneSignature::ATTENDANCE_ABSENT) : ?>
                <div class="signature-validate wpeo-button button-grey <?php echo $moreParams['moreCSS'] ?? ''; ?>"><i class="fas fa-save"></i> <?php echo $langs->trans('SignatureSaveButton'); ?></div>
            <?php endif; ?>
        </div>
    <?php else :
        print '<div class="center">' . $langs->trans('SignaturePublicInterfaceForbidden') . '</div>';
    endif; ?>
</div>

<?php
if (isset($moreParams['useConfirmation'])) {
    $confirmationParams = [
        'picto'             => 'fontawesome_fa-check-circle_fas_#47e58e',
        'color'             => '#47e58e',
        'confirmationTitle' => 'SavedSignature',
        'buttonParams'      => ['CloseModal' => 'button-blue signature-confirmation-close']
    ];
    require_once __DIR__ . '/../utils/confirmation_view.tpl.php';
}
