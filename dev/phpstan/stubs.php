<?php

/* Copyright (C) 2022-2026 EVARISK <technique@evarisk.com>
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
 * Minimal Dolibarr class stubs for PHPStan isolated mode.
 *
 * Compatible with PHP 7.4+ — no `mixed` native type hints.
 * Used only when Dolibarr core is not available.
 * Do NOT add real logic here — stubs only.
 */

if (!class_exists('DoliDB')) {
    class DoliDB
    {
        /** @var string */
        public $type = '';

        /**
         * @param mixed $value
         * @return string
         */
        public function escape($value)
        {
            return '';
        }

        /**
         * @param string   $sql
         * @param mixed[]  $params
         * @return resource|bool|null
         */
        public function query($sql, $params = [])
        {
            return null;
        }

        /**
         * @param resource|bool|null $result
         * @return object|null
         */
        public function fetch_object($result)
        {
            return null;
        }

        /**
         * @param resource|bool|null $result
         * @return int
         */
        public function num_rows($result)
        {
            return 0;
        }

        /** @return string */
        public function lasterror()
        {
            return '';
        }

        /** @return string */
        public function lasterrno()
        {
            return '';
        }

        /** @return bool */
        public function close()
        {
            return true;
        }
    }
}

if (!class_exists('Conf')) {
    class Conf
    {
        /** @var stdClass */
        public $global;

        /** @var int */
        public $entity = 1;

        public function __construct()
        {
            $this->global = new stdClass();
        }

        /**
         * @param string $name
         * @return mixed
         */
        public function __get($name)
        {
            return null;
        }
    }
}

if (!class_exists('Translate')) {
    class Translate
    {
        /** @var string */
        public $defaultlang = 'en_EN';

        /** @return int */
        public function load(string $domain)
        {
            return 1;
        }

        /**
         * @param mixed ...$args
         * @return string
         */
        public function trans(string $key, ...$args)
        {
            return $key;
        }

        /**
         * @param mixed ...$args
         * @return string
         */
        public function transnoentities(string $key, ...$args)
        {
            return $key;
        }

        /** @return string */
        public function transnoentitiesnoconv(string $key): string
        {
            return $key;
        }

        /** @return void */
        public function setDefaultLang(string $lang): void
        {
        }
    }
}

if (!class_exists('User')) {
    class User
    {
        /** @var int */
        public $id = 0;

        /** @var stdClass */
        public $rights;

        /** @var mixed[] */
        public $users = [];

        /** @var string[] */
        public $errors = [];

        /** @var int */
        public $socid = 0;

        public function __construct()
        {
            $this->rights = new stdClass();
        }

        /** @return bool */
        public function hasRight(string $module, string $permlevel, string $permright = '')
        {
            return false;
        }

        /** @return int */
        public function fetchAll()
        {
            return 1;
        }
    }
}

if (!class_exists('HookManager')) {
    class HookManager
    {
        /** @var string */
        public $resPrint = '';

        /** @var string */
        public $error = '';

        /** @var string[] */
        public $errors = [];

        /** @var mixed[] */
        public $resArray = [];

        /** @var string[] */
        public $hooks_modules = [];

        /** @param string[] $hooks */
        public function initHooks(array $hooks): void
        {
        }

        /**
         * @param string      $hookname
         * @param mixed[]     $parameters
         * @param mixed       $object
         * @param string      $action
         * @return int
         */
        public function executeHooks(string $hookname, array $parameters, &$object = null, &$action = '')
        {
            return 0;
        }
    }
}

