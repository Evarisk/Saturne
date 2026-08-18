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
    <?php if (getDolGlobalInt('SATURNE_ENABLE_PUBLIC_INTERFACE')) :
        ?>
        <input type="hidden" name="token" value="<?php echo newToken(); ?>">

        <?php
        // Determine the document format from the module's default model.
        // Native PDF models (e.g. "preventionplandocument") do NOT end with "_odt".
        // ODT models end with "_odt" (e.g. "preventionplandocument_odt").
        $confDefaultModel = dol_strtoupper($moduleNameLowerCase) . '_' . dol_strtoupper($documentType ?? '') . '_DEFAULT_MODEL';
        $defaultModel     = getDolGlobalString($confDefaultModel, '');
        $isNativePdf      = (!empty($defaultModel) && !preg_match('/_odt$/i', $defaultModel));

        $confAutoPdf = dol_strtoupper($moduleNameLowerCase) . '_AUTOMATIC_PDF_GENERATION';
        $canServePdf = $isNativePdf || (!empty($conf->global->MAIN_ODT_AS_PDF) && getDolGlobalInt($confAutoPdf) > 0);

        $path = DOL_MAIN_URL_ROOT . '/custom/' . $moduleNameLowerCase . '/documents/temp/';
        $specimenExt = $canServePdf ? '.pdf' : '.odt';
        
        $isSpecimen = 0;
        $sourceDirDoc = $conf->$moduleNameLowerCase->multidir_output[$object->entity ?? 1] . '/' . strtolower($objectType) . 'document/' . dol_sanitizeFileName($object->ref) . '/';
        $files = dol_dir_list($sourceDirDoc, 'files', 1, '\.' . ($canServePdf ? 'pdf' : 'odt') . '$', null, 'date', SORT_DESC);
        if (!empty($document->last_main_doc)) {
            $originalName = $document->last_main_doc;
            if ($canServePdf) $originalName = preg_replace('/\.odt$/', '.pdf', $originalName);
        } elseif (!empty($files)) {
            $originalName = $files[0]['name'];
        } else {
            $safeRef = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $object->ref);
            $originalName = $objectType . '_' . $safeRef . $specimenExt;
        }
        
        $specimenName = $isSpecimen ? 'specimen_' . $originalName : $originalName;
        ?>

        <div class="public-card__header wpeo-gridlayout grid-2 grid-gap-2">
            <div class="header-information">
                <div class="<?php echo $moreParams['moreCSS'] ?? ''; ?>"><a href="#" onclick="window.close();" class="information-back" id="signature-back-btn" style="display:none;">
                    <i class="fas fa-sm fa-chevron-left"></i>
                    <?php echo $langs->trans('Back'); ?>
                </a></div>
                <script>
                    // Show the Back button only when the page was opened by script (window.open)
                    // so window.close() will actually work. In all other cases (direct link from email),
                    // the button is hidden because it has no useful destination.
                    if (window.opener) {
                        document.getElementById('signature-back-btn').style.display = '';
                    }
                </script>
                <div class="information-title"><?php echo $langs->trans('ElectronicSignature'); ?></div>
                <div class="information-user"><?php echo dol_strtoupper($signatory->lastname) . ' ' . ucfirst($signatory->firstname); ?></div>
            </div>

            <?php if (!empty($object->id)) :
                ?>
            <div class="header-objet file-generation">
                <div class="objet-container">
                    <div class="objet-info">
                        <div class="objet-type"><?php echo $langs->trans(ucfirst($objectType)); ?></div>
                        <div class="objet-label">
                            <?php if (GETPOSTISSET('document_type') && $fileExists) : ?>
                                <a href="javascript:void(0);" class="auto-download" style="color: inherit; text-decoration: underline;">
                                    <i class="far fa-file-<?php echo ($canServePdf ? 'pdf' : 'word'); ?>"></i> <?php echo $originalName; ?>
                                </a>
                            <?php else: ?>
                                <?php echo $object->ref . ' ' . $object->label; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="objet-actions">
                        <input type="hidden" class="specimen-name" data-specimen-name="<?php echo $specimenName; ?>">
                        <input type="hidden" class="specimen-path" data-specimen-path="<?php echo $path; ?>">
                        <?php if (GETPOSTISSET('document_type') && $fileExists) :
                            ?>
                            <div class="wpeo-button button-square-40 button-rounded button-blue auto-download"><i class="fas fa-download"></i></div>
                            <?php
                        else :
                            ?>
                            <div class="wpeo-button button-square-40 button-rounded button-grey wpeo-tooltip-event" aria-label="<?php echo dol_escape_htmltag($langs->trans('DocumentNotAvailable')); ?>"><i class="fas fa-download"></i></div>
                            <?php
                        endif; ?>
                    </div>
                </div>
            </div>
                <?php
            endif; ?>
        </div>

        <?php if (!$canServePdf && GETPOSTISSET('document_type')) : ?>
        <div class="public-card__info" style="background:#fef9e7;border:1px solid #f0c674;border-radius:6px;padding:10px 16px;margin:8px 16px;font-size:0.9em;color:#856404;">
            <i class="fas fa-info-circle"></i>
            <?php echo $langs->trans('PublicSignatureNoPdfAvailable'); ?>
        </div>
        <?php endif; ?>

        <div class="public-card__content signature">
            <div class="signature-element">
                <?php if (empty($signatory->signature) && ((defined(get_class($object) . '::STATUS_VALIDATED') && $object->status == $object::STATUS_VALIDATED) || $object->status == 1) && $signatory->attendance != SaturneSignature::ATTENDANCE_ABSENT) :
                    ?>
                    <canvas class="canvas-container editable canvas-signature"></canvas>
                    <div class="signature-erase wpeo-button button-square-40 button-rounded button-grey"><span><i class="fas fa-eraser"></i></span></div>
                    <?php
                else :
                    ?>
                    <div class="canvas-container">
                        <img src='<?php echo $signatory->signature ?>' alt="">
                    </div>
                    <?php
                endif; ?>
            </div>
        </div>

        <div class="public-card__footer">
            <?php if (empty($signatory->signature) && ((defined(get_class($object) . '::STATUS_VALIDATED') && $object->status == $object::STATUS_VALIDATED) || $object->status == 1) && $signatory->attendance != SaturneSignature::ATTENDANCE_ABSENT) :
                ?>
                <div class="signature-validate wpeo-button button-grey <?php echo $moreParams['moreCSS'] ?? ''; ?>"><i class="fas fa-save"></i> <?php echo $langs->trans('SignatureSaveButton'); ?></div>
                <?php
            endif; ?>
        </div>
        <?php
    else :
        print '<div class="center">' . $langs->trans('SignaturePublicInterfaceForbidden') . '</div>';
    endif; ?>
