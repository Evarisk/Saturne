<?php
/* Copyright (C) 2022-2023 EVARISK <technique@evarisk.com>
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
 * \file    lib/user.lib.php
 * \ingroup saturne
 * \brief   Library files with common functions for Saturne User
 */

/**
 *  Return a link to the user card (with optionaly the picto)
 *  Use this->id,this->lastname, this->firstname
 *
 * @param  User   $object                User object
 * @param  int    $withpictoimg          Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto, -1=Include photo into link, -2=Only picto photo, -3=Only photo very small)
 * @param  string $option                On what the link point to ('leave', 'nolink', )
 * @param  int    $infologin             0=Add default info tooltip, 1=Add complete info tooltip, -1=No info tooltip
 * @param  int    $notooltip             1=Disable tooltip on picto and name
 * @param  int    $maxlen                Max length of visible username
 * @param  int    $hidethirdpartylogo    Hide logo of thirdparty if user is external user
 * @param  string $mode                  ''=Show firstname and lastname, 'firstname'=Show only firstname, 'firstelselast'=Show firstname or lastname if not defined, 'login'=Show login
 * @param  string $morecss               Add more css on link
 * @param  int    $save_lastsearch_value -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
 * @param  int    $display_initials      Show only initials for firstname/lastname of user
 * @return string                        String with URL
 */