if (!class_exists('CommonObject')) {
    class CommonObject
    {
        /** @var int */
        public $id = 0;

        /** @var string */
        public $ref = '';

        /** @var string */
        public $element = '';

        /** @var string */
        public $table_element = '';

        /** @var string */
        public $module = '';

        /** @var int */
        public $statut = 0;

        /** @var string */
        public $status = '';

        /** @var DoliDB */
        protected $db;

        /** @var string */
        public $error = '';

        /** @var string[] */
        public $errors = [];

        /** @var mixed[] */
        public $lines = [];

        /** @var mixed[] */
        public $array_options = [];

        public function __construct(DoliDB $db)
        {
            $this->db = $db;
        }

        /** @return int */
        public function fetch(int $id, string $ref = '')
        {
            return 1;
        }

        /** @return int */
        public function create(User $user)
        {
            return 1;
        }

        /** @return int */
        public function update(User $user)
        {
            return 1;
        }

        /** @return int */
        public function delete(User $user)
        {
            return 1;
        }

        /**
         * @param int    $withpicto
         * @param string $option
         * @param int    $notooltip
         * @param string $morecss
         * @param int    $save_lastsearch_value
         * @return string
         */
        public function getNomUrl(int $withpicto = 0, string $option = '', int $notooltip = 0, string $morecss = '', int $save_lastsearch_value = -1)
        {
            return '';
        }

        /** @return string */
        public function getLibStatut(int $mode = 0)
        {
            return '';
        }

        /** @return string */
        public function LibStatut(int $status, int $mode = 0)
        {
            return '';
        }
    }
}

if (!class_exists('Form')) {
    class Form
    {
        public function __construct(DoliDB $db)
        {
        }

        /**
         * @param mixed[]  $array
         * @param mixed    $id
         * @return string
         */
        public static function selectarray(string $htmlname, array $array, $id = 0, int $show_empty = 0, int $key_in_label = 0)
        {
            return '';
        }

        /**
         * @param mixed[] $arrayofvalues
         * @return string
         */
        public function buttonsSaveCancel(string $save = 'Save', string $cancel = 'Cancel', array $arrayofvalues = [])
        {
            return '';
        }

        /**
         * @param mixed $value
         * @param mixed $object
         * @param mixed $perm
         * @return string
         */
        public function editfieldkey(string $label, string $fieldname, $value, $object, $perm): string
        {
            return '';
        }

        /**
         * @param mixed $value
         * @param mixed $object
         * @param mixed $perm
         * @return string
         */
        public function editfieldval(string $label, string $fieldname, $value, $object, $perm): string
        {
            return '';
        }

        /** @return string */
        public function textwithpicto(string $text, string $help): string
        {
            return $text;
        }
    }
}

if (!class_exists('Societe')) {
    class Societe extends CommonObject
    {
        /** @var string */
        public $name = '';
    }
}

if (!class_exists('Task')) {
    class Task extends CommonObject
    {
    }
}

if (!class_exists('Expedition')) {
    class Expedition extends CommonObject
    {
    }
}

if (!class_exists('Reception')) {
    class Reception extends CommonObject
    {
    }
}

if (!class_exists('SupplierProposal')) {
    class SupplierProposal extends CommonObject
    {
    }
}

if (!class_exists('ExtraFields')) {
    class ExtraFields
    {
        public function __construct(DoliDB $db)
        {
        }

        /** @return int */
        public function fetch_name_optionals_label(string $table, bool $forceload = false)
        {
            return 1;
        }
    }
}

if (!class_exists('Categorie')) {
    class Categorie extends CommonObject
    {
    }
}

if (!class_exists('FormProjets')) {
    class FormProjets
    {
        public function __construct(DoliDB $db)
        {
        }
    }
}

if (!class_exists('Link')) {
    class Link extends CommonObject
    {
    }
}

if (!class_exists('DolibarrModules')) {
    class DolibarrModules
    {
        /** @var int */
        public $numero = 0;

        /** @var string */
        public $version = '';

        /** @var string */
        public $editor_name = '';

        public function __construct(DoliDB $db)
        {
        }

        /** @return int */
        public function init(string $options = '')
        {
            return 1;
        }

        /** @return int */
        public function remove(string $options = '')
        {
            return 1;
        }

        /** @return string */
        public function getDescLong()
        {
            return '';
        }
    }
}

if (!class_exists('DolibarrTriggers')) {
    class DolibarrTriggers
    {
        /** @var string */
        public $version = '';

        /** @var string */
        public $picto = '';

        public function __construct(DoliDB $db)
        {
        }

        /** @return string */
        public function getName()
        {
            return '';
        }

        /** @return string */
        public function getDesc()
        {
            return '';
        }

        /**
         * @param string    $action
         * @param CommonObject $object
         * @param User      $user
         * @param Translate $langs
         * @param Conf      $conf
         * @return int
         */
        public function runTrigger(string $action, $object, User $user, Translate $langs, Conf $conf)
        {
            return 0;
        }
    }
}

