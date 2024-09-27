<?php
/* Copyright (C) 2023-2024 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/admin/object_numbering_module_view.tpl.php
 * \ingroup saturne
 * \brief   Template page for object numbering module view
 */

/**
 * The following vars must be defined :
 * Global   : $conf, $db, $langs
 * Objects  : $object
 * Variable : $documentParentType (optional), $documentPath (optional), $moduleName, $moduleNameLowerCase, $objectModSubdir (optional)
 */

$varsToChecks = [
    'conf'                => ['isset' => true,  'not_empty' => true, 'type' => 'object'],
    'db'                  => ['isset' => true,  'not_empty' => true, 'type' => 'object'],
    'langs'               => ['isset' => true,  'not_empty' => true, 'type' => 'object'],
    'object'              => ['isset' => true,  'not_empty' => true, 'type' => 'object'],
    'documentParentType'  => ['isset' => false, 'not_empty' => true, 'type' => 'string'],
    'documentPath'        => ['isset' => false, 'not_empty' => true, 'type' => 'bool'],
    'moduleName'          => ['isset' => true,  'not_empty' => true, 'type' => 'string'],
    'moduleNameLowerCase' => ['isset' => true,  'not_empty' => true, 'type' => 'string'],
    'objectModSubdir'     => ['isset' => false, 'not_empty' => true, 'type' => 'string']
];

require_once __DIR__ . '/../../utils/saturne_check_variable.php';

//$numberingModuleLabel = str_contains($object->element, 'det') ? 'NumberingModuleDet' : 'NumberingModule';
print load_fiche_titre($langs->trans('NumberingModule'), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td class="nowrap">' . $langs->trans('NextValue') . '</td>';
print '<td class="center">' . $langs->trans('Status') . '</td>';
print '</tr>';

clearstatcache();

if (empty($documentPath)) {
    $objectType = $object->element;
    $path = '/custom/' . $moduleNameLowerCase . '/core/modules/' . $moduleNameLowerCase . '/' . (!empty($objectModSubdir) ? $objectModSubdir . '/' : '') . $objectType . '/';
} else {
    $objectType = $documentParentType;
    $path = '/custom/' . $moduleNameLowerCase . '/core/modules/' . $moduleNameLowerCase . '/' . $moduleNameLowerCase . 'documents/' . $objectType . '/';
}

$dir = dol_buildpath($path);
if (is_dir($dir)) {
    $handle = opendir($dir);
    if (is_resource($handle)) {
        while (($file = readdir($handle)) !== false) {
            $filelist[] = $file;
        }
        closedir($handle);
        arsort($filelist);
        if (is_array($filelist) && !empty($filelist)) {
            foreach ($filelist as $file) {
                if (preg_match('/mod_/', $file) && preg_match('/' . $objectType . '/i', $file)) {
                    if (file_exists($dir . '/' . $file)) {
                        $classname = substr($file, 0, dol_strlen($file) - 4);

                        require_once $dir . '/' . $file;
                        $module = new $classname($db);

                        if ($module->isEnabled()) {
                            print '<tr class="oddeven"><td>';
                            print $langs->trans($module->name);
                            print '</td><td>';
                            print $module->info();
                            print '</td>';

                            // Show next value.
                            print '<td class="nowrap">';
                            $tmp = $module->getNextValue($object);
                            if (preg_match('/^Error/', $tmp)) {
                                print '<div class="error">' . $langs->trans($tmp) . '</div>';
                            } elseif ($tmp == 'NotConfigured') {
                                print $langs->trans($tmp);
                            } else {
                                print $tmp;
                            }
                            print '</td>';

                            print '<td class="center">';
                            $confName = dol_strtoupper($moduleName . '_' . $objectType) . '_ADDON';
                            if (getDolGlobalString($confName) . '.php' == $file) {
                                print img_picto($langs->trans('Activated'), 'switch_on');
                            } else {
                                print '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?action=set_mod&value=' . preg_replace('/\.php$/', '', $file) . '&module_name=' . $moduleName . '&object_type=' . $objectType . '&token=' . newToken() . '">' . img_picto($langs->trans('Disabled'), 'switch_off') . '</a>';
                            }
                            print '</td></tr>';
                        }
                    }
                }
            }
        }
    }
}
print '</table>';
