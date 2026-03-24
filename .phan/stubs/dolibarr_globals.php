<?php

/**
 * Phan global variable stubs for Dolibarr.
 *
 * Phan needs to know the types of globals injected by Dolibarr's main.inc.php.
 * These declarations suppress PhanUndeclaredGlobalVariable on the standard
 * Dolibarr globals used throughout saturne code.
 */

/** @var Conf $conf */
global $conf;

/** @var DoliDB $db */
global $db;

/** @var HookManager $hookmanager */
global $hookmanager;

/** @var Translate $langs */
global $langs;

/** @var Societe $mysoc */
global $mysoc;

/** @var User $user */
global $user;
