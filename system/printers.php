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
	$page_security = 'SA_PRINTERS';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	Page::start(_($help_context = "Printer Locations"));
	Page::simple_mode(true);
	if ($Mode == 'ADD_ITEM' || $Mode == 'UPDATE_ITEM') {
		$error = 0;
		if (empty($_POST['name'])) {
			$error = 1;
			Errors::error(_("Printer name cannot be empty."));
			JS::set_focus('name');
		}
		elseif (empty($_POST['host'])) {
			Errors::notice(_("You have selected printing to server at user IP."));
		}
		elseif (!Validation::is_num('tout', 0, 60)) {
			$error = 1;
			Errors::error(_("Timeout cannot be less than zero nor longer than 60 (sec)."));
			JS::set_focus('tout');
		}
		if ($error != 1) {
			Printer::write_def($selected_id, get_post('name'), get_post('descr'), get_post('queue'), get_post('host'), Validation::input_num('port', 0), Validation::input_num('tout', 0));
			Errors::notice($selected_id == -1 ? _('New printer definition has been created') :
											_('Selected printer definition has been updated'));
			$Mode = 'RESET';
		}
	}
	if ($Mode == 'Delete') {
		// PREVENT DELETES IF DEPENDENT RECORDS IN print_profiles
		$sql = "SELECT COUNT(*) FROM print_profiles WHERE printer = " . DB::escape($selected_id);
		$result = DB::query($sql, "check printers relations failed");
		$myrow = DB::fetch_row($result);
		if ($myrow[0] > 0) {
			Errors::error(_("Cannot delete this printer definition, because print profile have been created using it."));
		}
		else {
			$sql = "DELETE FROM printers WHERE id=" . DB::escape($selected_id);
			DB::query($sql, "could not delete printer definition");
			Errors::notice(_('Selected printer definition has been deleted'));
		}
		$Mode = 'RESET';
	}
	if ($Mode == 'RESET') {
		$selected_id = -1;
		unset($_POST);
	}
	$result = Printer::get_all();
	start_form();
	start_table('tablestyle');
	$th = array(_("Name"), _("Description"), _("Host"), _("Printer Queue"), '', '');
	table_header($th);
	$k = 0; //row colour counter
	while ($myrow = DB::fetch($result)) {
		alt_table_row_color($k);
		label_cell($myrow['name']);
		label_cell($myrow['description']);
		label_cell($myrow['host']);
		label_cell($myrow['queue']);
		edit_button_cell("Edit" . $myrow['id'], _("Edit"));
		delete_button_cell("Delete" . $myrow['id'], _("Delete"));
		end_row();
	} //END WHILE LIST LOOP
	end_table();
	end_form();
	echo '<br>';
	start_form();
	start_table('tablestyle2');
	if ($selected_id != -1) {
		if ($Mode == 'Edit') {
			$myrow = Printer::get($selected_id);
			$_POST['name'] = $myrow['name'];
			$_POST['descr'] = $myrow['description'];
			$_POST['queue'] = $myrow['queue'];
			$_POST['tout'] = $myrow['timeout'];
			$_POST['host'] = $myrow['host'];
			$_POST['port'] = $myrow['port'];
		}
		hidden('selected_id', $selected_id);
	}
	else {
		if (!isset($_POST['host'])) {
			$_POST['host'] = 'localhost';
		}
		if (!isset($_POST['port'])) {
			$_POST['port'] = '515';
		}
	}
	text_row(_("Printer Name") . ':', 'name', null, 20, 20);
	text_row(_("Printer Description") . ':', 'descr', null, 40, 60);
	text_row(_("Host name or IP") . ':', 'host', null, 30, 40);
	text_row(_("Port") . ':', 'port', null, 5, 5);
	text_row(_("Printer Queue") . ':', 'queue', null, 20, 20);
	text_row(_("Timeout") . ':', 'tout', null, 5, 5);
	end_table(1);
	submit_add_or_update_center($selected_id == -1, '', 'both');
	end_form();
	Renderer::end_page();

?>
