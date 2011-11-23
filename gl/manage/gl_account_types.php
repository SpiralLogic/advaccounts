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
	$page_security = 'SA_GLACCOUNTGROUP';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	Page::start(_($help_context = "GL Account Groups"));
	Page::simple_mode(true);
	//-----------------------------------------------------------------------------------
	function can_process()
	{
		global $selected_id;
		if (!input_num('id')) {
			Errors::error(_("The account id must be an integer and cannot be empty."));
			JS::set_focus('id');
			return false;
		}
		if (strlen($_POST['name']) == 0) {
			Errors::error(_("The account group name cannot be empty."));
			JS::set_focus('name');
			return false;
		}
		if (isset($selected_id) && ($selected_id == $_POST['parent'])) {
			Errors::error(_("You cannot set an account group to be a subgroup of itself."));
			return false;
		}
		return true;
	}

	//-----------------------------------------------------------------------------------
	if ($Mode == 'ADD_ITEM' || $Mode == 'UPDATE_ITEM') {
		if (can_process()) {
			if ($selected_id != -1) {
				if (GL_AccountType::update($selected_id, $_POST['name'], $_POST['class_id'], $_POST['parent'])) {
					Errors::notice(_('Selected account type has been updated'));
				}
			} else {
				if (GL_AccountType::add($_POST['id'], $_POST['name'], $_POST['class_id'], $_POST['parent'])) {
					Errors::notice(_('New account type has been added'));
					$Mode = 'RESET';
				}
			}
		}
	}
	//-----------------------------------------------------------------------------------
	function can_delete($selected_id)
	{
		if ($selected_id == -1) {
			return false;
		}
		$type = DB::escape($selected_id);
		$sql
		 = "SELECT COUNT(*) FROM chart_master
		WHERE account_type=$type";
		$result = DB::query($sql, "could not query chart master");
		$myrow = DB::fetch_row($result);
		if ($myrow[0] > 0) {
			Errors::error(_("Cannot delete this account group because GL accounts have been created referring to it."));
			return false;
		}
		$sql
		 = "SELECT COUNT(*) FROM chart_types
		WHERE parent=$type";
		$result = DB::query($sql, "could not query chart types");
		$myrow = DB::fetch_row($result);
		if ($myrow[0] > 0) {
			Errors::error(_("Cannot delete this account group because GL account groups have been created referring to it."));
			return false;
		}
		return true;
	}

	//-----------------------------------------------------------------------------------
	if ($Mode == 'Delete') {
		if (can_delete($selected_id)) {
			GL_AccountType::delete($selected_id);
			Errors::notice(_('Selected account group has been deleted'));
		}
		$Mode = 'RESET';
	}
	if ($Mode == 'RESET') {
		$selected_id = -1;
		$_POST['id'] = $_POST['name'] = '';
		unset($_POST['parent']);
		unset($_POST['class_id']);
	}
	//-----------------------------------------------------------------------------------
	$result = GL_AccountType::get_all(check_value('show_inactive'));
	start_form();
	start_table(Config::get('tables_style'));
	$th = array(_("ID"), _("Name"), _("Subgroup Of"), _("Class Type"), "", "");
	inactive_control_column($th);
	table_header($th);
	$k = 0;
	while ($myrow = DB::fetch($result))
	{
		alt_table_row_color($k);
		$bs_text = GL_AccountClass::get_name($myrow["class_id"]);
		if ($myrow["parent"] == ANY_NUMERIC) {
			$parent_text = "";
		} else {
			$parent_text = GL_AccountType::get_name($myrow["parent"]);
		}
		label_cell($myrow["id"]);
		label_cell($myrow["name"]);
		label_cell($parent_text);
		label_cell($bs_text);
		inactive_control_cell($myrow["id"], $myrow["inactive"], 'chart_types', 'id');
		edit_button_cell("Edit" . $myrow["id"], _("Edit"));
		delete_button_cell("Delete" . $myrow["id"], _("Delete"));
		end_row();
	}
	inactive_control_row($th);
	end_table(1);
	//-----------------------------------------------------------------------------------
	start_table(Config::get('tables_style2'));
	if ($selected_id != -1) {
		if ($Mode == 'Edit') {
			//editing an existing status code
			$myrow = GL_AccountType::get($selected_id);
			$_POST['id'] = $myrow["id"];
			$_POST['name'] = $myrow["name"];
			$_POST['parent'] = $myrow["parent"];
			$_POST['class_id'] = $myrow["class_id"];
			hidden('selected_id', $selected_id);
		}
		hidden('id');
		label_row(_("ID:"), $_POST['id']);
	} else {
		text_row_ex(_("ID:"), 'id', 10);
	}
	text_row_ex(_("Name:"), 'name', 50);
	gl_account_types_list_row(_("Subgroup Of:"), 'parent', null, _("None"), true);
	class_list_row(_("Class Type:"), 'class_id', null);
	end_table(1);
	submit_add_or_update_center($selected_id == -1, '', 'both');
	end_form();
	//------------------------------------------------------------------------------------
	end_page();

?>