if (!class_exists('Contact')) {
    class Contact extends CommonObject
    {
        /** @var string */
        public $lastname = '';

        /** @var string */
        public $firstname = '';

        /** @var string */
        public $email = '';

        /** @var int */
        public $fk_soc = 0;

        public function __construct(DoliDB $db)
        {
            parent::__construct($db);
        }

        /** @return int */
        public function fetch($id, string $ref = '')
        {
            return 1;
        }

        /** @return string */
        public function getFullName(Translate $langs): string
        {
            return $this->firstname . ' ' . $this->lastname;
        }

        /** @return string */
        public function getCivilityLabel(): string
        {
            return '';
        }

        /**
         * @param mixed[] $filters
         * @return mixed[]|int
         */
        public function liste_contact($filters = [])
        {
            return [];
        }
    }
}

if (!class_exists('Project')) {
    class Project extends CommonObject
    {
        public function __construct(DoliDB $db)
        {
            parent::__construct($db);
        }

        /** @return int */
        public function fetch($id, string $ref = '')
        {
            return 1;
        }

        /**
         * @param int $withpicto
         * @return string
         */
        public function getNomUrl(int $withpicto = 0, string $option = '', int $notooltip = 0, string $morecss = '', int $save_lastsearch_value = -1)
        {
            return '';
        }
    }
}

if (!function_exists('llxFooter')) {
    function llxFooter(): void
    {
    }
}

if (!class_exists('Parsedown')) {
    class Parsedown
    {
        /** @return string */
        public function text(string $text): string
        {
            return $text;
        }
    }
}

if (!class_exists('OdfException')) {
    class OdfException extends Exception {}
}

if (!class_exists('OdfExceptionSegmentNotFound')) {
    class OdfExceptionSegmentNotFound extends Exception {}
}

if (!class_exists('SegmentException')) {
    class SegmentException extends Exception {}
}

if (!class_exists('Segment')) {
    class Segment
    {
        /**
         * @param mixed $value
         * @return void
         */
        public function setVars(string $key, $value, bool $encode = true, string $charset = 'UTF-8'): void
        {
        }

        /** @return void */
        public function merge(): void
        {
        }
    }
}

if (!class_exists('Odf')) {
    class Odf
    {
        /** @param mixed[] $options */
        public function __construct(string $filename, array $options = [])
        {
        }

        /**
         * @param mixed $value
         * @return void
         */
        public function setVars(string $key, $value, bool $encode = true, string $charset = 'UTF-8'): void
        {
        }

        /** @return void */
        public function setImage(string $key, string $value): void
        {
        }

        /** @return void */
        public function merge(): void
        {
        }

        /** @return Segment */
        public function setSegment(string $segment): Segment
        {
            return new Segment();
        }

        /** @return void */
        public function mergeSegment(Segment $segment): void
        {
        }

        /** @return void */
        public function exportAsAttachedPDF(string $file): void
        {
        }

        /** @return void */
        public function saveToDisk(string $file): void
        {
        }

        /** @return void */
        public function exportAsAttachedFile(): void
        {
        }
    }
}

if (!class_exists('TCPDF2DBarcode')) {
    class TCPDF2DBarcode
    {
        public function __construct(string $code, string $type)
        {
        }

        /**
         * @param int[]  $color
         * @return string|false
         */
        public function getBarcodePngData(int $w = 3, int $h = 3, array $color = [0, 0, 0])
        {
            return false;
        }
    }
}

// Global variables expected by Dolibarr files.
global $conf, $db, $hookmanager, $langs, $mysoc, $user;

if (!isset($db)) {
    $db = new DoliDB();
}
if (!isset($conf)) {
    $conf = new Conf();
}
if (!isset($langs)) {
    $langs = new Translate();
}
if (!isset($user)) {
    $user = new User();
}
if (!isset($hookmanager)) {
    $hookmanager = new HookManager();
}
if (!isset($mysoc)) {
    $mysoc = new Societe(new DoliDB());
}
