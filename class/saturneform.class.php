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
 * \file    class/saturneform.class.php
 * \ingroup saturne
 * \brief   Class file for manage SaturneForm
 */

/**
 * Class for SaturneForm
 */
abstract class SaturneForm
{
    /**
     * Show modify button
     *
     * @param SaturneObject $object     Current object
     * @param array         $moreParams More parameters
     */
    public function showModifyButton(SaturneObject $object, array $moreParams = []): void
    {
        global $conf, $langs;

        $displayButton = $conf->browser->layout != 'classic' ? '<i class="fas fa-edit fa-2x"></i>' : '<i class="fas fa-edit"></i>' . ' ' . $langs->transnoentities('Modify');
        if (($object->status == $object::STATUS_DRAFT || $moreParams['check'])) {
            print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . $moreParams['url'] . '&action=edit' . '">' . $displayButton . '</a>';
        } elseif ($object->status < $object::STATUS_DRAFT) {
            print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->transnoentities('ObjectMustBeDraft', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '">' . $displayButton . '</span>';
        }
    }

    /**
     * Show validate button
     *
     * @param SaturneObject $object     Current object
     * @param array         $moreParams More parameters
     */
    public static function showValidateButton(SaturneObject $object, array $moreParams = []): void
    {
        global $conf, $langs;

        $displayButton = $conf->browser->layout != 'classic' ? '<i class="fas fa-check fa-2x"></i>' : '<i class="fas fa-check"></i>' . ' ' . $langs->transnoentities('Validate');
        if (($object->status == $object::STATUS_DRAFT || $moreParams['check'])) {
            print '<span class="butAction" id="actionButtonValidate">' . $displayButton . '</span>';
        } elseif ($object->status < $object::STATUS_DRAFT) {
            print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->transnoentities('ObjectMustBeDraft', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '">' . $displayButton . '</span>';
        }
    }

    /**
     * Show reopen button
     *
     * @param SaturneObject $object     Current object
     * @param array         $moreParams More parameters
     */
    public static function showReOpenButton(SaturneObject $object, array $moreParams = []): void
    {
        global $conf, $langs;

        $displayButton = $conf->browser->layout != 'classic' ? '<i class="fas fa-lock-open fa-2x"></i>' : '<i class="fas fa-lock-open"></i>' . ' ' . $langs->transnoentities('ReOpenDoli');
        if ($object->status == $object::STATUS_VALIDATED) {
            print '<span class="butAction" id="actionButtonInProgress">' . $displayButton . '</span>';
        } elseif ($object->status < $object::STATUS_VALIDATED) {
            print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->transnoentities('ObjectMustBeValidated', $langs->transnoentities('The' . ucfirst($object->element)))) . '">' . $displayButton . '</span>';
        }
    }

    /**
     * Show sign button
     *
     * @param  SaturneObject $object     Current object
     * @param  array         $moreParams More parameters
     * @throws Exception
     */
    public static function showSignButton(SaturneObject $object, array $moreParams = []): void
    {
        global $conf, $db, $document, $langs;

        $signatory = new SaturneSignature($db, $object->module, $object->element);

        $displayButton = $conf->browser->layout != 'classic' ? '<i class="fas fa-signature fa-2x"></i>' : '<i class="fas fa-signature"></i>' . ' ' . $langs->transnoentities('Sign');
        if ($object->status == $object::STATUS_VALIDATED && !$signatory->checkSignatoriesSignatures($object->id, $object->element)) {
            print '<a class="butAction" href="' . dol_buildpath('custom/saturne/view/saturne_attendants.php?id=' . $object->id . '&module_name='. $object->module . '&object_type=' . $object->element . '&document_type=' . $moreParams['documentType'] ?? get_class($document) . '&attendant_table_mode=simple', 3) . '">' . $displayButton . '</a>';
        } elseif ($object->status < $object::STATUS_VALIDATED) {
            print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->transnoentities('ObjectMustBeValidatedToSign', $langs->transnoentities('The' . ucfirst($object->element)))) . '">' . $displayButton . '</span>';
        }
    }

