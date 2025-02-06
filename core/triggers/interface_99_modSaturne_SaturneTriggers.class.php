<?php
/* Copyright (C) 2021-2025 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    core/triggers/interface_99_modSaturne_SaturneTriggers.class.php
 * \ingroup saturne
 * \brief   Saturne trigger.
 */

require_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';

// Load Saturne librairies.
require_once __DIR__ . '/../../lib/saturne_functions.lib.php';

/**
 *  Class of triggers for Saturne module
 */
class InterfaceSaturneTriggers extends DolibarrTriggers
{
	/**
	 * @var DoliDB Database handler
	 */
	protected $db;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;

		$this->name        = preg_replace('/^Interface/i', '', get_class($this));
		$this->family      = 'demo';
		$this->description = 'Saturne triggers.';
		$this->version     = '1.7.0';
		$this->picto       = 'saturne@saturne';
	}

    /**
     * Trigger name
     *
     * @return string Name of trigger file
     */
    public function getName(): string
    {
        return parent::getName();
    }

    /**
     * Trigger description
     *
     * @return string Description of trigger file
     */
    public function getDesc(): string
    {
        return parent::getDesc();
    }

	/**
	 * Function called when a Dolibarr business event is done.
	 * All functions "runTrigger" are triggered if file
	 * is inside directory core/triggers
	 *
	 * @param  string       $action Event action code
	 * @param  CommonObject $object Object
	 * @param  User         $user   Object user
	 * @param  Translate    $langs  Object langs
	 * @param  Conf         $conf   Object conf
	 * @return int                  0 < if KO, 0 if no triggered ran, >0 if OK
	 * @throws Exception
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf): int
	{
        global $moduleNameLowerCase;

        if (!isModEnabled('saturne')) {
            return 0; // If module is not enabled, we do nothing
        }

        saturne_load_langs();

        // Data and type of action are stored into $object and $action
        dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . '. id=' . $object->id);

        require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
        $now = dol_now();
        $actioncomm = new ActionComm($this->db);

        $actioncomm->elementtype = $object->element . '@' . $moduleNameLowerCase;
        $actioncomm->type_code   = 'AC_OTH_AUTO';
        $actioncomm->code        = 'AC_' . $action;
        $actioncomm->datep       = $now;
        $actioncomm->fk_element  = $object->id;
        $actioncomm->userownerid = $user->id;
        $actioncomm->percentage  = -1;

        switch ($action) {
            // CERTIFICATE
            case 'SATURNE_CERTIFICATE_CREATE' :
                $actioncomm->label = $langs->transnoentities('ObjectCreateTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->create($user);
                break;

            case 'SATURNE_CERTIFICATE_MODIFY' :
                $actioncomm->label = $langs->transnoentities('ObjectModifyTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->create($user);
                break;

            case 'SATURNE_CERTIFICATE_DELETE' :
                $actioncomm->label = $langs->transnoentities('ObjectDeleteTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->create($user);
                break;

            case 'SATURNE_CERTIFICATE_VALIDATE' :
                $actioncomm->label = $langs->transnoentities('ObjectValidateTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->create($user);
                break;

            case 'SATURNE_CERTIFICATE_UNVALIDATE' :
                $actioncomm->label = $langs->transnoentities('ObjectUnValidateTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->create($user);
                break;

            case 'SATURNE_CERTIFICATE_ARCHIVE' :
                $actioncomm->label = $langs->transnoentities('ObjectArchivedTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->create($user);
                break;

            case 'SATURNE_CERTIFICATE_EXPIRE' :
                $actioncomm->label = $langs->transnoentities('ObjectExpiredTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->create($user);
                break;

            case 'SATURNE_CERTIFICATE_SENTBYMAIL' :
                $actioncomm->label = $langs->transnoentities('ObjectSentByMailTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->create($user);
                break;

            // SIGNATURE
            case 'SATURNE_SIGNATURE_ADDATTENDANT' :
                $actioncomm->elementtype = $object->object_type . '@' . $moduleNameLowerCase;
                $actioncomm->label       = $langs->transnoentities('AddAttendantTrigger', $langs->transnoentities($object->role) . ' ' . strtoupper($object->lastname) . ' ' . $object->firstname);
                if ($object->element_type == 'socpeople') {
                    $actioncomm->socpeopleassigned = [$object->element_id => $object->element_id];
                }
                $actioncomm->fk_element = $object->fk_object;
                $actioncomm->create($user);
                break;

            case 'SATURNE_SIGNATURE_SIGN' :
                $actioncomm->elementtype = $object->object_type . '@' . $moduleNameLowerCase;
                $actioncomm->label       = $langs->transnoentities('SignedTrigger', $langs->transnoentities($object->role) . ' ' . strtoupper($object->lastname) . ' ' . $object->firstname);
                if ($object->element_type == 'socpeople') {
                    $actioncomm->socpeopleassigned = [$object->element_id => $object->element_id];
                }
                $actioncomm->fk_element = $object->fk_object;
                $actioncomm->create($user);
                break;

            case 'SATURNE_SIGNATURE_SIGN_PUBLIC' :
                $actioncomm->elementtype = $object->object_type . '@' . $moduleNameLowerCase;
                $actioncomm->label       = $langs->transnoentities('SignedTrigger', $langs->transnoentities($object->role) . ' ' . strtoupper($object->lastname) . ' ' . $object->firstname);
                if ($object->element_type == 'socpeople') {
                    $actioncomm->socpeopleassigned = [$object->element_id => $object->element_id];
                }
                $actioncomm->fk_element = $object->fk_object;

                // The client can set HTTP header information (like $_SERVER['HTTP_CLIENT_IP'] ...) to any arbitrary value it wants. As such it's far more reliable to use $_SERVER['REMOTE_ADDR'], as this cannot be set by the user.
                $actioncomm->note_private .= (isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR']) ? $langs->transnoentities('IPAddress') . ' : ' . $_SERVER['REMOTE_ADDR'] . '<br>' : $langs->transnoentities('NoData'));
                $actioncomm->userownerid  = 0;
                $actioncomm->type_code    = 'AC_PUBLIC';
                $actioncomm->create($user);
                break;

            case 'SATURNE_SIGNATURE_PENDING_SIGNATURE' :
                $actioncomm->elementtype = $object->object_type . '@' . $moduleNameLowerCase;
                $actioncomm->label       = $langs->transnoentities('PendingSignatureTrigger', $langs->transnoentities($object->role) . ' ' . strtoupper($object->lastname) . ' ' . $object->firstname);
                if ($object->element_type == 'socpeople') {
                    $actioncomm->socpeopleassigned = [$object->element_id => $object->element_id];
                    $actioncomm->fk_contact        = $object->element_id;
                } else {
                    $actioncomm->fk_user_action = $object->element_id;
                }
                $actioncomm->fk_element = $object->fk_object;
                $actioncomm->create($user);
                break;

            case 'SATURNE_SIGNATURE_ATTENDANCE_DELAY' :
                $actioncomm->elementtype = $object->object_type . '@' . $moduleNameLowerCase;
                $actioncomm->label       = $langs->transnoentities('AttendanceDelayTrigger', $langs->transnoentities($object->role) . ' ' . strtoupper($object->lastname) . ' ' . $object->firstname);
                if ($object->element_type == 'socpeople') {
                    $actioncomm->socpeopleassigned = [$object->element_id => $object->element_id];
                }
                $actioncomm->fk_element = $object->fk_object;
                $actioncomm->create($user);
                break;

            case 'SATURNE_SIGNATURE_ATTENDANCE_ABSENT' :
                $actioncomm->elementtype = $object->object_type . '@' . $moduleNameLowerCase;
                $actioncomm->label       = $langs->transnoentities('AttendanceAbsentTrigger', $langs->transnoentities($object->role) . ' ' . strtoupper($object->lastname) . ' ' . $object->firstname);
                if ($object->element_type == 'socpeople') {
                    $actioncomm->socpeopleassigned = [$object->element_id => $object->element_id];
                }
                $actioncomm->fk_element = $object->fk_object;
                $actioncomm->create($user);
                break;

            case 'SATURNE_SIGNATURE_DELETE' :
                $actioncomm->elementtype = $object->object_type . '@' . $moduleNameLowerCase;
                $actioncomm->label       = $langs->transnoentities('DeletedTrigger', $langs->transnoentities($object->role) . ' ' . strtoupper($object->lastname) . ' ' . $object->firstname);
                if ($object->element_type == 'socpeople') {
                    $actioncomm->socpeopleassigned = [$object->element_id => $object->element_id];
                }
                $actioncomm->fk_element = $object->fk_object;
                $actioncomm->create($user);
                break;
		}
		return 0;
	}
}
