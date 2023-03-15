<?php
// Global variables definitions
global $conf, $db, $langs, $moduleName, $moduleNameLowerCase, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/includes/parsedown/Parsedown.php';

require_once __DIR__ . '/../../../../' . $moduleNameLowerCase . '/core/modules/mod' . $moduleName . '.class.php';

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$action = GETPOST('action', 'aZ09');

// Initialize technical objects
$classname = 'mod' . $moduleName;
$modModule = new $classname($db);
$parse     = new Parsedown();

// Security check
$permissiontoread = $user->rights->$moduleNameLowerCase->read;
saturne_check_access($permissiontoread, null, true);

/*
 *  Actions
*/

if ($action == 'closenotice') {
    dolibarr_set_const($db, strtoupper($moduleName) . '_SHOW_PATCH_NOTE', 0, 'integer', 0, '', $conf->entity);
}

/*
 * View
 */

$title   = $langs->trans('ModuleArea', $moduleName);
$helpUrl = 'FR:Module_' . $moduleName;

saturne_header(0, '', $title . ' ' . $modModule->version, $helpUrl);

print load_fiche_titre($title . ' ' . $modModule->version, '', $moduleNameLowerCase . '_color.png@' . $moduleNameLowerCase);

$moduleJustUpdated   = strtoupper($moduleName) . '_JUST_UPDATED';
$moduleVersion       = strtoupper($moduleName) . '_VERSION';
$moduleShowPatchNote = strtoupper($moduleName) . '_SHOW_PATCH_NOTE';

if ($conf->global->$moduleJustUpdated == 1) : ?>
    <div class="wpeo-notice notice-success">
        <div class="notice-content">
            <div class="notice-subtitle"><strong><?php echo $langs->trans('ModuleUpdate'); ?></strong>
                <?php echo $langs->trans('ModuleHasBeenUpdatedTo', $modModule->version) ?>
            </div>
        </div>
    </div>
    <?php dolibarr_set_const($db, $moduleJustUpdated, 0, 'integer', 0, '', $conf->entity);
endif;

if ($conf->global->$moduleVersion != $modModule->version) {
    $modModule->remove();
    $modModule->init();

    dolibarr_set_const($db, $moduleJustUpdated, 1, 'integer', 0, '', $conf->entity);
    dolibarr_set_const($db, $moduleShowPatchNote, 1, 'integer', 0, '', $conf->entity);
}

if ($conf->global->$moduleShowPatchNote > 0) : ?>
    <div class="wpeo-notice notice-info">
        <input type="hidden" name="token" value="<?php echo newToken(); ?>">
        <div class="notice-content">
            <div class="notice-title"><?php echo $langs->trans('ModulePatchNote', $modModule->version); ?>
                <div class="show-patchnote wpeo-button button-square-40 button-blue wpeo-tooltip-event modal-open" aria-label="<?php echo $langs->trans('ShowPatchNote'); ?>">
                    <input hidden class="modal-to-open" value="patch-note">
                    <i class="fas fa-list button-icon"></i>
                </div>
            </div>
        </div>
        <div class="notice-close notice-close-forever wpeo-tooltip-event" aria-label="<?php echo $langs->trans('DontShowPatchNote'); ?>" data-direction="left"><i class="fas fa-times"></i></div>
    </div>

    <div class="wpeo-modal wpeo-modal-patchnote" id="patch-note">
        <div class="modal-container wpeo-modal-event" style="max-width: 1280px; max-height: 1000px">
            <!-- Modal-Header -->
            <div class="modal-header">
                <h2 class="modal-title"><?php echo $langs->trans('ModulePatchNote', $modModule->version);  ?></h2>
                <div class="modal-close"><i class="fas fa-times"></i></div>
            </div>
            <!-- Modal Content-->
            <div class="modal-content">
                <?php $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/repos/' . strtolower($modModule->editor_name) . '/' . $moduleNameLowerCase . '/releases/tags/' . $modModule->version);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_USERAGENT, $moduleName);
                $output = curl_exec($ch);
                curl_close($ch);
                $data = json_decode($output);
                $data->body = preg_replace('/- #\b\d{1,4}\b/', '-', $data->body);
                $data->body = preg_replace('/- #\b\d{1,4}\b/', '-', $data->body);
                $html = $parse->text($data->body);
                print $html;
                ?>
            </div>
            <!-- Modal-Footer -->
            <div class="modal-footer">
                <div class="wpeo-button button-grey button-uppercase modal-close">
                    <span><?php echo $langs->trans('CloseModal'); ?></span>
                </div>
            </div>
        </div>
    </div>
<?php endif;

// End of page
llxFooter();
$db->close();