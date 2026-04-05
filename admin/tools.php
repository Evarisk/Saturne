<?php
/* Copyright (C) 2024-2026 EVARISK <technique@evarisk.com>
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
 * \file    admin/tools.php
 * \ingroup saturne
 * \brief   Saturne tools page
 */

// Load Saturne environment
if (file_exists('../saturne.main.inc.php')) {
    require_once __DIR__ . '/../saturne.main.inc.php';
} elseif (file_exists('../../saturne.main.inc.php')) {
    require_once __DIR__ . '/../../saturne.main.inc.php';
} else {
    die('Include of saturne main fails');
}

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

// Load Saturne libraries
require_once __DIR__ . '/../lib/saturne.lib.php';

// Global variables definitions
global $conf, $db, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['admin', 'saturne@saturne']);

// Security check
$permissiontoread = $user->rights->saturne->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * Actions
 */
$action = GETPOST('action', 'aZ09');

$modulesToScan = ['saturne', 'reedcrm', 'erpal', 'eoxia_framework'];
$langFilesData = [];

if ($action == 'scan' || $action == 'fix') {
    $langsToScan = [];
    foreach ($modulesToScan as $mod) {
        $path = DOL_DOCUMENT_ROOT . '/custom/' . $mod . '/langs';
        if (dol_is_dir($path)) {
            $langsDirs = dol_dir_list($path, 'directories', 0);
            foreach ($langsDirs as $ldir) {
                $files = dol_dir_list($ldir['fullname'], 'files', 0, '\.lang$');
                foreach ($files as $f) {
                    $langsToScan[] = $f['fullname'];
                }
            }
        }
    }
    
    $fileToFix = GETPOST('file_to_fix');
    
    // Process files
    foreach ($langsToScan as $fpath) {
        $content = file_get_contents($fpath);
        // Normalize CRLF to LF just in case
        $content = str_replace("\r\n", "\n", $content);
        $lines = explode("\n", $content);
        $anomaliesCount = 0;
        $keysCount = 0;
        $newlines = [];
        
        foreach ($lines as $line) {
            $cleaned = $line;
            if (trim($line) === '' || strpos(trim($line), '#') === 0) {
                $newlines[] = $line;
                continue;
            }
            if (preg_match('/^([^=]+?)\s*=\s*(.*)$/', $line, $matches)) {
                $keysCount++;
                $cleaned = trim($matches[1]) . ' = ' . trim($matches[2]);
                if ($line !== $cleaned) {
                    $anomaliesCount++;
                }
            }
            $newlines[] = $cleaned;
        }
        
        if ($action == 'fix' && $anomaliesCount > 0 && $fpath === $fileToFix) {
            file_put_contents($fpath, implode("\n", $newlines));
            $anomaliesCount = 0; // Fixed!
            setEventMessages('Le fichier '.basename($fpath).' a été corrigé avec succès.', null, 'mesgs');
        }
        
        $langFilesData[$fpath] = [
            'anomalies' => $anomaliesCount,
            'keys' => $keysCount,
            'cleaned' => implode("\n", $newlines)
        ];
    }
}

/*
 * View
 */

$title    = $langs->trans('ModuleSetup', 'Saturne');
$help_url  = 'FR:Module_Saturne#Configuration';

saturne_header(0, '', $title, $help_url);

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">' . $langs->trans('BackToModuleList') . '</a>';

print load_fiche_titre($title, $linkback, 'title_setup');

// Configuration header
$head = saturne_admin_prepare_head();
print dol_get_fiche_head($head, 'tools', $title, -1, 'saturne_color@saturne');

print load_fiche_titre('<span class="saturne-dynamic-title"><i class="fas fa-wrench paddingright"></i> Outils Saturne</span>', '', '');

print '<div class="fichecenter">';
print '<div style="border: 1px solid var(--colortexttitre, #2b7da1); padding: 20px; border-radius: 8px; margin-bottom: 30px;">';

print load_fiche_titre('<i class="fas fa-language paddingright"></i> Normalisation des fichiers de langue', '', '');

print '<div style="margin-bottom: 20px;">';
print '  <p style="margin-bottom: 15px;">Cet utilitaire parcourt les fichiers <code>.lang</code> des modules liés à l\'écosystème Saturne et identifie les problèmes de formatage autour du signe <code>=</code>.</p>';

print '  <form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
print '    <input type="hidden" name="token" value="'.newToken().'">';
print '    <input type="hidden" name="action" value="scan">';
print '    <button type="submit" class="button"><i class="fas fa-search paddingright"></i> Analyser les fichiers .lang</button>';
print '  </form>';
print '</div>';

