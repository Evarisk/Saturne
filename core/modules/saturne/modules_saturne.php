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
 * or see https://www.gnu.org/
 */

/**
 * \file    core/modules/saturne/modules_saturne.php
 * \ingroup saturne
 * \brief   File that contains parent class for saturne numbering models and saturne documents models.
 */

/**
 *  Parent class to manage saturne numbering rules.
 */
abstract class ModeleNumRefSaturne
{
    /**
     * Dolibarr version of the loaded numbering module ref.
     * @var string
     */
    public string $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'.

    /**
     * @var string Numbering module ref prefix.
     */
    public string $prefix = '';

    /**
     * @var string Numbering module ref suffix.
     */
    public string $suffix = '';

    /**
     * @var string Name.
     */
    public string $name = '';

    /**
     * @var string Error code (or message).
     */
    public string $error = '';

    /**
     * Return if a module can be used or not.
     *
     * @return bool true if module can be used.
     */
    public function isEnabled(): bool
    {
        return true;
    }

    /**
     * Returns the default description of the numbering template.
     *
     * @return string Text with description.
     */
    public function info(): string
    {
        global $langs;

        return $langs->trans('StandardModel', $this->prefix);
    }

    /**
     *    Return last value
     *
     * @param Object $object Object we need next value for
     * @return string                Value if KO, <0 if KO
     * @throws Exception
     */
    public function getLastValue(object $object)
    {
        global $db, $conf;

        // first we get the max value
        $posindice = strlen($this->prefix) + 1;
        $sql       = "SELECT MAX(CAST(SUBSTRING(ref FROM " . $posindice . ") AS SIGNED)) as max";
        $sql      .= ' FROM ' . MAIN_DB_PREFIX . $object->table_element;
        $sql      .= " WHERE ref LIKE '" . $db->escape($this->prefix) . "%'";
        if ($object->ismultientitymanaged == 1) {
            $sql .= " AND entity = " . $conf->entity;
        }
        $sql .= " ORDER BY rowid DESC LIMIT 1";

        $resql = $db->query($sql);

        if ($resql) {
            $obj = $db->fetch_object($resql);
        } else {
            dol_syslog(get_class($this) . '::getLastValue', LOG_DEBUG);
            return -1;
        }
        return $this->prefix . $obj->max;
    }

    /**
     * Checks if the numbers already in the database do not
     * cause conflicts that would prevent this numbering working.
     *
     * @param  object $object Object we need next value for.
     * @return bool           False if conflicted, true if OK.
     */
    public function canBeActivated(object $object): bool
    {
        global $conf, $langs, $db;

        $coyymm = ''; $max = '';

        $posIndice = strlen($this->prefix) + 6;
        $sql = 'SELECT MAX(CAST(SUBSTRING(ref FROM ' . $posIndice . ') AS SIGNED)) as max';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . $object->table_element;
        $sql .= " WHERE ref LIKE '" . $db->escape($this->prefix) . "____-%'";
        if ($object->ismultientitymanaged == 1) {
            $sql .= ' AND entity = ' . $conf->entity;
        }

        $resql = $db->query($sql);
        if ($resql) {
            $row = $db->fetch_row($resql);
            if ($row) {
                $coyymm = substr($row[0], 0, 6); $max = $row[0];
            }
        }
        if ($coyymm && !preg_match('/' . $this->prefix . '[0-9][0-9][0-9][0-9]/i', $coyymm)) {
            $this->error = $langs->trans('ErrorNumRefModel', $max);
            return false;
        }

        return true;
    }

    /**
     * Return next free value.
     *
     * @param  object    $object Object we need next value for.
     * @return string            Value if OK, <0 if KO.
     * @throws Exception
     */
    public function getNextValue(object $object)
    {
        global $db, $conf;

        $sqlLike = '____-%';
        $hideDate = 0;
        $suffixSize = 4;

        // First we get the max value.
        $posIndice = dol_strlen($this->prefix) + dol_strlen($sqlLike);
        $sql = 'SELECT MAX(CAST(SUBSTRING(ref FROM ' . $posIndice . ') AS SIGNED)) as max';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . $object->table_element;
        $sql .= " WHERE ref LIKE '" . $db->escape($this->prefix) . $sqlLike . "'";
        if ($object->ismultientitymanaged == 1) {
            $sql .= ' AND entity = ' . $conf->entity;
        }

        $resql = $db->query($sql);
        if ($resql) {
            $obj = $db->fetch_object($resql);
            if ($obj) {
                $max = intval($obj->max);
            } else {
                $max = 0;
            }
        } else {
            dol_syslog(get_class($this) . '::getNextValue', LOG_DEBUG);
            return -1;
        }

        $date = !empty($object->date_creation) ? $object->date_creation : dol_now();
        $yymm = strftime('%y%m', $date);

        if ($max >= (pow(10, 4) - 1)) {
            $num = $max + 1; // If counter > 9999, we do not format on 4 chars, we take number as it is.
        } else {
            $num = sprintf('%0'. $suffixSize .'s', $max + 1);
        }

        dol_syslog(get_class($this) . '::getNextValue return ' . $this->prefix . $yymm . '-' . $num);
        return $this->prefix . ($hideDate ? '' : $yymm . '-') . $num;
    }

