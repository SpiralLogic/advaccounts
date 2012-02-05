<?php

	/**********************************************************************
	Copyright (C) Advanced Group PTY LTD
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
	 ***********************************************************************/
	// Display demo user name and password within login form if "Config::get('demo_mode') " is true
	if (Config::get('demo_mode') == true) {
		$demo_text = _("Login as user: demouser and password: password");
	} else {
		$demo_text = _("Please login here");
	}
	if (!Config::get('company_default') === false) {
		Config::set('company_default', 1);
	}
	$def_theme = "default";
	$login_timeout = User::get()->last_act;
	$title = $login_timeout ? _('Authorization timeout') : APP_TITLE . " " . VERSION . " - " . _("Login");
	$encoding = isset($_SESSION['Language']->encoding) ? $_SESSION['Language']->encoding : "utf-8";
	$rtl = isset($_SESSION['Language']->dir) ? $_SESSION['Language']->dir : "ltr";
	$js = "(function set_fullmode() {	document.getElementById('ui_mode').value = 1;document.loginform.submit();return true;})();";
	if (!$login_timeout) {
		$js .= "(function defaultCompany(){document.forms[0].login_company.options[" . User::get()->company . "].selected = true;})()";
	}
	JS::beforeload($js);
	echo "<!DOCTYPE HTML>\n";
	echo "<html dir='$rtl' >\n";
	echo "<head><title>$title</title>\n";
	echo "<meta charset=$encoding>\n";
	echo "<link rel='apple-touch-icon' href='/company/images/advanced-icon.png'/>";
	echo "<link href='/themes/{$def_theme}/default.css' rel='stylesheet'> \n";

	echo "</head>\n";
	echo "<body class='loginscreen' >\n";
	echo "<table class='titletext'><tr><td>$title</td></tr></table>\n";
	Display::div_start('_page_body');
	Display::br(2);
	start_form(false, $_SESSION['timeout']['uri'], "loginform");	echo "<input type='hidden' id=ui_mode name='ui_mode' value='" . User::get()->ui_mode . "' />\n";

	start_table('login');
	start_row();
	echo "<td class='center' colspan=2>";
	if (!$login_timeout) { // ADV logo
		echo "<a target='_blank' href='" . POWERED_URL . "'><img src='/themes/$def_theme/images/logo_advaccounts.png' alt='ADVAccounts'  /></a>";
	} else {
		echo "<span class='font5'>" . _('Authorization timeout') . "</span><br>You were idle for: " . (User::get()->last_act + $_SESSION['current_user']->timeout - time());
	}
	echo "</td>\n";
	end_row();
	if (!$login_timeout) {
		table_section_title(_("Version ") . VERSION . " - " . _("Login"));
	}
	$value = $login_timeout ? $_SESSION['current_user']->loginname : (Config::get('demo_mode') ? "demouser" : "");	start_row();
			label_cell($demo_text, "colspan=2 class='center'");
			end_row();
	text_row(_("User name"), "user_name", $value, 20, 30);
	$password = Config::get('demo_mode') ? "password" : "";
	password_row(_("Password:"), 'password', $password);
	if ($login_timeout) {
		hidden('login_company', User::get()->company);
	} else {
		if (isset($_SESSION['current_user']->company)) {
			$coy = $_SESSION['current_user']->company;
		} else {
			$coy = Config::get('company_default');
		}
		echo "<tr><td class='label'>" . _("Company") . "</td><td><select name='login_company'>\n";
		$companies=Config::get_all('db');
		foreach ($companies as $name=>$company) {
			if (!$company['company']) continue;
			echo "<option value=$name " . ($name == $coy ? 'selected' : '') . ">" . $company['company'] . "</option>";
		}
		echo "</select>\n";

	}	start_row();
	echo "<td colspan='2' class='center pad20'><input type='submit' value='&nbsp;&nbsp;" . _("Login -->") . "&nbsp;&nbsp;'
	name='SubmitUser'" .
	 ($login_timeout ? '' : " ") . " /></td>\n";
			end_row();
	end_table(1);
	foreach (
		$_SESSION['timeout']['post'] as $p => $val
	) {
		// add all request variables to be resend together with login data
		if (!in_array($p, array('ui_mode', 'user_name', 'password', 'SubmitUser', 'login_company'))) {
			echo "<input type='hidden' name='$p' value='$val'>";
		}
	}
	end_form(1);
	Display::div_end();
	echo "<div class='center'>\n";
	if (isset($_SESSION['current_user'])) {
		echo 		$date = Dates::Today() . " | " . Dates::Now();
	} else {
		echo 	$date = date("m/d/Y") . " | " . date("h.i am");
	}
	echo "<div class='center'>\n";
	echo "<br><a class='pad20' target='_blank' href='" . POWERED_URL . "' tabindex='-1'>" . APP_TITLE . ' ' . VERSION . " - " . _
	("Theme:") . "
	 " . $def_theme . "</a>\n";
	echo "<br><br><a target='_blank' href='" . POWERED_URL . "' tabindex='-1'>" . POWERED_BY . "</a>\n";
	echo "<script>//<![CDATA[<!--
	 document.forms[0].user_name.select();
	 document.forms[0].user_name.focus();
	 //--> //]]></script>";
	echo "</body></html>\n";
?>
