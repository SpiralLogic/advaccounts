<?php
/**********************************************************************
Copyright (C) FrontAccounting, LLC.
Released under the terms of the GNU General Public License, GPL,
as published by the Free Software Foundation, either version 3
of the License, or (at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
 ***********************************************************************/
	$page_security = 'SA_CHGPASSWD';
	$path_to_root = "..";
	include_once($_SERVER['DOCUMENT_ROOT'] . "/includes/session.inc");

	page(_($help_context = "Change password"));

	include_once($path_to_root . "/includes/date_functions.inc");
	include_once($path_to_root . "/includes/faui.inc");

	include_once($path_to_root . "/admin/db/users_db.inc");

	function can_process() {

		if (strlen($_POST['password']) < 4) {
			display_error(_("The password entered must be at least 4 characters long."));
			set_focus('password');
			return false;
		}

		if (strstr($_POST['password'], $_SESSION["wa_current_user"]->username) != false) {
			display_error(_("The password cannot contain the user login."));
			set_focus('password');
			return false;
		}

		if ($_POST['password'] != $_POST['passwordConfirm']) {
			display_error(_("The passwords entered are not the same."));
			set_focus('password');
			return false;
		}

		return true;
	}

	if (isset($_POST['UPDATE_ITEM'])) {

		if (can_process()) {
			if (Config::get('demo_mode') ) {
				display_warning(_("Password cannot be changed in demo mode."));
			} else {
				$auth = new Auth($_SESSION["wa_current_user"]->username);
				$check = $auth->checkPasswordStrength($_POST['password']);
				if ($check['error'] > 0)
					display_error($check['text']);
				elseif ($check['strength'] < 3)
					display_error(_("Password Too Weeak!"));
				else {
					$auth->update_password($_SESSION['wa_current_user']->user,$_POST['password']);
					unset($_SESSION['change_password']);
					display_notification(_("Password Changed"));
				}
			}
			$Ajax->activate('_page_body');
		}
	} elseif ($_SESSION['change_password']) {
	display_warning('You are required to change your password!');
}

	start_form();

	start_table( Config::get('tables.style') );
	table_section_title(_("Enter your new password in the fields."));

	$myrow = get_user($_SESSION["wa_current_user"]->user);

	label_row(_("User login:"), $myrow['user_id']);

	$_POST['password'] = "";
	$_POST['passwordConfirm'] = "";

	password_row(_("Password:"), 'password', $_POST['password']);
	password_row(_("Repeat password:"), 'passwordConfirm', $_POST['passwordConfirm']);


	end_table(1);

	submit_center('UPDATE_ITEM', _('Change password'), true, '', 'default');
	end_form();
	end_page();
?>