</div>

<?php
if (isset($moreParams['useConfirmation'])) {
    $downloadLink = '';
    if (GETPOSTISSET('document_type') && $fileExists) {
        $downloadLink = '<div class="file-generation-modal" style="margin-bottom:10px;"><input type="hidden" class="specimen-name" data-specimen-name="'.$specimenName.'"><input type="hidden" class="specimen-path" data-specimen-path="'.$path.'"><a href="javascript:void(0);" class="auto-download" style="text-decoration: underline; color: #47e58e; font-weight: bold;"><i class="far fa-file-'.($canServePdf ? 'pdf' : 'word').'"></i> '.$originalName.'</a></div>';
    }

    $confirmationParams = [
        'picto'             => 'fontawesome_fa-check-circle_fas_#47e58e',
        'color'             => '#47e58e',
        'confirmationTitle' => 'SavedSignature',
        'confirmationContent' => $downloadLink,
        'buttonParams'      => ['CloseModal' => 'button-blue signature-confirmation-close']
    ];
    require_once __DIR__ . '/../utils/confirmation_view.tpl.php';
}
?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    if (window.saturne && window.saturne.signature) {
        window.saturne.signature.autoDownloadSpecimen = function() {
            let element        = $(this).closest('.file-generation, .file-generation-modal');
            let token          = window.saturne.toolbox.getToken();
            let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);

            $.ajax({
                url: document.URL + querySeparator + 'action=builddoc&token=' + token,
                type: 'POST',
                success: function(resp) {
                    let $newElement = $(resp).find('.header-objet.file-generation');
                    let filename = $newElement.find('.specimen-name').attr('data-specimen-name');
                    let path     = $newElement.find('.specimen-path').attr('data-specimen-path');

                    $('.header-objet.file-generation').replaceWith($newElement);

                    // Only attempt to download if generation succeeded and the file actually exists
                    if (filename && path && $newElement.find('.auto-download').length > 0) {
                        window.saturne.signature.download(path + filename, filename);
                    } else {
                        console.error('Failed to generate or locate the specimen document.');
                        $.jnotify('Echec de la génération du document', 'error');
                    }
                    $.ajax({
                        url: document.URL + querySeparator + 'action=remove_file&token=' + token,
                        type: 'POST',
                        success: function() {},
                        error: function() {}
                    });
                },
                error: function() {}
            });
        };
    }
});
</script>
