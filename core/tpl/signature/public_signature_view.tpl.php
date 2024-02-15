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
 * Variable   : $moduleNameLowerCase, $fileExists
 */ ?>

<div class="signature-container">
    <?php if (!empty($conf->global->SATURNE_ENABLE_PUBLIC_INTERFACE)) : ?>
        <input type="hidden" name="token" value="<?php echo newToken(); ?>">
        <div class="informations">
            <span><?php echo $langs->trans('Hello') . ' ' . dol_strtoupper($signatory->lastname) . ' ' . ucfirst($signatory->firstname); ?></span>
            <span><?php echo $langs->trans('PublicDownloadDocument', $langs->trans($objectType), $object->ref . ' ' . $object->label); ?></span>
            <div class="file-generation">
                <?php $path = DOL_MAIN_URL_ROOT . '/custom/' . $moduleNameLowerCase . '/documents/temp/'; ?>
                <input type="hidden" class="specimen-name" data-specimen-name="<?php echo $objectType . '_specimen_' . $trackID . '.odt'; ?>">
                <input type="hidden" class="specimen-path" data-specimen-path="<?php echo $path; ?>">
                <?php if (GETPOSTISSET('document_type') && $fileExists) : ?>
                    <div class="wpeo-button button-square-50 button-primary auto-download"><i class="fas fa-download"></i></div>
                <?php else : ?>
                    <div class="wpeo-button button-square-50 button-grey"><i class="fas fa-download"></i></div>
                <?php endif; ?>
            </div>
            <button type="submit" class="wpeo-button button-square-50 button-grey" onclick="window.close();"><i class="fas fa-times"></i></button>
        </div>
        <div class="signature">
            <div class="signature-element">
                <?php if (empty($signatory->signature) && $object->status == $object::STATUS_VALIDATED && $signatory->attendance != SaturneSignature::ATTENDANCE_ABSENT) : ?>
                    <canvas class="canvas-container" style="height: 98%; width: 100%; border: #0b419b solid 2px"></canvas>
                    <div class="signature-erase wpeo-button button-square-50 button-grey"><span><i class="fas fa-eraser"></i></span></div>
                    <div class="signature-validate wpeo-button button-square-50 button-grey"><span><i class="fas fa-file-signature"></i></span></div>
                <?php else : ?>
                    <img src='<?php echo $signatory->signature ?>' alt="">
                    <span><?php echo $langs->trans('ThanksForSignDocument'); ?></span>
                    <button type="submit" class="wpeo-button button-primary" onclick="window.close();"><?php echo $langs->trans('CloseModal'); ?></button>
                <?php endif; ?>
            </div>
        </div>
    <?php else :
        print '<div class="center">' . $langs->trans('SignaturePublicInterfaceForbidden') . '</div>';
    endif; ?>
</div>
