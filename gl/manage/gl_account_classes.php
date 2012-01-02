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
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	$page_security = SA_GLACCOUNTCLASS;

	Page::start(_($help_context = "GL Account Classes"));
	Page::simple_mode(true);
	function can_process() {
		if (!is_numeric($_POST['id'])) {
			Errors::error(_("The account class ID must be numeric."));
			JS::set_focus('id');
			return false;
		}
		if (strlen($_POST['name']) == 0) {
			Errors::error(_("The account class name cannot be empty."));
			JS::set_focus('name');
			return false;
		}
		if (Config::get('accounts_gl_oldconvertstyle') == 1) {
			$_POST['Balance'] = check_value('Balance');
		}
		return true;
	}

	if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
		if (can_process()) {
			if ($selected_id != -1) {
				if (GL_Class::update($selected_id, $_POST['name'], $_POST['ctype'])) {
					Errors::notice(_('Selected account class settings has been updated'));
				}
			}
			else {
				if (GL_Class::add($_POST['id'], $_POST['name'], $_POST['ctype'])) {
					Errors::notice(_('New account class has been added'));
					$Mode = MODE_RESET;
				}
			}
		}
	}
	function can_delete($selected_id) {
		if ($selected_id == -1) {
			return false;
		}
		$sql = "SELECT COUNT(*) FROM chart_types
		WHERE class_id=$selected_id";
		$result = DB::query($sql, "could not query chart master");
		$myrow = DB::fetch_row($result);
		if ($myrow[0] > 0) {
			Errors::error(_("Cannot delete this account class because GL account types have been created referring to it."));
			return false;
		}
		return true;
	}

	if ($Mode == MODE_DELETE) {
		if (can_delete($selected_id)) {
			GL_Class::delete($selected_id);
			Errors::notice(_('Selected account class has been deleted'));
		}
		$Mode = MODE_RESET;
	}
	if ($Mode == MODE_RESET) {
		$selected_id = -1;
		$_POST['id'] = $_POST['name'] = $_POST['ctype'] = '';
	}
	$result = GL_Class::get_all(check_value('show_inactive'));
	start_form();
	start_table('tablestyle');
	$th = array(_("Class ID"), _("Class Name"), _("Class Type"), "", "");
	if (Config::get('accounts_gl_oldconvertstyle') == 1) {
		$th[2] = _("Balance Sheet");
	}
	inactive_control_column($th);
	table_header($th);
	$k = 0;
	while ($myrow = DB::fetch($result)) {
		alt_table_row_color($k);
		label_cell($myrow["cid"]);
		label_cell($myrow['class_name']);
		if (Config::get('accounts_gl_oldconvertstyle') == 1) {
			$myrow['ctype'] = ($myrow["ctype"] >= CL_ASSETS && $myrow["ctype"] < CL_INCOME ? 1 : 0);
			label_cell(($myrow['ctype'] == 1 ? _("Yes") : _("No")));
		}
		else {
			label_cell($class_types[$myrow["ctype"]]);
		}
		inactive_control_cell($myrow["cid"], $myrow["inactive"], 'chart_class', 'cid');
		edit_button_cell("Edit" . $myrow["cid"], _("Edit"));
		delete_button_cell("Delete" . $myrow["cid"], _("Delete"));
		end_row();
	}
	inactive_control_row($th);
	end_table(1);
	start_table('tablestyle2');
	if ($selected_id != -1) {
		if ($Mode == MODE_EDIT) {
			//editing an existing status code
			$myrow = GL_Class::get($selected_id);
			$_POST['id'] = $myrow["cid"];
			$_POST['name'] = $myrow["class_name"];
			if (Config::get('accounts_gl_oldconvertstyle') == 1) {
				$_POST['ctype'] = ($myrow["ctype"] >= CL_ASSETS && $myrow["ctype"] < CL_INCOME ? 1 : 0);
			}
			else {
				$_POST['ctype'] = $myrow["ctype"];
			}
			hidden('selected_id', $selected_id);
		}
		hidden('id');
		label_row(_("Class ID:"), $_POST['id']);
	}
	else {
		text_row_ex(_("Class ID:"), 'id', 3);
	}
	text_row_ex(_("Class Name:"), 'name', 50, 60);
	if (Config::get('accounts_gl_oldconvertstyle') == 1) {
		check_row(_("Balance Sheet"), 'ctype', null);
	}
	else {
		GL_Class::types_row(_("Class Type:"), 'ctype', null);
	}
	end_table(1);
	submit_add_or_update_center($selected_id == -1, '', 'both');
	end_form();
	Page::end();

?>