    /**
     * Show lock button
     *
     * @param  SaturneObject $object     Current object
     * @param  array         $moreParams More parameters
     * @throws Exception
     */
    public static function showLockButton(SaturneObject $object, array $moreParams = []): void
    {
        global $conf, $db, $langs;

        $signatory = new SaturneSignature($db, $object->module, $object->element);

        $displayButton = $conf->browser->layout != 'classic' ? '<i class="fas fa-lock fa-2x"></i>' : '<i class="fas fa-lock"></i>' . ' ' . $langs->transnoentities('Lock');
        if ($object->status == $object::STATUS_VALIDATED && $signatory->checkSignatoriesSignatures($object->id, $object->element)) {
            print '<span class="butAction" id="actionButtonLock">' . $displayButton . '</span>';
        } elseif ($object->status < $object::STATUS_LOCKED) {
            print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->transnoentities('AllSignatoriesMustHaveSigned', $langs->transnoentities('The' . ucfirst($object->element)))) . '">' . $displayButton . '</span>';
        }
    }

    /**
     * Show send email button
     *
     * @param SaturneObject $object     Current object
     * @param array         $moreParams More parameters
     */
    public static function showSendEmailButton(SaturneObject $object, array $moreParams = []): void
    {
        global $conf, $langs;

        $displayButton = $conf->browser->layout != 'classic' ? '<i class="fas fa-envelope fa-2x"></i>' : '<i class="fas fa-envelope"></i>' . ' ' . $langs->transnoentities('SendMail');
        if ($object->status >= $object::STATUS_VALIDATED) {
            $uploadDir  = $conf->{$object->module}->multidir_output[$conf->entity ?? 1];
            $fileParams = dol_most_recent_file($uploadDir . '/' . $object->element . 'document' . '/' . $object->ref);
            $file       = $fileParams['fullname'];
            if (file_exists($file) && !strstr($fileParams['name'], 'specimen')) {
                $forceBuildDoc = 0;
            } else {
                $forceBuildDoc = 1;
            }
            print dolGetButtonAction($displayButton, '', 'default', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=presend&forcebuilddoc=' . $forceBuildDoc . '&mode=init#formmailbeforetitle');
        } else {
            print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->transnoentities('ObjectMustBeValidatedToSendEmail', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '">' . $displayButton . '</span>';
        }
    }

    /**
     * Show archive button
     *
     * @param SaturneObject $object     Current object
     * @param array         $moreParams More parameters
     */
    public static function showArchiveButton(SaturneObject $object, array $moreParams = []): void
    {
        global $conf, $langs;

        $displayButton = $conf->browser->layout != 'classic' ?  '<i class="fas fa-archive fa-2x"></i>' : '<i class="fas fa-archive"></i>' . ' ' . $langs->transnoentities('Archive');
        if ($object->status == $object::STATUS_LOCKED) {
            print '<span class="butAction" id="actionButtonArchive">' . $displayButton . '</span>';
        } elseif ($object->status < $object::STATUS_ARCHIVED) {
            print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->transnoentities('ObjectMustBeLockedToArchive', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '">' . $displayButton . '</span>';
        }
    }

    /**
     * Show clone button
     *
     * @param SaturneObject $object     Current object
     * @param array         $moreParams More parameters
     */
    public static function showCloneButton(SaturneObject $object, array $moreParams = []): void
    {
        global $conf, $langs;

        $displayButton = $conf->browser->layout != 'classic' ? '<i class="fas fa-clone fa-2x"></i>' : '<i class="fas fa-clone"></i>' . ' ' . $langs->transnoentities('ToClone');
        print '<span class="butAction" id="actionButtonClone">' . $displayButton . '</span>';
    }

    /**
     * Show delete button
     *
     * @param SaturneObject $object     Current object
     * @param array         $moreParams More parameters
     */
    public static function showDeleteButton(SaturneObject $object, array $moreParams = []): void
    {
        global $conf, $langs, $user;

        $displayButton = $conf->browser->layout != 'classic' ? '<i class="fas fa-trash fa-2x"></i>' : '<i class="fas fa-trash"></i>' . ' ' . $langs->transnoentities('Delete');
        if ($object->status == $object::STATUS_DRAFT || $user->hasRight($object->module, $object->element, 'delete')) {
            print '<span class="butAction butActionDelete" id="actionButtonDelete">' . $displayButton . '</span>';
        }
    }

    /**
     * Show buttons for actions
     *
     * @param object $object    Current object
     * @param string $action    Action
     * @param array  $moreParams More parameters
     */
    public static function showButtons(object $object, string $action = '', array $moreParams = []): void
    {
        global $hookmanager;

        $parameters = [];
        $resHook    = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action);
        if ($resHook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        if (empty($resHook)) {
            $buttons = ['showModifyButton', 'showValidateButton', 'showReOpenButton', 'showSignButton', 'showLockButton', 'showSendEmailButton', 'showArchiveButton', 'showCloneButton', 'showDeleteButton'];
            foreach ($buttons as $method) {
                if (!isset($moreParams['override' . ucfirst($method)])) {
                    self::$method($object, $moreParams[$method] ?? []);
                }
            }
        }
    }

    /**
     * Draft confirmation
     *
     * @param  array  $moreParams More parameters
     * @return string             Form confirm
     */
    public static function draftConfirmation(array $moreParams = []): string
    {
        global $form, $langs, $object;

        return $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->transnoentities('ReOpenObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->transnoentities('ConfirmReOpenObject', $langs->transnoentities('The' . ucfirst($object->element)), $langs->transnoentities('The' . ucfirst($object->element))), 'confirm_setdraft', '', 'yes', 'actionButtonInProgress');
    }

    /**
     * Validate confirmation
     *
     * @param  array  $moreParams More parameters
     * @return string             Form confirm
     */
    public static function validateConfirmation(array $moreParams = []): string
    {
        global $form, $langs, $object;

        return $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->transnoentities('ValidateObject', $langs->transnoentities('The' . ucfirst($object->element))), $moreParams['question'] ?? $langs->transnoentities('ConfirmValidateObject', $langs->transnoentities('The' . ucfirst($object->element)), $langs->transnoentities('The' . ucfirst($object->element))), 'confirm_validate', '', 'yes', 'actionButtonValidate');
    }

    /**
     * Lock confirmation
     *
     * @param  array  $moreParams More parameters
     * @return string             Form confirm
     */
    public static function lockConfirmation(array $moreParams = []): string
    {
        global $form, $langs, $object;

        return $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->transnoentities('LockObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->transnoentities('ConfirmLockObject', $langs->transnoentities('The' . ucfirst($object->element))), 'confirm_lock', '', 'yes', 'actionButtonLock');
    }

    /**
     * Archive confirmation
     *
     * @param  array  $moreParams More parameters
     * @return string             Form confirm
     */
    public static function archiveConfirmation(array $moreParams = []): string
    {
        global $form, $langs, $object;

        return $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&forcebuilddoc=true', $langs->transnoentities('ArchiveObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->transnoentities('ConfirmArchiveObject', $langs->transnoentities('The' . ucfirst($object->element))), 'confirm_archive', '', 'yes', 'actionButtonArchive');
    }

    /**
     * Clone confirmation
     *
     * @param  array  $moreParams More parameters
     * @return string             Form confirm
     */
    public static function cloneConfirmation(array $moreParams = []): string
    {
        global $form, $langs, $object;

        return $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->transnoentities('CloneObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->transnoentities('ConfirmCloneObject', $langs->transnoentities('The' . ucfirst($object->element))), 'confirm_clone', $moreParams['formQuestion'] ?? '', 'yes', 'actionButtonClone', 0, $moreParams['width'] ?? 500);
    }

    /**
     * Delete confirmation
     *
     * @param  array  $moreParams More parameters
     * @return string             Form confirm
     */
    public static function deleteConfirmation(array $moreParams = []): string
    {
        global $form, $langs, $object;

        return $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->transnoentities('DeleteObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->transnoentities('ConfirmDeleteObject', $langs->transnoentities('The' . ucfirst($object->element))), 'confirm_delete', '', 'yes', 'actionButtonDelete');
    }

    /**
     * Delete line confirmation
     *
     * @param  array  $moreParams More parameters
     * @return string             Form confirm
     */
    public static function deleteLineConfirmation(array $moreParams = []): string
    {
        global $form, $langs, $object, $objectLine;

        $lineID = GETPOST('lineid');
        $objectLine->fetch($lineID);

        return $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&lineid=' . $lineID, $langs->transnoentities('DeleteLineObject', $langs->transnoentities('The' . ucfirst($objectLine->element)), $langs->transnoentities('The' . ucfirst($object->element))), $langs->transnoentities('ConfirmDeleteLineObject', $langs->transnoentities('The' . ucfirst($objectLine->element)), $objectLine->ref, $langs->transnoentities('The' . ucfirst($object->element))), 'confirm_delete_line', '', 'yes', 'actionButtonDeleteLine');
    }

    /**
     * Remove file confirmation
     *
     * @param  array  $moreParams More parameters
     * @return string             Form confirm
     */
    public static function removeFileConfirmation(array $moreParams = []): string
    {
        global $conf, $form, $langs, $object;

        return $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&file=' . GETPOST('file') . '&entity=' . $conf->entity, $langs->transnoentities('RemoveFileObject'), $langs->transnoentities('ConfirmRemoveFileObject', GETPOST('file')), 'remove_file', '', 'yes', 'actionButtonRemoveFile');
    }

    /**
     * Action confirmation
     *
     * @param  string $action     Action
     * @param  array  $moreParams More parameters
     * @return string             Form confirm
     */
    public static function actionConfirmation(string $action, array $moreParams = []): string
    {
        global $hookmanager, $object;

        $formConfirm   = '';
        $confirmations = ['draftConfirmation', 'validateConfirmation', 'lockConfirmation', 'archiveConfirmation', 'cloneConfirmation', 'deleteConfirmation', 'removeFileConfirmation'];
        foreach ($confirmations as $method) {
            if (!isset($moreParams['override' . ucfirst($method)])) {
                $formConfirm .= self::$method($moreParams[$method] ?? []);
            }
        }

        $parameters = ['formConfirm' => $formConfirm];
        $resHook    = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action);
        if (empty($resHook)) {
            $formConfirm .= $hookmanager->resPrint;
        } elseif ($resHook > 0) {
            $formConfirm = $hookmanager->resPrint;
        }

        return  $formConfirm;
    }
}
