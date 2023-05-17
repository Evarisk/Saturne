<?php

if ($action == 'save_project' && $permissiontoadd) {
	$projectKey = GETPOST('key');
	// Link to a project
	$object->$projectKey = GETPOST($projectKey, 'int');
	$object->update($user, 1);
}
