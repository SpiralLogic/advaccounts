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
	$page_security = 'SA_CHGPASSWD';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	Page::start(_($help_context = "Change password"));
	function can_process()
		{
			if (strlen($_POST['password']) < 4) {
				Errors::error(_("The password entered must be at least 4 characters long."));
				JS::set_focus('password');
				return false;
			}
			if (strstr($_POST['password'], User::get()->username) != false) {
				Errors::error(_("The password cannot contain the user login."));
				JS::set_focus('password');
				return false;
			}
			if ($_POST['password'] != $_POST['passwordConfirm']) {
				Errors::error(_("The passwords entered are not the same."));
				JS::set_focus('password');
				return false;
			}
			return true;
		}

	if (isset($_POST['UPDATE_ITEM'])) {
		if (can_process()) {
			if (Config::get('demo_mode')) {
				Errors::warning(_("Password cannot be changed in demo mode."));
			} else {
				$auth = new Auth(User::get()->username);
				$check = $auth->checkPasswordStrength($_POST['password']);
				if ($check['error'] > 0) {
					Errors::error($check['text']);
				}
				elseif ($check['strength'] < 3)
				{
					Errors::error(_("Password Too Weak!"));
				}
				else {
					$auth->update_password($_SESSION['current_user']->user, $_POST['password']);
					unset($_SESSION['change_password']);
					Errors::notice(_("Password Changed"));
				}
			}
			$Ajax->activate('_page_body');
		}
	} elseif (Input::session('change_password')) {
		Errors::warning('You are required to change your password!');
	}
	Display::start_form();
	Display::start_table('tablestyle');
	Display::table_section_title(_("Enter your new password in the fields."));
	$myrow = Users::get(User::get()->user);
	label_row(_("User login:"), $myrow['user_id']);
	$_POST['password'] = "";
	$_POST['passwordConfirm'] = "";
	password_row(_("Password:"), 'password', $_POST['password']);
	password_row(_("Repeat password:"), 'passwordConfirm', $_POST['passwordConfirm']);
	Display::end_table(1);
	submit_center('UPDATE_ITEM', _('Change password'), true, '', 'default');
	Display::end_form();
	end_page();
?>
