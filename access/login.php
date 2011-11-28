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

	echo "<!DOCTYPE HTML>\n";
	echo "<html dir='$rtl' >\n";
	echo "<head><title>$title</title>\n";
	echo "<meta http-equiv='Content-type' content='text/html; charset=$encoding' />\n";
	echo "<link rel='apple-touch-icon' href='/company/images/advanced-icon.png'/>";
	echo "<link href='/themes/$def_theme/default.css' rel='stylesheet' type='text/css'> \n";
	$js = "(function set_fullmode() {	document.getElementById('ui_mode').value = 1;document.loginform.submit();return true;})();";
	if (!$login_timeout) {
		$js .= "(function defaultCompany(){document.forms[0].company_login_name.options[" . User::get()->company . "].selected = true;})()";
	}
	JS::onLoad($js);
	echo "</head>\n";
	echo "<body id='loginscreen' >\n";
	echo "<table class='titletext'><tr><td>$title</td></tr></table>\n";
	div_start('_page_body');
	br(2);
	start_form(false, false, $_SESSION['timeout']['uri'], "loginform");
	start_table("class='login'");
	start_row();
	echo "<td align='center' colspan=2>";
	if (!$login_timeout) { // FA logo
		echo "<a target='_blank' href='" . POWERED_URL . "'><img src='/themes/$def_theme/images/logo_frontaccounting.png' alt='FrontAccounting' height='50' border='0' /></a>";
	} else {
		echo "<font size=5>" . _('Authorization timeout') . "</font><br>You were idle for: " . (User::get()->last_act + $_SESSION['current_user']->timeout - time());
	}
	echo "</td>\n";
	end_row();
	echo "<input type='hidden' id=ui_mode name='ui_mode' value='" . User::get()->ui_mode . "' />\n";
	if (!$login_timeout) {
		table_section_title(_("Version") . VERSION . "   Build " . BUILD_VERSION . " - " . _("Login"));
	}
	$value = $login_timeout ? $_SESSION['current_user']->loginname : (Config::get('demo_mode') ? "demouser" : "");
	text_row(_("User name"), "user_name_entry_field", $value, 20, 30);
	$password = Config::get('demo_mode') ? "password" : "";
	password_row(_("Password:"), 'password', $password);
	if ($login_timeout) {
		hidden('company_login_name', User::get()->company);
	} else {
		if (isset($_SESSION['current_user']->company)) {
			$coy = $_SESSION['current_user']->company;
		} else {
			$coy = Config::get('company_default');
		}
		echo "<tr><td class='label'>" . _("Company") . "</td><td><select name='company_login_name'>\n";
		for (
			$i = 1; $i < count(Config::get_all('db')) + 1; $i++
		) {
			echo "<option value=$i " . ($i == $coy ? 'selected' : '') . ">" . Config::get('db.' . $i, "name") . "</option>";
		}
		echo "</select>\n";
		start_row();
		label_cell($demo_text, "colspan=2 align='center'");
		end_row();
	}
	end_table(1);
	echo "<center><input type='submit' value='&nbsp;&nbsp;" . _("Login -->") . "&nbsp;&nbsp;' name='SubmitUser'" . ($login_timeout ? '' : " onclick='set_fullmode();'") . " /></center>\n";
	foreach (
		$_SESSION['timeout']['post'] as $p => $val
	) {
		// add all request variables to be resend together with login data
		if (!in_array($p, array('ui_mode', 'user_name_entry_field', 'password', 'SubmitUser', 'company_login_name'))) {
			echo "<input type='hidden' name='$p' value='$val'>";
		}
	}
	end_form(1);
	div_end();
	echo "<table class='bottomBar'>\n";
	echo "<tr>";
	if (isset($_SESSION['current_user'])) {
		$date = Dates::Today() . " | " . Dates::Now();
	} else {
		$date = date("m/d/Y") . " | " . date("h.i am");
	}
	echo "<td class='bottomBarCell'>$date</td>\n";
	echo "</tr></table>\n";
	echo "<table class='footer'>\n";
	echo "<tr>\n";
	echo "<td><a target='_blank' href='" . POWERED_URL . "' tabindex='-1'>" . APP_TITLE . ' ' . VERSION . " - " . _("Theme:") . " " . $def_theme . "</a></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td><a target='_blank' href='" . POWERED_URL . "' tabindex='-1'>" . POWERED_BY . "</a></td>\n";
	echo "</tr>\n";
	echo "</table><br><br>\n";
	echo "<script>//<![CDATA[<!--
	            document.forms[0].user_name_entry_field.select();
	            document.forms[0].user_name_entry_field.focus();
	            //--> //]]></script>";
	echo "</body></html>\n";
?>