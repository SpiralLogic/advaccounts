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
	$page_security = 'SA_USERS';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	Page::start(_($help_context = "Users"));
	Page::simple_mode(true);

	function can_process($user)
	{
		if (strlen($_POST['user_id']) < 4) {
			Errors::error(_("The user login entered must be at least 4 characters long."));
			JS::set_focus('user_id');
			return false;
		}
		if ($_POST['password'] != "") {
			if (strlen($_POST['password']) < 4) {
				Errors::error(_("The password entered must be at least 4 characters long."));
				JS::set_focus('password');
				return false;
			}
			if (strstr($_POST['password'], $_POST['user_id']) != false) {
				Errors::error(_("The password cannot contain the user login."));
				JS::set_focus('password');
				return false;
			}
			$check = ($user !== null) ? $user->checkPasswordStrength($_POST['password']) : false;
			if (!$check && $check['error'] > 0) {
				Errors::error($check['text']);
				return false;
			}
			if (!$check && $check['strength'] < 3) {
				Errors::error(_("Password Too Weeak!"));
				return false;
			}
		}
		return true;
	}


	if ($Mode == 'ADD_ITEM' || $Mode == 'UPDATE_ITEM') {
		$user = null;
		if ($_POST['password'] != "") {
			$user = new Auth($_POST['user_id']);
			$password = $user->hash_password($_POST['password']);
		}
		if (can_process($user)) {
			if ($selected_id != -1) {
				Users::update(
					$selected_id, $_POST['user_id'], $_POST['real_name'], $_POST['phone'],
					$_POST['email'], $_POST['Access'], $_POST['language'],
					$_POST['profile'], check_value('rep_popup'), $_POST['pos']
				);
				Users::update_password($selected_id, $_POST['user_id'], $password);
				Errors::notice(_("The selected user has been updated."));
			} else {
				Users::add(
					$_POST['user_id'], $_POST['real_name'], $password,
					$_POST['phone'], $_POST['email'], $_POST['Access'], $_POST['language'],
					$_POST['profile'], check_value('rep_popup'), $_POST['pos']
				);
				$id = DB::insert_id();
				// use current user display preferences as start point for new user
				Users::update_display_prefs(
					$id, User::price_dec(), User::qty_dec(), User::exrate_dec(),
					User::percent_dec(), User::show_gl_info(), User::show_codes(),
					User::date_format(), User::date_sep(), User::tho_sep(),
					User::dec_sep(), User::theme(), User::pagesize(), User::hints(),
					$_POST['profile'], check_value('rep_popup'), User::query_size(),
					User::graphic_links(), $_POST['language'], User::sticky_date(), User::startup_tab()
				);
				Errors::notice(_("A new user has been added."));
			}
			$Mode = 'RESET';
		}
	}

	if ($Mode == 'Delete') {
		Users::delete($selected_id);
		Errors::notice(_("User has been deleted."));
		$Mode = 'RESET';
	}

	if ($Mode == 'RESET') {
		$selected_id = -1;
		$sav = Display::get_post('show_inactive');
		unset($_POST); // clean all input fields
		$_POST['show_inactive'] = $sav;
	}
	$result = Users::get_all(check_value('show_inactive'));
	Display::start_form();
	Display::start_table(Config::get('tables_style'));
	$th = array(
		_("User login"), _("Full Name"), _("Phone"),
		_("E-mail"), _("Last Visit"), _("Access Level"), "", ""
	);
	inactive_control_column($th);
	Display::table_header($th);
	$k = 0; //row colour counter
	while ($myrow = DB::fetch($result))
	{
		Display::alt_table_row_color($k);
		$last_visit_date = Dates::sql2date($myrow["last_visit_date"]);
		/*The security_headings array is defined in config.php */
		$not_me = strcasecmp($myrow["user_id"], User::get()->username);
		label_cell($myrow["user_id"]);
		label_cell($myrow["real_name"]);
		label_cell($myrow["phone"]);
		email_cell($myrow["email"]);
		label_cell($last_visit_date, "nowrap");
		label_cell($myrow["role"]);
		if ($not_me) {
			inactive_control_cell($myrow["id"], $myrow["inactive"], 'users', 'id');
		}
		elseif (check_value('show_inactive'))
		{
			label_cell('');
		}
		edit_button_cell("Edit" . $myrow["id"], _("Edit"));
		if ($not_me) {
			delete_button_cell("Delete" . $myrow["id"], _("Delete"));
		} else {
			label_cell('');
		}
		Display::end_row();
	} //END WHILE LIST LOOP
	inactive_control_row($th);
	Display::end_table(1);

	Display::start_table(Config::get('tables_style2'));
	$_POST['email'] = "";
	if ($selected_id != -1) {
		if ($Mode == 'Edit') {
			//editing an existing User
			$myrow = Users::get($selected_id);
			$_POST['id'] = $myrow["id"];
			$_POST['user_id'] = $myrow["user_id"];
			$_POST['real_name'] = $myrow["real_name"];
			$_POST['phone'] = $myrow["phone"];
			$_POST['email'] = $myrow["email"];
			$_POST['Access'] = $myrow["role_id"];
			$_POST['language'] = $myrow["language"];
			$_POST['profile'] = $myrow["print_profile"];
			$_POST['rep_popup'] = $myrow["rep_popup"];
			$_POST['pos'] = $myrow["pos"];
		}
		hidden('selected_id', $selected_id);
		hidden('user_id');
		Display::start_row();
		label_row(_("User login:"), Input::post('user_id'));
	} else { //end of if $selected_id only do the else when a new record is being entered
		text_row(_("User Login:"), "user_id", null, 22, 20);
		$_POST['language'] = User::language();
		$_POST['profile'] = User::print_profile();
		$_POST['rep_popup'] = User::rep_popup();
		$_POST['pos'] = User::pos();
	}
	$_POST['password'] = "";
	password_row(_("Password:"), 'password', $_POST['password']);
	if ($selected_id != -1) {
		Display::table_section_title(_("Enter a new password to change, leave empty to keep current."));
	}
	text_row_ex(_("Full Name") . ":", 'real_name', 50);
	text_row_ex(_("Telephone No.:"), 'phone', 30);
	email_row_ex(_("Email Address:"), 'email', 50);
	security_roles_list_row(_("Access Level:"), 'Access', null);
	languages_list_row(_("Language:"), 'language', null);
	pos_list_row(_("User's POS") . ':', 'pos', null);
	print_profiles_list_row(
		_("Printing profile") . ':', 'profile', null,
		_('Browser printing support')
	);
	check_row(
		_("Use popup window for reports:"), 'rep_popup', Input::post('rep_popup'),
		false, _('Set this option to on if your browser directly supports pdf files')
	);
	Display::end_table(1);
	submit_add_or_update_center($selected_id == -1, '', 'both');
	Display::end_form();
	end_page();
?>