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
	//---------------------------------------------------------------------------------------------------
	function display_grn_summary(&$po, $editable = false) {
		start_table(Config::get('tables_style2') . " width=90%");
		start_row();
		label_cells(_("Supplier"), $po->supplier_name, "class='label'");
		if (!Banking::is_company_currency($po->curr_code)) {
			label_cells(_("Order Currency"), $po->curr_code, "class='label'");
		}
		label_cells(
			_("For Purchase Order"), ui_view::get_trans_view_str(ST_PURCHORDER, $po->order_no),
			"class='label'"
		);
		label_cells(_("Ordered On"), $po->orig_order_date, "class='label'");
		label_cells(_("Supplier's Reference"), $po->requisition_no, "class='label'");
		end_row();
		start_row();
		if ($editable) {
			if (!isset($_POST['ref'])) {
				$_POST['ref'] = Refs::get_next(ST_SUPPRECEIVE);
			}
			ref_cells(_("Reference"), 'ref', '', null, "class='label'");
			if (!isset($_POST['Location'])) {
				$_POST['Location'] = $po->Location;
			}
			label_cell(_("Deliver Into Location"), "class='label'");
			locations_list_cells(null, "Location", $_POST['Location']);
			if (!isset($_POST['DefaultReceivedDate'])) {
				$_POST['DefaultReceivedDate'] = Dates::new_doc_date();
			}
			date_cells(_("Date Items Received"), 'DefaultReceivedDate', '', true, 0, 0, 0, "class='label'");
		} else {
			label_cells(_("Reference"), $po->reference, "class='label'");
			label_cells(_("Deliver Into Location"), get_location_name($po->Location), "class='label'");
		}
		end_row();
		if (!$editable) {
			label_row(_("Delivery Address"), $po->delivery_address, "class='label'", "colspan=9");
		}
		if ($po->Comments != "") {
			label_row(_("Order Comments"), $po->Comments, "class='label'", "colspan=9");
		}
		end_table(1);
	}

?>