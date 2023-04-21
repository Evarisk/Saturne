<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
		$this->version     = '1.1.0';
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

        // Data and type of action are stored into $object and $action
        dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . '. id=' . $object->id);

        require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
        $now = dol_now();
        $actioncomm = new ActionComm($this->db);

        $actioncomm->elementtype = $object->element . '@' . $moduleNameLowerCase;
        $actioncomm->type_code   = 'AC_OTH_AUTO';
        $actioncomm->datep       = $now;
        $actioncomm->fk_element  = $object->id;
        $actioncomm->userownerid = $user->id;
        $actioncomm->percentage  = -1;

        switch ($action) {
            // CERTIFICATE
            case 'SATURNECERTIFICATE_CREATE' :
                $actioncomm->code  = 'AC_SATURNECERTIFICATE_CREATE';
                $actioncomm->label = $langs->trans('ObjectCreateTrigger', $langs->transnoentities(ucfirst($object->element)));
                $actioncomm->create($user);
                break;

            case 'SATURNECERTIFICATE_MODIFY' :
                $actioncomm->code  = 'AC_SATURNECERTIFICATE_MODIFY';
                $actioncomm->label = $langs->trans('ObjectModifyTrigger', $langs->transnoentities(ucfirst($object->element)));
                $actioncomm->create($user);
                break;

            case 'SATURNECERTIFICATE_DELETE' :
                $actioncomm->code  = 'AC_SATURNECERTIFICATE_DELETE';
                $actioncomm->label = $langs->trans('ObjectDeleteTrigger', $langs->transnoentities(ucfirst($object->element)));
                $actioncomm->create($user);
                break;

            case 'SATURNECERTIFICATE_VALIDATE' :
                $actioncomm->code  = 'AC_SATURNECERTIFICATE_VALIDATE';
                $actioncomm->label = $langs->trans('ObjectValidateTrigger', $langs->transnoentities(ucfirst($object->element)));
                $actioncomm->create($user);
                break;

            case 'SATURNECERTIFICATE_UNVALIDATE' :
                $actioncomm->code  = 'AC_SATURNECERTIFICATE_UNVALIDATE';
                $actioncomm->label = $langs->trans('ObjectUnValidateTrigger', $langs->transnoentities(ucfirst($object->element)));
                $actioncomm->create($user);
                break;

            case 'SATURNECERTIFICATE_ARCHIVED' :
                $actioncomm->code  = 'AC_SATURNECERTIFICATE_ARCHIVED';
                $actioncomm->label = $langs->trans('ObjectArchivedTrigger', $langs->transnoentities(ucfirst($object->element)));
                $actioncomm->create($user);
                break;

            case 'SATURNECERTIFICATE_SENTBYMAIL' :
                $actioncomm->code  = 'AC_SATURNECERTIFICATE_SENTBYMAIL';
                $actioncomm->label = $langs->trans('ObjectSentByMailTrigger', $langs->transnoentities(ucfirst($object->element)));
                $actioncomm->create($user);
                break;

            // SIGNATURE
            case 'SATURNESIGNATURE_ADDATTENDANT' :
                $actioncomm->elementtype = $object->object_type . '@' . $moduleNameLowerCase;
                $actioncomm->code        = 'AC_SATURNESIGNATURE_ADDATTENDANT';
                $actioncomm->label       = $langs->transnoentities('AddAttendantTrigger', $langs->trans($object->role) . ' ' . strtoupper($object->lastname) . ' ' . $object->firstname);
                if ($object->element_type == 'socpeople') {
                    $actioncomm->socpeopleassigned = [$object->element_id => $object->element_id];
                }
                $actioncomm->fk_element = $object->fk_object;
                $actioncomm->create($user);
                break;

            case 'SATURNESIGNATURE_SIGNED' :
                $actioncomm->elementtype = $object->object_type . '@' . $moduleNameLowerCase;
                $actioncomm->code        = 'AC_SATURNESIGNATURE_SIGNED';
                $actioncomm->label       = $langs->transnoentities('SignedTrigger', $langs->trans($object->role) . ' ' . strtoupper($object->lastname) . ' ' . $object->firstname);
                if ($object->element_type == 'socpeople') {
                    $actioncomm->socpeopleassigned = [$object->element_id => $object->element_id];
                }
                $actioncomm->fk_element = $object->fk_object;
                $actioncomm->create($user);
                break;

            case 'SATURNESIGNATURE_SIGNED_PUBLIC' :
                $actioncomm->elementtype = $object->object_type . '@' . $moduleNameLowerCase;
                $actioncomm->code        = 'AC_SATURNESIGNATURE_SIGNED_PUBLIC';
                $actioncomm->label       = $langs->transnoentities('SignedTrigger', $langs->trans($object->role) . ' ' . strtoupper($object->lastname) . ' ' . $object->firstname);
                if ($object->element_type == 'socpeople') {
                    $actioncomm->socpeopleassigned = [$object->element_id => $object->element_id];
                }
                $actioncomm->fk_element = $object->fk_object;
                $actioncomm->userownerid = $object->element_id;
                $actioncomm->create($user);
                break;

            case 'SATURNESIGNATURE_PENDING_SIGNATURE' :
                $actioncomm->elementtype = $object->object_type . '@' . $moduleNameLowerCase;
                $actioncomm->code        = 'AC_SATURNESIGNATURE_PENDING_SIGNATURE';
                $actioncomm->label       = $langs->transnoentities('PendingSignatureTrigger', $langs->trans($object->role) . ' ' . strtoupper($object->lastname) . ' ' . $object->firstname);
                if ($object->element_type == 'socpeople') {
                    $actioncomm->socpeopleassigned = [$object->element_id => $object->element_id];
                }
                $actioncomm->fk_element = $object->fk_object;
                $actioncomm->create($user);
                break;

            case 'SATURNESIGNATURE_ATTENDANCE_DELAY' :
                $actioncomm->elementtype = $object->object_type . '@' . $moduleNameLowerCase;
                $actioncomm->code        = 'AC_SATURNESIGNATURE_ATTENDANCE_DELAY';
                $actioncomm->label       = $langs->transnoentities('AttendanceDelayTrigger', $langs->trans($object->role) . ' ' . strtoupper($object->lastname) . ' ' . $object->firstname);
                if ($object->element_type == 'socpeople') {
                    $actioncomm->socpeopleassigned = [$object->element_id => $object->element_id];
                }
                $actioncomm->fk_element = $object->fk_object;
                $actioncomm->create($user);
                break;

            case 'SATURNESIGNATURE_ATTENDANCE_ABSENT' :
                $actioncomm->elementtype = $object->object_type . '@' . $moduleNameLowerCase;
                $actioncomm->code        = 'AC_SATURNESIGNATURE_ATTENDANCE_ABSENT';
                $actioncomm->label       = $langs->transnoentities('AttendanceAbsentTrigger', $langs->trans($object->role) . ' ' . strtoupper($object->lastname) . ' ' . $object->firstname);
                if ($object->element_type == 'socpeople') {
                    $actioncomm->socpeopleassigned = [$object->element_id => $object->element_id];
                }
                $actioncomm->fk_element = $object->fk_object;
                $actioncomm->create($user);
                break;

            case 'SATURNESIGNATURE_DELETED' :
                $actioncomm->elementtype = $object->object_type . '@' . $moduleNameLowerCase;
                $actioncomm->code        = 'AC_SATURNESIGNATURE_DELETED';
                $actioncomm->label       = $langs->transnoentities('DeletedTrigger', $langs->trans($object->role) . ' ' . strtoupper($object->lastname) . ' ' . $object->firstname);
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