    /**
     * Returns version of numbering module.
     *
     * @return string Value.
     */
    public function getVersion(): string
    {
        global $langs;

        if ($this->version == 'development') {
            return $langs->trans('VersionDevelopment');
        }
        if ($this->version == 'experimental') {
            return $langs->trans('VersionExperimental');
        }
        if ($this->version == 'dolibarr') {
            return DOL_VERSION;
        }
        if ($this->version) {
            return $this->version;
        }
        return $langs->trans('NotAvailable');
    }
}

/**
 *  Parent class to manage custom numbering rules.
 */
abstract class CustomModeleNumRefSaturne extends ModeleNumRefSaturne
{
    /**
     *  Return description of module
     *
     *  @return     string      Texte descripif
     */
    public function info(): string
    {

        global $conf, $langs, $db, $moduleNameLowerCase;

        $langs->load("bills");

        $form = new Form($db);
        $className = get_class($this);

        $modName = str_replace('mod_', '', $className);
        $confName = strtoupper($moduleNameLowerCase . '_' . $modName . '_ADDON');

        $texte = $langs->trans('GenericNumRefModelDesc')."<br>\n";
        $texte .= '<form action="' . $_SERVER['PHP_SELF'] . '?module_name=' . $moduleNameLowerCase . '" method="POST">';
        $texte .= '<input type="hidden" name="token" value="'.newToken().'">';
        $texte .= '<input type="hidden" name="action" value="update_mask">';
        $texte .= '<input type="hidden" name="mask" value="'. $confName .'">';
        $texte .= '<table class="nobordernopadding" width="100%">';

        $tooltip = $langs->trans("SaturneGenericMaskCodes");

        // Parametrage du prefix
        $texte .= '<tr><td>'.$langs->trans("Mask").':</td>';
        $texte .= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat minwidth175" name="addon_value" value="'.$conf->global->$confName.'">', $tooltip, 1, 1).'</td>';

        $texte .= '<td class="left" rowspan="2">&nbsp; <input type="submit" class="button button-edit" name="Button"value="'.$langs->trans("Modify").'"></td>';

        $texte .= '</tr>';

        $texte .= '</table>';
        $texte .= '</form>';

        return $texte;
    }

    /**
     *    Return last value
     *
     * @param Object $object Object we need next value for
     * @return string                Value if KO, <0 if KO
     * @throws Exception
     */
    public function getLastValue(object $object)
    {
        $nextValue = $this->getNextValue($object);

        $nextValueSuffix = preg_replace('/'. $this->prefix .'/', '', $nextValue);
        $nextValueNumber = ltrim($nextValueSuffix, '0');
        $nextValueNumber -= 1;

        $nextValueSuffixLength = dol_strlen($nextValueSuffix);
        $nextValueNumberLength = dol_strlen($nextValueNumber);

        $zeroString = '';
        for ($i = 0; $i < ($nextValueSuffixLength - $nextValueNumberLength); $i++) {
            $zeroString .= '0';
        }

        return $this->prefix . $zeroString . $nextValueNumber;
    }

    /**
     *  Return next value
     *
     *  @return string      			Value if OK, 0 if KO
     */
    public function getNextValue(object $object): string
    {
        global $db, $conf;

        $underscoreString = '';
        for ($i = 1; $i < dol_strlen($this->suffix); $i++) {
            $underscoreString .= '_';
        }
        $sqlLike = $underscoreString . '%';
        $suffixSize = dol_strlen($this->suffix);

        // First we get the max value.
        $posIndice = dol_strlen($this->prefix) + dol_strlen($sqlLike);
        $sql = 'SELECT MAX(CAST(SUBSTRING(ref FROM ' . $posIndice . ') AS SIGNED)) as max';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . $object->table_element;
        $sql .= " WHERE ref LIKE '" . $db->escape($this->prefix) . $sqlLike . "'";
        if ($object->ismultientitymanaged == 1) {
            $sql .= ' AND entity = ' . $conf->entity;
        }

        $resql = $db->query($sql);
        if ($resql) {
            $obj = $db->fetch_object($resql);
            if ($obj) {
                $max = intval($obj->max);
            } else {
                $max = 0;
            }
        } else {
            dol_syslog(get_class($this) . '::getNextValue', LOG_DEBUG);
            return -1;
        }

        $date = !empty($object->date_creation) ? $object->date_creation : dol_now();
        $yymm = strftime('%y%m', $date);

        if ($max >= (pow(10, 4) - 1)) {
            $num = $max + 1; // If counter > 9999, we do not format on 4 chars, we take number as it is.
        } else {
            $num = sprintf('%0'. $suffixSize .'s', $max + 1);
        }

        dol_syslog(get_class($this) . '::getNextValue return ' . $this->prefix . $yymm . '-' . $num);
        return $this->prefix . $num;
    }

