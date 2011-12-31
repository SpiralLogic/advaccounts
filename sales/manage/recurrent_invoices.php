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
	$page_security = 'SA_SRECURRENT';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	JS::open_window(900, 600);
	Page::start(_($help_context = "Recurrent Invoices"));
	Page::simple_mode(true);
	if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
		$input_error = 0;
		if (strlen($_POST['description']) == 0) {
			$input_error = 1;
			Errors::error(_("The area description cannot be empty."));
			JS::set_focus('description');
		}
		if ($input_error != 1) {
			if ($selected_id != -1) {
				$sql = "UPDATE recurrent_invoices SET
 			description=" . DB::escape($_POST['description']) . ",
 			order_no=" . DB::escape($_POST['order_no']) . ",
 			debtor_no=" . DB::escape($_POST['debtor_no']) . ",
 			group_no=" . DB::escape($_POST['group_no']) . ",
 			days=" . Validation::input_num('days', 0) . ",
 			monthly=" . Validation::input_num('monthly', 0) . ",
 			begin='" . Dates::date2sql($_POST['begin']) . "',
 			end='" . Dates::date2sql($_POST['end']) . "'
 			WHERE id = " . DB::escape($selected_id);
				$note = _('Selected recurrent invoice has been updated');
			}
			else {
				$sql = "INSERT INTO recurrent_invoices (description, order_no, debtor_no,
 			group_no, days, monthly, begin, end, last_sent) VALUES (" . DB::escape($_POST['description']) . ", " . DB::escape($_POST['order_no']) . ", " . DB::escape($_POST['debtor_no']) . ", " . DB::escape($_POST['group_no']) . ", " . Validation::input_num('days', 0) . ", " . Validation::input_num('monthly', 0) . ", '" . Dates::date2sql($_POST['begin']) . "', '" . Dates::date2sql($_POST['end']) . "', '" . Dates::date2sql(Add_Years($_POST['begin'], -5)) . "')";
				$note = _('New recurrent invoice has been added');
			}
			DB::query($sql, "The recurrent invoice could not be updated or added");
			Errors::notice($note);
			$Mode = MODE_RESET;
		}
	}
	if ($Mode == MODE_DELETE) {
		$cancel_delete = 0;
		if ($cancel_delete == 0) {
			$sql = "DELETE FROM recurrent_invoices WHERE id=" . DB::escape($selected_id);
			DB::query($sql, "could not delete recurrent invoice");
			Errors::notice(_('Selected recurrent invoice has been deleted'));
		} //end if Delete area
		$Mode = MODE_RESET;
	}
	if ($Mode == MODE_RESET) {
		$selected_id = -1;
		unset($_POST);
	}
	function get_sales_group_name($group_no) {
		$sql = "SELECT description FROM groups WHERE id = " . DB::escape($group_no);
		$result = DB::query($sql, "could not get group");
		$row = DB::fetch($result);
		return $row[0];
	}

	$sql = "SELECT * FROM recurrent_invoices ORDER BY description, group_no, debtor_no";
	$result = DB::query($sql, "could not get recurrent invoices");
	start_form();
	start_table('tablestyle width70');
	$th = array(
		_("Description"), _("Template No"), _("Customer"), _("Branch") . "/" . _("Group"), _("Days"), _("Monthly"), _("Begin"), _("End"), _("Last Created"), "", ""
	);
	table_header($th);
	$k = 0;
	while ($myrow = DB::fetch($result)) {
		$begin = Dates::sql2date($myrow["begin"]);
		$end = Dates::sql2date($myrow["end"]);
		$last_sent = Dates::sql2date($myrow["last_sent"]);
		alt_table_row_color($k);
		label_cell($myrow["description"]);
		label_cell(Debtor::trans_view(ST_SALESORDER, $myrow["order_no"]));
		if ($myrow["debtor_no"] == 0) {
			label_cell("");
			label_cell(get_sales_group_name($myrow["group_no"]));
		}
		else {
			label_cell(Debtor::get_name($myrow["debtor_no"]));
			label_cell(Sales_Branch::get_name($myrow['group_no']));
		}
		label_cell($myrow["days"]);
		label_cell($myrow['monthly']);
		label_cell($begin);
		label_cell($end);
		label_cell($last_sent);
		edit_button_cell("Edit" . $myrow["id"], _("Edit"));
		delete_button_cell("Delete" . $myrow["id"], _("Delete"));
		end_row();
	}
	end_table();
	end_form();
	echo '<br>';
	start_form();
	start_table('tablestyle2');
	if ($selected_id != -1) {
		if ($Mode == MODE_EDIT) {
			//editing an existing area
			$sql = "SELECT * FROM recurrent_invoices WHERE id=" . DB::escape($selected_id);
			$result = DB::query($sql, "could not get recurrent invoice");
			$myrow = DB::fetch($result);
			$_POST['description'] = $myrow["description"];
			$_POST['order_no'] = $myrow["order_no"];
			$_POST['debtor_no'] = $myrow["debtor_no"];
			$_POST['group_no'] = $myrow["group_no"];
			$_POST['days'] = $myrow["days"];
			$_POST['monthly'] = $myrow["monthly"];
			$_POST['begin'] = Dates::sql2date($myrow["begin"]);
			$_POST['end'] = Dates::sql2date($myrow["end"]);
		}
		hidden("selected_id", $selected_id);
	}
	text_row_ex(_("Description:"), 'description', 50);
	Sales_UI::templates_row(_("Template:"), 'order_no');
	Debtor::row(_("Customer:"), 'debtor_no', null, " ", true);
	if ($_POST['debtor_no'] > 0) {
		Debtor_Branch::row(_("Branch:"), $_POST['debtor_no'], 'group_no', null, false);
	}
	else {
		Sales_UI::groups_row(_("Sales Group:"), 'group_no', null, " ");
	}
	small_amount_row(_("Days:"), 'days', 0, null, null, 0);
	small_amount_row(_("Monthly:"), 'monthly', 0, null, null, 0);
	date_row(_("Begin:"), 'begin');
	date_row(_("End:"), 'end', null, null, 0, 0, 5);
	end_table(1);
	submit_add_or_update_center($selected_id == -1, '', 'both');
	end_form();
	Page::end();
?>
