<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
 * \file    class/saturneform.class.php
 * \ingroup saturne
 * \brief   Class file for manage SaturneForm
 */

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';

/**
 * Class for SaturneForm
 */
class SaturneForm
{
    /**
     * @var bool Browser layout is on phone
     */
    public bool $OnPhone = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $conf;

        if ($conf->browser->layout == 'phone') {
            $this->OnPhone = true;
        }
    }

    /**
     * Show modify button
     *
     * @param SaturneObject $object     Current object
     * @param array         $moreParams More parameters
     */
    public function showModifyButton(SaturneObject $object, array $moreParams = [])
    {
        global $langs;

        // Modify
        $displayButton = $this->OnPhone ? '<i class="fas fa-edit fa-2x"></i>' : '<i class="fas fa-edit"></i>' . ' ' . $langs->trans('Modify');
        if (($object->status == $object::STATUS_DRAFT || $moreParams['check'])) {
            print '<a class="butAction" id="actionButtonEdit" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . $moreParams['url'] . '&action=edit' . '">' . $displayButton . '</a>';
        } else {
            print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeDraft', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '">' . $displayButton . '</span>';
        }
    }

    /**
     * Show validate button
     *
     * @param SaturneObject $object     Current object
     * @param array         $moreParams More parameters
     */
    public function showValidateButton(SaturneObject $object, array $moreParams = [])
    {
        global $langs;

        // Validate
        $displayButton = $this->OnPhone ? '<i class="fas fa-check fa-2x"></i>' : '<i class="fas fa-check"></i>' . ' ' . $langs->trans('Validate');
        if (($object->status == $object::STATUS_DRAFT || $moreParams['check'])) {
            print '<span class="butAction" id="actionButtonValidate">' . $displayButton . '</span>';
        } elseif ($object->status < $object::STATUS_DRAFT) {
            print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeDraft', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '">' . $displayButton . '</span>';
        }
    }

    /**
     * Show reopen button
     *
     * @param SaturneObject $object Current object
     */
    public function showReOpenButton(SaturneObject $object)
    {
        global $langs;

        // ReOpen
        $displayButton = $this->OnPhone ? '<i class="fas fa-lock-open fa-2x"></i>' : '<i class="fas fa-lock-open"></i>' . ' ' . $langs->trans('ReOpenDoli');
        if ($object->status == $object::STATUS_VALIDATED) {
            print '<span class="butAction" id="actionButtonInProgress">' . $displayButton . '</span>';
        } elseif ($object->status > $object::STATUS_VALIDATED) {
            print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeValidated', $langs->transnoentities('The' . ucfirst($object->element)))) . '">' . $displayButton . '</span>';
        }
    }

    /**
     * Show sign button
     *
     * @param  SaturneObject    $object    Current object
     * @param  SaturneSignature $signatory Signatory object
     * @throws Exception
     */
    public function showSignButton(SaturneObject $object, SaturneSignature $signatory)
    {
        global $langs;

        // Sign
        $displayButton = $this->OnPhone ? '<i class="fas fa-signature fa-2x"></i>' : '<i class="fas fa-signature"></i>' . ' ' . $langs->trans('Sign');
        if ($object->status == $object::STATUS_VALIDATED && !$signatory->checkSignatoriesSignatures($object->id, $object->element)) {
            print '<a class="butAction" id="actionButtonSign" href="' . dol_buildpath('/custom/saturne/view/saturne_attendants.php?id=' . $object->id . '&module_name=DoliSIRH&object_type=' . $object->element . '&document_type=TimeSheetDocument&attendant_table_mode=simple', 3) . '">' . $displayButton . '</a>';
        } else {
            print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeValidatedToSign', $langs->transnoentities('The' . ucfirst($object->element)))) . '">' . $displayButton . '</span>';
        }
    }

    /**
     * Show lock button
     *
     * @param  SaturneObject    $object    Current object
     * @param  SaturneSignature $signatory Signatory object
     * @throws Exception
     */
    public function showlockButton(SaturneObject $object, SaturneSignature $signatory)
    {
        global $langs;

        // Lock
        $displayButton = $this->OnPhone ? '<i class="fas fa-lock fa-2x"></i>' : '<i class="fas fa-lock"></i>' . ' ' . $langs->trans('Lock');
        if ($object->status == $object::STATUS_VALIDATED && $signatory->checkSignatoriesSignatures($object->id, $object->element)) {
            print '<span class="butAction" id="actionButtonLock">' . $displayButton . '</span>';
        } else {
            print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('AllSignatoriesMustHaveSigned', $langs->transnoentities('The' . ucfirst($object->element)))) . '">' . $displayButton . '</span>';
        }
    }

    /**
     * Show send email button
     *
     * @param SaturneObject $object    Current object
     * @param string        $uploadDir Upload dir path
     */
    public function showSendEmailButton(SaturneObject $object, string $uploadDir)
    {
        global $langs;

        // Send email
        $displayButton = $this->OnPhone ? '<i class="fas fa-envelope fa-2x"></i>' : '<i class="fas fa-envelope"></i>' . ' ' . $langs->trans('SendMail');
        if ($object->status == $object::STATUS_LOCKED) {
            $fileParams = dol_most_recent_file($uploadDir . '/' . $object->element . 'document' . '/' . $object->ref);
            $file       = $fileParams['fullname'];
            if (file_exists($file) && !strstr($fileParams['name'], 'specimen')) {
                $forceBuildDoc = 0;
            } else {
                $forceBuildDoc = 1;
            }
            print dolGetButtonAction($displayButton, '', 'default', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=presend&forcebuilddoc=' . $forceBuildDoc . '&mode=init#formmailbeforetitle');
        } else {
            print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeLockedToSendEmail', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '">' . $displayButton . '</span>';
        }
    }

    /**
     * Show archive button
     *
     * @param SaturneObject $object Current object
     */
    public function showArchiveButton(SaturneObject $object)
    {
        global $langs;

        // Archive
        $displayButton = $this->OnPhone ?  '<i class="fas fa-archive fa-2x"></i>' : '<i class="fas fa-archive"></i>' . ' ' . $langs->trans('Archive');
        if ($object->status == $object::STATUS_LOCKED) {
            print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=confirm_archive&token=' . newToken() . '">' . $displayButton . '</a>';
        } else {
            print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeLockedToArchive', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '">' . $displayButton . '</span>';
        }
    }

    /**
     * Show delete button
     *
     * @param SaturneObject $object             Current object
     * @param int           $permissionToDelete Delete object permission
     */
    public function showDeleteButton(SaturneObject $object, int $permissionToDelete)
    {
        global $langs;

        // Delete (need delete permission, or if draft, just need create/modify permission)
        $displayButton = $this->OnPhone ? '<i class="fas fa-trash fa-2x"></i>' : '<i class="fas fa-trash"></i>' . ' ' . $langs->trans('Delete');
        print dolGetButtonAction($displayButton, '', 'delete', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=delete&token=' . newToken(), '', $permissionToDelete || ($object->status == $object::STATUS_DRAFT));
    }
}