    /**
     * Set prefix and suffix for custom value
     *
     * @param string $moduleNameLowerCase Module name
     * @param string $objectType          Object element type
     */
    public function setCustomValue(string $moduleNameLowerCase, string $objectType)
    {
        $refMod = getDolGlobalString(dol_strtoupper($moduleNameLowerCase .  '_' . $objectType .  '_' . $this->name) . '_ADDON');
        if (dol_strlen($refMod)) {
            $refModSplitted = preg_split('/\{/', $refMod);
            if (is_array($refModSplitted) && !empty($refModSplitted)) {
                $suffix       = preg_replace('/}/', '', $refModSplitted[1]);
                $this->prefix = $refModSplitted[0];
                $this->suffix = $suffix;
            }
        }
    }
}

require_once DOL_DOCUMENT_ROOT . '/core/class/commondocgenerator.class.php';

/**
 * Parent class for documents models.
 */
class SaturneDocumentModel extends CommonDocGenerator
{
    /**
     * @var array Minimum version of PHP required by module.
     * e.g.: PHP ≥ 5.5 = array(5, 5)
     */
    public $phpmin = [7, 4];

    /**
     * @var string Dolibarr version of the loaded document.
     */
    public $version = 'dolibarr';

    /**
     * @var string Module.
     */
    public string $module = '';

    /**
     * @var string Document type.
     */
    public string $document_type = '';

    /**
     * Constructor.
     *
     * @param  DoliDb $db                  Database handler.
     * @param  string $moduleNameLowerCase Module name.
     * @param  string $objectDocumentType  Object document type.
     */
    public function __construct(DoliDB $db, string $moduleNameLowerCase = 'saturne', string $objectDocumentType = 'saturne_documents')
    {
        global $langs;

        parent::__construct($db);

        $this->module         = $moduleNameLowerCase;
        $this->document_type  = $objectDocumentType;
        $this->name           = $langs->transnoentities('ODTDefaultTemplateName');
        $this->custom_name    = $langs->transnoentities('CustomODT');
        $this->description    = $langs->transnoentities('DocumentModelOdt');
        $this->custom_info    = false; //@todo remove info method in doc class for better management of custom_info
        $this->scandir        = dol_strtoupper($this->module) . '_' . dol_strtoupper($this->document_type) . '_ADDON_ODT_PATH';        // Name of constant that is used to save list of directories to scan.
        $this->custom_scandir = dol_strtoupper($this->module) . '_' . dol_strtoupper($this->document_type) . '_CUSTOM_ADDON_ODT_PATH';

        // Page size for A4 format.
        $this->type         = 'odt';
        $this->page_largeur = 0;
        $this->page_hauteur = 0;
        $this->format       = [$this->page_largeur, $this->page_hauteur];
        $this->marge_gauche = 0;
        $this->marge_droite = 0;
        $this->marge_haute  = 0;
        $this->marge_basse  = 0;

        $this->option_logo      = 1; // Display logo.
        $this->option_multilang = 1; // Available in several languages.
    }

    /**
     * Return list of active generation modules
     *
     * @param  DoliDB $db                Database handler
     * @param  string $type              Document type
     * @param  int    $maxfilenamelength Max length of value to show
     *
     * @return array|int                 List of templates
     * @throws Exception
     */
    public static function liste_modeles(DoliDB $db, string $type, int $maxfilenamelength = 0)
    {
        require_once __DIR__ . '/../../../lib/saturne_functions.lib.php';
        return saturne_get_list_of_models($db, $type, $maxfilenamelength);
    }