if ($action == 'scan' || $action == 'fix') {
    print '<table class="noborder centpercent">';
    print '  <tr class="liste_titre">';
    print '    <td>Chemin du fichier</td>';
    print '    <td align="center" width="150">Clés de traduction</td>';
    print '    <td align="center" width="200">Anomalies</td>';
    print '    <td align="center" width="120">Statut</td>';
    print '    <td align="center" width="80">Réparer</td>';
    print '  </tr>';
    
    $totalAnomalies = 0;
    
    foreach ($langFilesData as $file => $data) {
        $count = $data['anomalies'];
        $keys = $data['keys'];
        $totalAnomalies += $count;
        $shortfile = str_replace(DOL_DOCUMENT_ROOT . '/custom/', '', $file);
        $modalId = 'modal_fix_' . md5($file);
        
        print '  <tr class="oddeven">';
        
        print '    <td>' . $shortfile . '</td>';
        print '    <td align="center">' . $keys . '</td>';
        
        if ($count == 0) {
            print '    <td align="center" class="opacitymedium">0</td>';
            print '    <td align="center"><span class="badge badge-success" style="background:#2ecc71; color:#fff; padding:3px 8px; border-radius:4px; display:inline-block; min-width:80px;"><i class="fas fa-check"></i> Conforme</span></td>';
            print '    <td align="center"><a href="#" class="butActionRefused" onclick="return false;" title="Aucune réparation requise" style="margin: 0; height: 31px; padding: 0 10px; display: inline-flex; align-items: center; box-sizing: border-box;">RÉPARER</a></td>';
        } else {
            print '    <td align="center"><strong><span style="color:#e74c3c">' . $count . '</span> espaces non conformes</strong></td>';
            print '    <td align="center"><span class="badge badge-warning" style="background:#f39c12; color:#fff; padding:3px 8px; border-radius:4px; display:inline-block; min-width:80px;"><i class="fas fa-exclamation-triangle"></i> Erreurs</span></td>';
            print '    <td align="center"><a href="#" class="butAction" onclick="$(\'#'.$modalId.'\').dialog({modal:true, width:800, title:\'Réparation du fichier\'}); return false;" style="margin: 0; height: 31px; padding: 0 10px; display: inline-flex; align-items: center; box-sizing: border-box;">RÉPARER</a></td>';
        }
        print '  </tr>';
        
        // Render hidden modal for ALL files
        print '<div id="'.$modalId.'" style="display:none;">';
        print '  <div style="background:#f8f9fa; padding:15px; border-radius:5px; margin-bottom:15px; border:1px solid #ddd;">';
        print '    <div style="margin-bottom:8px;"><strong>Fichier :</strong> ' . $shortfile . '</div>';
        print '    <div style="margin-bottom:8px;"><strong>Clés de traduction initiales :</strong> ' . $keys . '</div>';
        print '    <div style="margin-bottom:8px;"><strong>Clés de traduction corrigées :</strong> ' . $keys . ' <span style="color:#2ecc71;"><i class="fas fa-check"></i> Préservées</span></div>';
        if ($count > 0) {
            print '    <div><strong>Anomalies détectées :</strong> <span style="color:#e74c3c;">' . $count . '</span> espaces d\'égalisation à nettoyer</div>';
        } else {
            print '    <div><strong>Anomalies détectées :</strong> <span style="color:#2ecc71;">' . $count . '</span> espaces d\'égalisation à nettoyer</div>';
        }
        print '  </div>';
        print '  <div><strong>Aperçu du contenu corrigé :</strong></div>';
        print '  <textarea style="width:100%; height:250px; font-family:monospace; font-size:12px; margin-top:10px;" readonly>' . htmlspecialchars($data['cleaned']) . '</textarea>';
        print '  <div style="text-align:right; margin-top:15px;">';
        print '    <form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
        print '      <input type="hidden" name="token" value="'.newToken().'">';
        print '      <input type="hidden" name="action" value="fix">';
        print '      <input type="hidden" name="file_to_fix" value="'.htmlspecialchars($file).'">';
        
        if ($count > 0) {
            print '      <button type="submit" class="button" style="padding: 6px 14px;"><i class="fas fa-magic paddingright"></i> Appliquer la réparation</button>';
        } else {
            print '      <button type="button" class="button button_disabled" disabled style="padding: 6px 14px;"><i class="fas fa-check paddingright"></i> Aucune réparation requise</button>';
        }
        
        print '    </form>';
        print '  </div>';
        print '</div>';
    }
    
    if (count($langFilesData) === 0) {
        print '  <tr><td colspan="3" align="center" class="opacitymedium">Aucun fichier de langue trouvé.</td></tr>';
    }
    
    print '</table>';
}

print '</div>';
print '</div>'; // End fichecenter

print dol_get_fiche_end();

llxFooter();
$db->close();
