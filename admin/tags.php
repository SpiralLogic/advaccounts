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

	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	// For tag constants
	include_once(APP_PATH . "admin/db/tags_db.php");

	// Set up page security based on what type of tags we're working with
	if (@$_GET['type'] == "account" || get_post('type') == TAG_ACCOUNT) {
		$page_security = 'SA_GLACCOUNTTAGS';
	} else if (@$_GET['type'] == "dimension" || get_post('type') == TAG_DIMENSION) {
		$page_security = 'SA_DIMTAGS';
	}

	// We use Input::post('type') throughout this script, so convert $_GET vars
	// if Input::post('type') is not set.
	if (!Input::post('type')) {
		if (Input::get('type') == "account")
			$_POST['type'] = TAG_ACCOUNT;
		elseif (Input::get('type') == "dimension")
			$_POST['type'] = TAG_DIMENSION;
		else
			die(_("Unspecified tag type"));
	}

	// Set up page based on what type of tags we're working with
	switch (Input::post('type')) {
		case TAG_ACCOUNT:
			// Account tags
			$_SESSION['page_title'] = _($help_context = "Account Tags");
			break;
		case TAG_DIMENSION:
			// Dimension tags
			$_SESSION['page_title'] = _($help_context = "Dimension Tags");
	}

	page($_SESSION['page_title']);

	simple_page_mode(true);

	//-----------------------------------------------------------------------------------

	function can_process() {
		if (strlen($_POST['name']) == 0) {
			ui_msgs::display_error(_("The tag name cannot be empty."));
			ui_view::set_focus('name');
			return false;
		}
		return true;
	}

	//-----------------------------------------------------------------------------------

	if ($Mode == 'ADD_ITEM' || $Mode == 'UPDATE_ITEM') {
		if (can_process()) {
			if ($selected_id != -1) {
				if ($ret = Tags::update($selected_id, $_POST['name'], $_POST['description']))
					ui_msgs::display_notification(_('Selected tag settings have been updated'));
			}
			else
			{
				if ($ret = Tags::add(Input::post('type'), $_POST['name'], $_POST['description']))
					ui_msgs::display_notification(_('New tag has been added'));
			}
			if ($ret) $Mode = 'RESET';
		}
	}

	//-----------------------------------------------------------------------------------

	function can_delete($selected_id) {
		if ($selected_id == -1)
			return false;
		$result = Tags::get_associated_records($selected_id);

		if (DBOld::num_rows($result) > 0) {
			ui_msgs::display_error(_("Cannot delete this tag because records have been created referring to it."));
			return false;
		}

		return true;
	}

	//-----------------------------------------------------------------------------------

	if ($Mode == 'Delete') {
		if (can_delete($selected_id)) {
			Tags::delete($selected_id);
			ui_msgs::display_notification(_('Selected tag has been deleted'));
		}
		$Mode = 'RESET';
	}

	//-----------------------------------------------------------------------------------

	if ($Mode == 'RESET') {
		$selected_id   = -1;
		$_POST['name'] = $_POST['description'] = '';
	}

	//-----------------------------------------------------------------------------------

	$result = Tags::get_all(Input::post('type'), check_value('show_inactive'));

	start_form();
	start_table(Config::get('tables.style'));
	$th = array(_("Tag Name"), _("Tag Description"), "", "");
	inactive_control_column($th);
	table_header($th);

	$k = 0;
	while ($myrow = DBOld::fetch($result))
	{
		alt_table_row_color($k);

		label_cell($myrow['name']);
		label_cell($myrow['description']);
		inactive_control_cell($myrow["id"], $myrow["inactive"], 'tags', 'id');
		edit_button_cell("Edit" . $myrow["id"], _("Edit"));
		delete_button_cell("Delete" . $myrow["id"], _("Delete"));
		end_row();
	}

	inactive_control_row($th);
	end_table(1);

	//-----------------------------------------------------------------------------------

	start_table(Config::get('tables.style2'));

	if ($selected_id != -1) // We've selected a tag
	{
		if ($Mode == 'Edit') {
			// Editing an existing tag
			$myrow = Tags::get($selected_id);

			$_POST['name']        = $myrow["name"];
			$_POST['description'] = $myrow["description"];
		}
		// Note the selected tag
		hidden('selected_id', $selected_id);
	}

	text_row_ex(_("Tag Name:"), 'name', 15, 30);
	text_row_ex(_("Tag Description:"), 'description', 40, 60);
	hidden('type');

	end_table(1);

	submit_add_or_update_center($selected_id == -1, '', 'both');

	end_form();

	//------------------------------------------------------------------------------------

	end_page();

?>
