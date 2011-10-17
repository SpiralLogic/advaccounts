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
	$page_security = 'SA_SHIPPING';

	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	page(_($help_context = "Shipping Company"));

	simple_page_mode(true);
	//----------------------------------------------------------------------------------------------

	function can_process() {
		if (strlen($_POST['shipper_name']) == 0) {
			ui_msgs::display_error(_("The shipping company name cannot be empty."));
			ui_view::set_focus('shipper_name');
			return false;
		}
		return true;
	}

	//----------------------------------------------------------------------------------------------
	if ($Mode == 'ADD_ITEM' && can_process()) {

		$sql = "INSERT INTO shippers (shipper_name, contact, phone, phone2, address)
		VALUES (" . DBOld::escape($_POST['shipper_name']) . ", " .
		 DBOld::escape($_POST['contact']) . ", " .
		 DBOld::escape($_POST['phone']) . ", " .
		 DBOld::escape($_POST['phone2']) . ", " .
		 DBOld::escape($_POST['address']) . ")";

		DBOld::query($sql, "The Shipping Company could not be added");
		ui_msgs::display_notification(_('New shipping company has been added'));
		$Mode = 'RESET';
	}

	//----------------------------------------------------------------------------------------------

	if ($Mode == 'UPDATE_ITEM' && can_process()) {

		$sql = "UPDATE shippers SET shipper_name=" . DBOld::escape($_POST['shipper_name']) . " ,
		contact =" . DBOld::escape($_POST['contact']) . " ,
		phone =" . DBOld::escape($_POST['phone']) . " ,
		phone2 =" . DBOld::escape($_POST['phone2']) . " ,
		address =" . DBOld::escape($_POST['address']) . "
		WHERE shipper_id = " . DBOld::escape($selected_id);

		DBOld::query($sql, "The shipping company could not be updated");
		ui_msgs::display_notification(_('Selected shipping company has been updated'));
		$Mode = 'RESET';
	}

	//----------------------------------------------------------------------------------------------

	if ($Mode == 'Delete') {
		// PREVENT DELETES IF DEPENDENT RECORDS IN 'sales_orders'

		$sql = "SELECT COUNT(*) FROM sales_orders WHERE ship_via=" . DBOld::escape($selected_id);
		$result = DBOld::query($sql, "check failed");
		$myrow = DBOld::fetch_row($result);
		if ($myrow[0] > 0) {
			$cancel_delete = 1;
			ui_msgs::display_error(_("Cannot delete this shipping company because sales orders have been created using this shipper."));
		}
		else
		{
			// PREVENT DELETES IF DEPENDENT RECORDS IN 'debtor_trans'

			$sql = "SELECT COUNT(*) FROM debtor_trans WHERE ship_via=" . DBOld::escape($selected_id);
			$result = DBOld::query($sql, "check failed");
			$myrow = DBOld::fetch_row($result);
			if ($myrow[0] > 0) {
				$cancel_delete = 1;
				ui_msgs::display_error(_("Cannot delete this shipping company because invoices have been created using this shipping company."));
			}
			else
			{
				$sql = "DELETE FROM shippers WHERE shipper_id=" . DBOld::escape($selected_id);
				DBOld::query($sql, "could not delete shipper");
				ui_msgs::display_notification(_('Selected shipping company has been deleted'));
			}
		}
		$Mode = 'RESET';
	}

	if ($Mode == 'RESET') {
		$selected_id = -1;
		$sav = get_post('show_inactive');
		unset($_POST);
		$_POST['show_inactive'] = $sav;
	}
	//----------------------------------------------------------------------------------------------

	$sql = "SELECT * FROM shippers";
	if (!check_value('show_inactive')) $sql .= " WHERE !inactive";
	$sql .= " ORDER BY shipper_id";
	$result = DBOld::query($sql, "could not get shippers");

	start_form();
	start_table(Config::get('tables.style'));
	$th = array(_("Name"), _("Contact Person"), _("Phone Number"), _("Secondary Phone"), _("Address"), "", "");
	inactive_control_column($th);
	table_header($th);

	$k = 0; //row colour counter

	while ($myrow = DBOld::fetch($result))
	{
		alt_table_row_color($k);
		label_cell($myrow["shipper_name"]);
		label_cell($myrow["contact"]);
		label_cell($myrow["phone"]);
		label_cell($myrow["phone2"]);
		label_cell($myrow["address"]);
		inactive_control_cell($myrow["shipper_id"], $myrow["inactive"], 'shippers', 'shipper_id');
		edit_button_cell("Edit" . $myrow["shipper_id"], _("Edit"));
		delete_button_cell("Delete" . $myrow["shipper_id"], _("Delete"));
		end_row();
	}

	inactive_control_row($th);
	end_table(1);

	//----------------------------------------------------------------------------------------------

	start_table(Config::get('tables.style2'));

	if ($selected_id != -1) {
		if ($Mode == 'Edit') {
			//editing an existing Shipper

			$sql = "SELECT * FROM shippers WHERE shipper_id=" . DBOld::escape($selected_id);

			$result = DBOld::query($sql, "could not get shipper");
			$myrow = DBOld::fetch($result);

			$_POST['shipper_name'] = $myrow["shipper_name"];
			$_POST['contact'] = $myrow["contact"];
			$_POST['phone'] = $myrow["phone"];
			$_POST['phone2'] = $myrow["phone2"];
			$_POST['address'] = $myrow["address"];
		}
		hidden('selected_id', $selected_id);
	}

	text_row_ex(_("Name:"), 'shipper_name', 40);

	text_row_ex(_("Contact Person:"), 'contact', 30);

	text_row_ex(_("Phone Number:"), 'phone', 32, 30);

	text_row_ex(_("Secondary Phone Number:"), 'phone2', 32, 30);

	text_row_ex(_("Address:"), 'address', 50);

	end_table(1);

	submit_add_or_update_center($selected_id == -1, '', 'both');

	end_form();
	end_page();
?>