    /**
     * Return description of document model
     *
     * @param  Translate $langs Lang object to use for output
     *
     * @return string           Description
     */
    public function info(Translate $langs): string
    {
        global $conf;

        $confName = (!$this->custom_info ? $this->scandir : $this->custom_scandir);

        $info = $this->description . ' . <br>';
        $info .= '<form action="' . $_SERVER['PHP_SELF'] . '?module_name=' . $this->module . '#' . $this->document_type . '" method="POST"' . ($this->custom_info ? ' enctype="multipart/form-data"' : '') . '>';
        $info .= '<input type="hidden" name="token" value="' . newToken() . '">';
        $info .= '<input type="hidden" name="action" value="setModuleOptions">';
        $info .= '<input type="hidden" name="keyforuploaddir" value="' . $confName . '">';

        // List of directories area
        $infoTitle   = $langs->trans('ListOfDirectories');
        $listOfDir   = explode(',', preg_replace('/[\r\n]+/', ',', trim($conf->global->$confName)));
        $listOfFiles = [];
        foreach ($listOfDir as $key => $tmpDir) {
            $tmpDir = trim($tmpDir);
            $tmpDir = preg_replace('/DOL_DATA_ROOT/', DOL_DATA_ROOT, $tmpDir);
            $tmpDir = preg_replace('/DOL_DOCUMENT_ROOT/', DOL_DOCUMENT_ROOT, $tmpDir);
            if (!$tmpDir) {
                unset($listOfDir[$key]);
                continue;
            }
            if (!is_dir($tmpDir)) {
                $infoTitle .= img_warning($langs->trans('ErrorDirNotFound', $tmpDir), 0);
            }
            else {
                $tmpFiles = dol_dir_list($tmpDir, 'files', 0, '\.(ods|odt)');
                if (count($tmpFiles)) {
                    $listOfFiles = array_merge($listOfFiles, $tmpFiles);
                }
            }
        }

        // Scan directories
        $nbFiles = count($listOfFiles);
        if (!empty($conf->global->$confName)) {
            $info .= $langs->trans('NumberOfModelFilesFound') . ': <b>';
            $info .= count($listOfFiles);
            $info .= '</b>';
        }

        if ($nbFiles) {
            $info .= '<div class="file-generation">';
            foreach ($listOfFiles as $file) {
                // Show list of found files
                $path = DOL_MAIN_URL_ROOT . '/custom/' . $this->module . '/documents/temp/';
                $info .= '<input type="hidden" class="template-name" value="'.  $file['name'] .'">';
                $info .= '<input type="hidden" class="template-type" value="' . $file['level1name'] . '">';
                $info .= '<input type="hidden" class="template-path" value="' . $path . '">';
                $info .= '- ' . $file['name'];
                if (!$this->custom_info) {
                    $info .= ' <a class="wpeo-button button-blue download-template" style="padding: 1px 2px;">' . img_picto('', 'download') . '</a>';
                } else {
                    $info .= ' <a class="wpeo-button button-blue" style="padding: 1px 2px;" href="' . DOL_URL_ROOT . '/document.php?modulepart=ecm&attachment=1&entity=' . $conf->entity . '&file=' . $this->module . '/' . dol_strtolower($this->document_type) . '/' . $file['name'] . '">' . img_picto('', 'fontawesome_fa-download_fas_#ffffff') . '</a>';
                    $info .= ' <a class="wpeo-button button-red" style="padding: 1px 2px;" href="' . $_SERVER['PHP_SELF'] . '?module_name=' . $this->module . '&modulepart=ecm&keyforuploaddir='. $confName . '&action=deletefile&token=' . newToken() . '&file=' . urlencode(basename($file['name'])) . '&type=' . $this->document_type . '">' . img_picto('', 'fontawesome_fa-trash_fas_#ffffff') . '</a>';
                }
                $info .= '<br>';
            }
            $info .= '</div>';
        }

        if ($this->custom_info) {
            // Add input to upload a new template file
            $info .= '<div>' . $langs->trans('UploadNewTemplate') . ' <input type="file" name="userfile">';
            $info .= '<input type="submit" class="button" name="upload" value="' . dol_escape_htmltag($langs->trans('Upload')) . '">';
            $info .= '</div>';
        }

        $info .= '</form>';

        return $info;
    }