function get_nom_url_user(User $object, $withpictoimg = 0, $option = '', $infologin = 0, $notooltip = 0, $maxlen = 24, $hidethirdpartylogo = 0, $mode = '', $morecss = '', $save_lastsearch_value = -1, $display_initials = 1)
{
	global $langs, $conf, $db, $hookmanager, $dolibarr_main_demo;
	global $menumanager;

	if ( ! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) && $withpictoimg) $withpictoimg = 0;

	$result = ''; $label = '';

	if ( ! empty($object->photo)) {
		$label .= '<div class="photointooltip">';
		$label .= Form::showphoto('userphoto', $object, 0, 60, 0, 'photowithmargin photologintooltip', 'small', 0, 1); // Force height to 60 so we total height of tooltip can be calculated and collision can be managed
		$label .= '</div><div style="clear: both;"></div>';
	}

	// Info Login
	$company = '';
	$companylink = '';
	$label                               .= '<div class="centpercent">';
	$label                               .= '<u>' . $langs->trans("User") . '</u><br>';
	$label                               .= '<b>' . $langs->trans('Name') . ':</b> ' . $object->getFullName($langs, '');
	if ( ! empty($object->login)) $label .= '<br><b>' . $langs->trans('Login') . ':</b> ' . $object->login;
	if ( ! empty($object->job)) $label   .= '<br><b>' . $langs->trans("Job") . ':</b> ' . $object->job;
	$label                               .= '<br><b>' . $langs->trans("Email") . ':</b> ' . $object->email;
	if ( ! empty($object->phone)) $label .= '<br><b>' . $langs->trans("Phone") . ':</b> ' . $object->phone;
	if ( ! empty($object->admin))
		$label                           .= '<br><b>' . $langs->trans("Administrator") . '</b>: ' . yn($object->admin);
	if ( ! empty($object->socid)) {
		// Add thirdparty for external users
		$thirdpartystatic = new Societe($db);
		$thirdpartystatic->fetch($object->socid);
		if (empty($hidethirdpartylogo)) $companylink = ' ' . $thirdpartystatic->getNomUrl(2, (($option == 'nolink') ? 'nolink' : '')); // picto only of company
		$company                                     = ' (' . $langs->trans("Company") . ': ' . $thirdpartystatic->name . ')';
	}
	$type   = ($object->socid ? $langs->trans("External") . $company : $langs->trans("Internal"));
	$label .= '<br><b>' . $langs->trans("Type") . ':</b> ' . $type;
	$label .= '<br><b>' . $langs->trans("Status") . '</b>: ' . $object->getLibStatut(4);
	$label .= '</div>';
	if ($infologin > 0) {
		$label                                                        .= '<br>';
		$label                                                        .= '<br><u>' . $langs->trans("Session") . '</u>';
		$label                                                        .= '<br><b>' . $langs->trans("IPAddress") . '</b>: ' . $_SERVER["REMOTE_ADDR"];
		if ( ! empty($conf->global->MAIN_MODULE_MULTICOMPANY)) $label .= '<br><b>' . $langs->trans("ConnectedOnMultiCompany") . ':</b> ' . $conf->entity . ' (user entity ' . $object->entity . ')';
		$label                                                        .= '<br><b>' . $langs->trans("AuthenticationMode") . ':</b> ' . $_SESSION["dol_authmode"] . (empty($dolibarr_main_demo) ? '' : ' (demo)');
		$label                                                        .= '<br><b>' . $langs->trans("ConnectedSince") . ':</b> ' . dol_print_date($object->datelastlogin, "dayhour", 'tzuser');
		$label                                                        .= '<br><b>' . $langs->trans("PreviousConnexion") . ':</b> ' . dol_print_date($object->datepreviouslogin, "dayhour", 'tzuser');
		$label                                                        .= '<br><b>' . $langs->trans("CurrentTheme") . ':</b> ' . $conf->theme;
		$label                                                        .= '<br><b>' . $langs->trans("CurrentMenuManager") . ':</b> ' . $menumanager->name;
		$s                                                             = picto_from_langcode($langs->getDefaultLang());
		$label                                                        .= '<br><b>' . $langs->trans("CurrentUserLanguage") . ':</b> ' . ($s ? $s . ' ' : '') . $langs->getDefaultLang();
		$label                                                        .= '<br><b>' . $langs->trans("Browser") . ':</b> ' . $conf->browser->name . ($conf->browser->version ? ' ' . $conf->browser->version : '') . ' (' . $_SERVER['HTTP_USER_AGENT'] . ')';
		$label                                                        .= '<br><b>' . $langs->trans("Layout") . ':</b> ' . $conf->browser->layout;
		$label                                                        .= '<br><b>' . $langs->trans("Screen") . ':</b> ' . $_SESSION['dol_screenwidth'] . ' x ' . $_SESSION['dol_screenheight'];
		if ($conf->browser->layout == 'phone') $label                 .= '<br><b>' . $langs->trans("Phone") . ':</b> ' . $langs->trans("Yes");
		if ( ! empty($_SESSION["disablemodules"])) $label             .= '<br><b>' . $langs->trans("DisabledModules") . ':</b> <br>' . join(', ', explode(',', $_SESSION["disablemodules"]));
	}
	if ($infologin < 0) $label = '';

	$url                         = DOL_URL_ROOT . '/user/card.php?id=' . $object->id;
	if ($option == 'leave') $url = DOL_URL_ROOT . '/holiday/list.php?id=' . $object->id;

	if ($option != 'nolink') {
		// Add param to save lastsearch_values or not
		$add_save_lastsearch_values                                                                                      = ($save_lastsearch_value == 1 ? 1 : 0);
		if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values = 1;
		if ($add_save_lastsearch_values) $url                                                                           .= '&save_lastsearch_values=1';
	}

	$linkclose = "";
	if ($option == 'blank') {
		$linkclose .= ' target=_blank';
	}
	$linkstart = '<a href="' . $url . '"';
	if (empty($notooltip)) {
		if ( ! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
			$langs->load("users");
			$label      = $langs->trans("ShowUser");
			$linkclose .= ' alt="' . dol_escape_htmltag($label, 1) . '"';
		}
		$linkclose .= ' title="' . dol_escape_htmltag($label, 1) . '"';
		$linkclose .= ' class="classfortooltip' . ($morecss ? ' ' . $morecss : '') . '"';

		/*
		 $hookmanager->initHooks(array('userdao'));
		 $parameters=array('id'=>$object->id);
		 $reshook=$hookmanager->executeHooks('getnomurltooltip',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
		 if ($reshook > 0) $linkclose = $hookmanager->resPrint;
		 */
	}

	$linkstart .= $linkclose . '>';
	$linkend    = '</a>';

	//if ($withpictoimg == -1) $result.='<div class="nowrap">';
	$result .= (($option == 'nolink') ? '' : $linkstart);
	if ($withpictoimg) {
		$paddafterimage                              = '';
		if (abs($withpictoimg) == 1) $paddafterimage = 'style="margin-' . ($langs->trans("DIRECTION") == 'rtl' ? 'left' : 'right') . ': 3px;"';
		// Only picto
		if ($withpictoimg > 0) $picto = '<!-- picto user --><span class="nopadding userimg' . ($morecss ? ' ' . $morecss : '') . '">' . img_object('', 'user', $paddafterimage . ' ' . ($notooltip ? '' : 'class="paddingright classfortooltip"'), 0, 0, $notooltip ? 0 : 1) . '</span>';
		// Picto must be a photo
		else $picto = '<!-- picto photo user --><span class="nopadding userimg' . ($morecss ? ' ' . $morecss : '') . '"' . ($paddafterimage ? ' ' . $paddafterimage : '') . '>' . Form::showphoto('userphoto', $object, 0, 0, 0, 'userphoto' . ($withpictoimg == -3 ? 'small' : ''), 'mini', 0, 1) . '</span>';
		$result    .= $picto;
	}

	if ($withpictoimg > -2 && $withpictoimg != 2 && $display_initials) {
		$initials = '';
		if (dol_strlen($object->firstname)) {
			$initials .= str_split($object->firstname, 1)[0];
		}
		if (dol_strlen($object->lastname)) {
			$initials .= str_split($object->lastname, 1)[0];
		}
		if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) $result .= '<span class=" nopadding usertext' . (( ! isset($object->statut) || $object->statut) ? '' : ' strikefordisabled') . ($morecss ? ' ' . $morecss : '') . '">';
		if ($mode == 'login') $result                                  .= $initials;
		else $result                                                   .= $initials;
		if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) $result .= '</span>';
	} elseif ($display_initials == 0) {
		if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
			$result .= '<span class="nopadding usertext' . (( ! isset($object->statut) || $object->statut) ? '' : ' strikefordisabled') . ($morecss ? ' ' . $morecss : '') . '">';
		}
		if ($mode == 'login') {
			$result .= dol_string_nohtmltag(dol_trunc($object->login, $maxlen));
		} else {
			$result .= dol_string_nohtmltag($object->getFullName($langs, '', ($mode == 'firstelselast' ? 3 : ($mode == 'firstname' ? 2 : -1)), $maxlen));
		}
		if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
			$result .= '</span>';
		}
	}
	$result .= (($option == 'nolink') ? '' : $linkend);
	//if ($withpictoimg == -1) $result.='</div>';

	$result .= $companylink;

	global $action;
	$hookmanager->initHooks(array('userdao'));
	$parameters               = array('id' => $object->id, 'getnomurluser' => $result);
	$reshook                  = $hookmanager->executeHooks('getNomUrlUser', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
	if ($reshook > 0) $result = $hookmanager->resPrint;
	else $result             .= $hookmanager->resPrint;

	return $result;
}