    /**
     * Set tmparray vars.
     *
     * @param  array       $tmpArray    Temp array contains all document data.
     * @param  Odf|Segment $listLines   Object to fill with data to convert in ODT Segment.
     * @param  Translate   $outputLangs Lang object to use for output.
     * @param  bool        $segmentVars It's ODT Segment or not.
     *
     * @throws Exception
     */
    public function setTmpArrayVars(array $tmpArray, $listLines, Translate $outputLangs, bool $segmentVars = true)
    {
        unset($tmpArray['object_fields']);
        unset($tmpArray['object_lines']);

        foreach ($tmpArray as $key => $val) {
            try {
                if (preg_match('/photo/', $key) || preg_match('/logo$/', $key)) {
                    // Image.
                    if (file_exists($val)) {
                        $listLines->setImage($key, $val);
                    } else if (dol_strlen($val) > 0){
						if ($key == 'mycompany_logo') {
							$listLines->setVars($key, $outputLangs->transnoentities('ErrorNoSocietyLogo'), true, 'UTF-8');
						} else {
							$listLines->setVars($key, $outputLangs->transnoentities('ErrorFileNotFound'), true, 'UTF-8');
						}
                    } else {
                        $listLines->setVars($key, '', true, 'UTF-8');
                    }
                } elseif (preg_match('/signature/', $key) && is_file($val)) {
                    $imageSize = getimagesize($val);
                    $newWidth  = 200;
                    if ($imageSize[0]) {
                        $ratio     = $newWidth / $imageSize[0];
                        $newHeight = $ratio * $imageSize[1];
                        dol_imageResizeOrCrop($val, 0, $newWidth, $newHeight);
                    }
                    $listLines->setImage($key, $val);
                } elseif (empty($val)) { // Text.
                    $listLines->setVars($key, $outputLangs->trans('NoData'), true, 'UTF-8');
                } else {
                    $listLines->setVars($key, html_entity_decode($val, ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
                }
            } catch (OdfException|SegmentException $e) {
                dol_syslog($e->getMessage());
            }
        }
        if ($segmentVars) {
            $listLines->merge();
        }
    }

    /**
     * Set attendants segment
     *
     * @param  Odf       $odfHandler  Object builder odf library
     * @param  Translate $outputLangs Lang object to use for output
     * @param  array     $moreParam   More param (Object/user/etc)
     *
     * @throws Exception
     */
    public function setAttendantsSegment(Odf $odfHandler, Translate $outputLangs, array $moreParam)
    {
        global $conf, $moduleNameLowerCase, $langs;

        // Get attendants
        $foundTagForLines = 1;
        try {
            $segment   = (!empty($moreParam['segmentName']) ? $moreParam['segmentName'] : 'attendant');
            $listLines = $odfHandler->setSegment($segment);
        } catch (OdfException|OdfExceptionSegmentNotFound $e) {
            // We may arrive here if tags for lines not present into template
            $foundTagForLines = 0;
            $listLines        = '';
            dol_syslog($e->getMessage());
        }

        if ($foundTagForLines) {
            if (!empty($moreParam['object'])) {
                $signatory        = new SaturneSignature($this->db, $this->module, $moreParam['object']->element);
                if (!empty($moreParam['segmentName'])) {
                    $signatoriesArray = $signatory->fetchSignatory($moreParam['segmentName'], $moreParam['object']->id, $moreParam['object']->element);
                } else {
                    $signatoriesArray = $signatory->fetchSignatories($moreParam['object']->id, $moreParam['object']->element);
                }
                if (!empty($signatoriesArray) && is_array($signatoriesArray)) {
                    $nbAttendant = 0;
                    $tempDir     = $conf->$moduleNameLowerCase->multidir_output[$moreParam['object']->entity ?? 1] . '/temp/';
                    if (empty($moreParam['excludeAttendantsRole'])) {
                        $moreParam['excludeAttendantsRole'] = [];
                    }
                    foreach ($signatoriesArray as $objectSignatory) {
                        if (!in_array($objectSignatory->role, $moreParam['excludeAttendantsRole'])) {
                            $tmpArray[$segment . '_number']    = ++$nbAttendant;
                            $tmpArray[$segment . '_lastname']  = strtoupper($objectSignatory->lastname);
                            $tmpArray[$segment . '_firstname'] = dol_strlen($objectSignatory->firstname) > 0 ? ucfirst($objectSignatory->firstname) : '';
                            switch ($objectSignatory->attendance) {
                                case 1:
                                    $attendance = $outputLangs->trans('Delay');
                                    break;
                                case 2:
                                    $attendance = $outputLangs->trans('Absent');
                                    break;
                                default:
                                    $attendance = $outputLangs->transnoentities('Present');
                                    break;
                            }
                            switch ($objectSignatory->element_type) {
                                case 'user':
                                    $user    = new User($this->db);
                                    $societe = new Societe($this->db);
                                    $user->fetch($objectSignatory->element_id);
                                    $tmpArray[$segment . '_job'] = $user->job;
                                    if ($user->fk_soc > 0) {
                                        $societe->fetch($user->fk_soc);
                                        $tmpArray[$segment . '_company'] = $societe->name;
                                    } else {
                                        $tmpArray[$segment . '_company'] = $conf->global->MAIN_INFO_SOCIETE_NOM;
                                    }
                                    break;
                                case 'socpeople':
                                    $contact = new Contact($this->db);
                                    $societe = new Societe($this->db);
                                    $contact->fetch($objectSignatory->element_id);
                                    $tmpArray[$segment . '_job'] = $contact->poste;
                                    if ($contact->fk_soc > 0) {
                                        $societe->fetch($contact->fk_soc);
                                        $tmpArray[$segment . '_company'] = $societe->name;
                                    } else {
                                        $tmpArray[$segment . '_company'] = $conf->global->MAIN_INFO_SOCIETE_NOM;
                                    }
                                    break;
                                default:
                                    $tmpArray[$segment . '_job']     = '';
                                    $tmpArray[$segment . '_company'] = '';
                                    break;
                            }
                            $tmpArray[$segment . '_role']           = $outputLangs->transnoentities($objectSignatory->role);
                            $tmpArray[$segment . '_signature_date'] = dol_print_date($objectSignatory->signature_date, 'dayhour', 'tzuser');
                            $tmpArray[$segment . '_attendance']     = $attendance;
                            if (dol_strlen($objectSignatory->signature) > 0 && $objectSignatory->signature != $langs->transnoentities('FileGenerated')) {
                                $confSignatureName = dol_strtoupper($this->module) . '_SHOW_SIGNATURE_SPECIMEN';
                                if ($moreParam['specimen'] == 0 || ($moreParam['specimen'] == 1 && $conf->global->$confSignatureName == 1)) {
                                    $encodedImage = explode(',', $objectSignatory->signature)[1];
                                    $decodedImage = base64_decode($encodedImage);
                                    file_put_contents($tempDir . 'signature' . $objectSignatory->id . '.png', $decodedImage);
                                    $tmpArray[$segment . '_signature'] = $tempDir . 'signature' . $objectSignatory->id . '.png';
                                } else {
                                    $tmpArray[$segment . '_signature'] = '';
                                }
                            } else {
                                $tmpArray[$segment . '_signature'] = '';
                            }
                            $this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                            dol_delete_file($tempDir . 'signature' . $objectSignatory->id . '.png');
                        }
                    }
                } else {
                    $tmpArray[$segment . '_number']         = '';
                    $tmpArray[$segment . '_lastname']       = '';
                    $tmpArray[$segment . '_firstname']      = '';
                    $tmpArray[$segment . '_job']            = '';
                    $tmpArray[$segment . '_company']        = '';
                    $tmpArray[$segment . '_role']           = '';
                    $tmpArray[$segment . '_signature_date'] = '';
                    $tmpArray[$segment . '_attendance']     = '';
                    $tmpArray[$segment . '_signature']      = '';
                    $this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                }
                $odfHandler->mergeSegment($listLines);
            }
        }
    }

    /**
     * Fill odt tags.
     *
     * @param  Odf       $odfHandler  Object builder odf library.
     * @param  Translate $outputLangs Lang object to use for output.
     * @param  array     $tmpArray    Temp array contains all document data.
     * @param  array     $moreParam   More param (Object/user/etc).
     * @param  bool      $segmentVars It's ODT Segment or not.
     *
     * @throws Exception
     */
    public function fillTags(Odf $odfHandler, Translate $outputLangs, array $tmpArray, array $moreParam, bool $segmentVars = true)
    {
        $this->setTmpArrayVars($tmpArray, $odfHandler, $outputLangs, false);

        if ($segmentVars) {
            $this->fillTagsLines($odfHandler, $outputLangs, $moreParam);
        }
    }

    /**
     * Fill all odt tags for segments lines.
     *
     * @param  Odf       $odfHandler  Object builder odf library.
     * @param  Translate $outputLangs Lang object to use for output.
     * @param  array     $moreParam   More param (Object/user/etc).
     *
     * @return int                    1 if OK, <=0 if KO.
     * @throws Exception
     */
    public function fillTagsLines(Odf $odfHandler, Translate $outputLangs, array $moreParam): int
    {
        // Replace tags of lines.
        try {
            if (!empty($moreParam['multipleAttendantsSegment'])) {
                foreach ($moreParam['multipleAttendantsSegment'] as $multipleAttendantSegment) {
                    $moreParam['segmentName'] = $multipleAttendantSegment;
                    $this->setAttendantsSegment($odfHandler, $outputLangs, $moreParam);
                }
            } else {
                $this->setAttendantsSegment($odfHandler, $outputLangs, $moreParam);
            }
        } catch (OdfException $e) {
            $this->error = $e->getMessage();
            dol_syslog($this->error, LOG_WARNING);
            return -1;
        }
        return 0;
    }

    /**
     * Function to build a document on disk
     *
     * @param  SaturneDocuments $objectDocument  Object source to build document
     * @param  Translate        $outputLangs     Lang object to use for output
     * @param  string           $srcTemplatePath Full path of source filename for generator using a template file
     * @param  int              $hideDetails     Do not show line details
     * @param  int              $hideDesc        Do not show desc
     * @param  int              $hideRef         Do not show ref
     * @param  array            $moreParam       More param (Object/user/etc)
     * @return int                               1 if OK, <=0 if KO
     * @throws Exception
     */
    public function write_file(SaturneDocuments $objectDocument, Translate $outputLangs, string $srcTemplatePath, int $hideDetails = 0, int $hideDesc = 0, int $hideRef = 0, array $moreParam): int
    {
        global $action, $conf, $hookmanager, $langs, $moduleNameLowerCase, $mysoc;

        $object = $moreParam['object'];

        if (empty($srcTemplatePath)) {
            dol_syslog('doc_' . $this->document_type . '_odt::write_file parameter srctemplatepath empty', LOG_WARNING);
            return -1;
        }
        if (empty($moduleNameLowerCase)) {
            $moduleNameLowerCase = $objectDocument->module;
        }

        // Add ODT generation hook
        $hookmanager->initHooks(['odtgeneration']);

        if (!is_object($outputLangs)) {
            $outputLangs = $langs;
        }

        $outputLangs->charset_output = 'UTF-8';

        if ($conf->$moduleNameLowerCase->dir_output) {
            $confRefModName   = dol_strtoupper($this->module) . '_' . dol_strtoupper($this->document_type) . '_ADDON';
            $numberingModules = [(!empty($moreParam['subDir']) ? $moreParam['subDir'] : $moduleNameLowerCase . 'documents/') . $this->document_type => $conf->global->$confRefModName];
            list($refModName) = saturne_require_objects_mod($numberingModules, $moduleNameLowerCase);

            $objectDocumentRef    = $refModName->getNextValue($objectDocument);
            $objectDocument->ref  = $objectDocumentRef;
            $objectDocument->type = $this->document_type;
            $objectDocumentID     = $objectDocument->create($moreParam['user'], true, $object);

            $objectDocument->fetch($objectDocumentID);

            $objectDocumentRef = dol_sanitizeFileName($objectDocument->ref);

            $dir = $conf->$moduleNameLowerCase->multidir_output[$object->entity ?? 1] . '/' . $this->document_type . (dol_strlen($object->ref) > 0 ? '/' . $object->ref : '');
            if ($moreParam['specimen'] == 1 && $moreParam['zone'] == 'public') {
                $dir .= '/public_specimen';
            }

            if (!file_exists($dir)) {
                if (dol_mkdir($dir) < 0) {
                    $this->error = $langs->transnoentities('ErrorCanNotCreateDir', $dir);
                    return -1;
                }
            }

            if (file_exists($dir)) {
                $newFile     = basename($srcTemplatePath);
                $newFileTmp  = preg_replace('/\.od([ts])/i', '', $newFile);
                $newFileTmp  = preg_replace('/template_/i', '', $newFileTmp);
                $societyName = preg_replace('/\./', '_', $conf->global->MAIN_INFO_SOCIETE_NOM);

                $date       = dol_print_date(dol_now(), 'dayxcard');
                $newFileTmp = $date . (dol_strlen($object->ref) > 0 ? '_' . $object->ref : '') . '_' . $objectDocumentRef . ($moreParam['hideTemplateName'] ? '' : '_' . $outputLangs->transnoentities($newFileTmp)) . '_' . (!empty($moreParam['documentName'])   ? $moreParam['documentName'] : '') . $societyName . (!empty($moreParam['additionalName']) ? $moreParam['additionalName'] : '');

                if ($moreParam['specimen'] == 1) {
                    $newFileTmp .= '_specimen';
                }
                $newFileTmp = str_replace(' ', '_', $newFileTmp);
                $newFileTmp = dol_sanitizeFileName($newFileTmp);

                // Get extension (ods or odt)
                $newFileFormat = substr($newFile, strrpos($newFile, '.') + 1);
                $fileName      = $newFileTmp . '.' . $newFileFormat;
                $file          = $dir . '/' . $fileName;

                $objectDocument->setValueFrom('last_main_doc', $fileName, '', null, '', '', $moreParam['user'], '', '');
                if (!empty($objectDocument->error)) {
                    $objectDocument->errors[] = $objectDocument->ref;
                    setEventMessages($objectDocument->error, $objectDocument->errors, 'errors');
                    return -1;
                }

                dol_mkdir($conf->$moduleNameLowerCase->dir_temp);

                if (!is_writable($conf->$moduleNameLowerCase->dir_temp)) {
                    $this->error = 'Failed to write in temp directory ' . $conf->$moduleNameLowerCase->dir_temp;
                    dol_syslog('Error in write_file: ' . $this->error, LOG_ERR);
                    return -1;
                }

                // Make substitution
                $substitutionArray = [];
                complete_substitutions_array($substitutionArray, $outputLangs, $object);
                // Call the ODTSubstitution hook
                $parameters = ['file' => $file, 'object' => $object, 'outputlangs' => $outputLangs, 'substitutionarray' => &$substitutionArray];
                $hookmanager->executeHooks('ODTSubstitution', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

                // Open and load template
                require_once ODTPHP_PATH . 'odf.php';
                try {
                    $odfHandler = new odf(
                        $srcTemplatePath,
                        [
                            'PATH_TO_TMP'     => $conf->$moduleNameLowerCase->dir_temp,
                            'ZIP_PROXY'       => 'PclZipProxy', // PhpZipProxy or PclZipProxy. Got "bad compression method" error when using PhpZipProxy
                            'DELIMITER_LEFT'  => '{',
                            'DELIMITER_RIGHT' => '}'
                        ]
                    );
                } catch (Exception $e) {
                    $this->error = $e->getMessage();
                    dol_syslog($e->getMessage());
                    return -1;
                }

                // Define substitution array
                $substitutionArray          = getCommonSubstitutionArray($outputLangs, 0, null, $object);
                $arraySoc                   = $this->get_substitutionarray_mysoc($mysoc, $outputLangs);
                $arraySoc['mycompany_logo'] = preg_replace('/_small/', '_mini', $arraySoc['mycompany_logo']);

                $tmpArray = array_merge($substitutionArray, $arraySoc, $moreParam['tmparray']);
                if (isModEnabled('multicompany')) {
                    $tmpArray['entity'] = $conf->entity;
                } else {
                    $tmpArray['entity'] = '';

                }
                $tmpArray['object_document_ref'] = $objectDocumentRef;
                complete_substitutions_array($tmpArray, $outputLangs, $object);

                $this->fillTags($odfHandler, $outputLangs, $tmpArray, $moreParam);

                // Replace labels translated
                $tmpArray = $outputLangs->get_translations_for_substitutions();

                // Call the beforeODTSave hook
                $parameters = ['odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputLangs, 'substitutionarray' => &$tmpArray];
                $hookmanager->executeHooks('beforeODTSave', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

                $fileInfos   = pathinfo($fileName);
                $pdfName     = $fileInfos['filename'] . '.pdf';
                $confPdfName = dol_strtoupper($this->module) . '_AUTOMATIC_PDF_GENERATION';

                // Write new file
                if (!empty($conf->global->MAIN_ODT_AS_PDF) && $conf->global->$confPdfName > 0) {
                    try {
                        $odfHandler->exportAsAttachedPDF($file);

                        $documentUrl = DOL_URL_ROOT . '/document.php';
                        setEventMessages($langs->transnoentities('FileGenerated') . ' - ' . '<a href=' . $documentUrl . '?modulepart=' . $moduleNameLowerCase . '&file=' . urlencode($this->document_type . '/' . $object->ref . '/' . $pdfName) . '&entity=' . $conf->entity . '"' . '>' . $pdfName  . '</a>', []);
                    } catch (Exception $e) {
                        $this->error = $e->getMessage();
                        dol_syslog($e->getMessage());
                        setEventMessages($langs->transnoentities('FileCouldNotBeGeneratedInPDF') . '<br>' . $langs->transnoentities('CheckDocumentationToEnablePDFGeneration'), [], 'errors');
                    }
                } else {
                    try {
                        $odfHandler->saveToDisk($file);
                    } catch (Exception $e) {
                        $this->error = $e->getMessage();
                        dol_syslog($e->getMessage());
                        return -1;
                    }
                }

                $parameters = ['odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputLangs, 'substitutionarray' => &$tmpArray];
                $hookmanager->executeHooks('afterODTCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

                if (!empty($conf->global->MAIN_UMASK)) {
                    @chmod($file, octdec($conf->global->MAIN_UMASK));
                }

                $tempDir   = $conf->$moduleNameLowerCase->multidir_output[$object->entity ?? 1] . '/temp/';
                $fileArray = dol_dir_list($tempDir, 'files');
                if (!empty($fileArray)) {
                    foreach ($fileArray as $file) {
                        unlink($file['fullname']);
                    }
                }

                $odfHandler = null; // Destroy object

                return 1; // Success
            } else {
                $this->error = $langs->transnoentities('ErrorCanNotCreateDir', $dir);
                return -1;
            }
        }

        return -1;
    }
}
