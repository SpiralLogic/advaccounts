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

	//--------------------------------------------------------------------------------------

	function display_bom($item_check) {

		$result = Manufacturing::get_bom($item_check);

		if (DBOld::num_rows($result) == 0) {
			ui_msgs::display_note(_("The bill of material for this item is empty."), 0, 1);
		}
		else
		{

			start_table(Config::get('tables.style'));
			$th = array(_("Component"), _("Description"), _("Work Centre"),
				_("From Location"), _("Quantity"), _("Unit Cost"), _("Total Cost")
			);

			table_header($th);

			$j = 1;
			$k = 0; //row colour counter

			$total_cost = 0;

			while ($myrow = DBOld::fetch($result))
			{

				alt_table_row_color($k);

				label_cell($myrow["component"]);
				label_cell($myrow["description"]);
				label_cell($myrow["WorkCentreDescription"]);
				label_cell($myrow["location_name"]);
				qty_cell($myrow["quantity"], false, get_qty_dec($myrow["component"]));
				amount_cell($myrow["standard_cost"]);
				amount_cell($myrow["ComponentCost"]);
				end_row();
				$total_cost += $myrow["ComponentCost"];

				$j++;
				If ($j == 12) {
					$j = 1;
					table_header($th);
				}
				//end of page full new headings if
			}
			//end of while

			label_row("<b>" . _("Total Cost") . "</b>", "<b>" . number_format2($total_cost, user_price_dec()) . "</b>",
				"colspan=6 align=right", "nowrap align=right");
			end_table();
		}
	}

	//--------------------------------------------------------------------------------------

	function display_wo_requirements($woid, $quantity, $show_qoh = false, $date = null) {

		$result = get_wo_requirements($woid);

		if (DBOld::num_rows($result) == 0) {
			ui_msgs::display_note(_("There are no Requirements for this Order."), 1, 0);
		}
		else
		{

			start_table(Config::get('tables.style') . "  width=90%");
			$th = array(_("Component"), _("From Location"), _("Work Centre"),
				_("Unit Quantity"), _("Total Quantity"), _("Units Issued"), _("On Hand")
			);

			table_header($th);

			$k = 0; //row colour counter
			$has_marked = false;

			if ($date == null)
				$date = Dates::Today();

			while ($myrow = DBOld::fetch($result))
			{

				$qoh = 0;
				$show_qoh = true;
				// if it's a non-stock item (eg. service) don't show qoh
				if (!Manufacturing::has_stock_holding($myrow["mb_flag"]))
					$show_qoh = false;

				if ($show_qoh)
					$qoh = get_qoh_on_date($myrow["stock_id"], $myrow["loc_code"], $date);

				if ($show_qoh && ($myrow["units_req"] * $quantity > $qoh) &&
				 !SysPrefs::allow_negative_stock()
				) {
					// oops, we don't have enough of one of the component items
					start_row("class='stockmankobg'");
					$has_marked = true;
				}
				else
					alt_table_row_color($k);

				if (user_show_codes())
					label_cell($myrow["stock_id"] . " - " . $myrow["description"]);
				else
					label_cell($myrow["description"]);

				label_cell($myrow["location_name"]);
				label_cell($myrow["WorkCentreDescription"]);
				$dec = get_qty_dec($myrow["stock_id"]);
				qty_cell($myrow["units_req"], false, $dec);
				qty_cell($myrow["units_req"] * $quantity, false, $dec);
				qty_cell($myrow["units_issued"], false, $dec);
				if ($show_qoh)
					qty_cell($qoh, false, $dec);
				else
					label_cell("");
				end_row();
			}

			end_table();

			if ($has_marked)
				ui_msgs::display_note(_("Marked items have insufficient quantities in stock."), 0, 0, "class='red'");
		}
	}

	//--------------------------------------------------------------------------------------

	function display_wo_productions($woid) {

		$result = get_work_order_productions($woid);

		if (DBOld::num_rows($result) == 0) {
			ui_msgs::display_note(_("There are no Productions for this Order."), 1, 1);
		}
		else
		{
			start_table(Config::get('tables.style'));
			$th = array(_("#"), _("Reference"), _("Date"), _("Quantity"));

			table_header($th);

			$k = 0; //row colour counter
			$total_qty = 0;

			while ($myrow = DBOld::fetch($result))
			{

				alt_table_row_color($k);

				$total_qty += $myrow['quantity'];

				label_cell(ui_view::get_trans_view_str(29, $myrow["id"]));
				label_cell($myrow['reference']);
				label_cell(Dates::sql2date($myrow["date_"]));
				qty_cell($myrow['quantity'], false, get_qty_dec($myrow['reference']));
				end_row();
			}
			//end of while

			label_row(_("Total"), number_format2($total_qty, user_qty_dec()),
				"colspan=3", "nowrap align=right");

			end_table();
		}
	}

	//--------------------------------------------------------------------------------------

	function display_wo_issues($woid) {

		$result = get_work_order_issues($woid);

		if (DBOld::num_rows($result) == 0) {
			ui_msgs::display_note(_("There are no Issues for this Order."), 0, 1);
		}
		else
		{
			start_table(Config::get('tables.style'));
			$th = array(_("#"), _("Reference"), _("Date"));

			table_header($th);

			$k = 0; //row colour counter

			while ($myrow = DBOld::fetch($result))
			{

				alt_table_row_color($k);

				label_cell(ui_view::get_trans_view_str(28, $myrow["issue_no"]));
				label_cell($myrow['reference']);
				label_cell(Dates::sql2date($myrow["issue_date"]));
				end_row();
			}

			end_table();
		}
	}

	//--------------------------------------------------------------------------------------

	function display_wo_payments($woid) {
		global $wo_cost_types;

		//$result = get_bank_trans(null, null, PT_WORKORDER, $woid);
		$result = get_gl_wo_cost_trans($woid);

		if (DBOld::num_rows($result) == 0) {
			ui_msgs::display_note(_("There are no additional costs for this Order."), 0, 1);
		}
		else
		{
			start_table(Config::get('tables.style'));
			$th = array(_("#"), _("Type"), _("Date"), _("Amount"));

			table_header($th);

			$k = 0; //row colour counter

			while ($myrow = DBOld::fetch($result))
			{

				alt_table_row_color($k);

				label_cell(ui_view::get_gl_view_str(ST_WORKORDER, $myrow["type_no"], $myrow["type_no"]));
				label_cell($wo_cost_types[$myrow['person_id']]);
				$date = Dates::sql2date($myrow["tran_date"]);
				label_cell($date);
				amount_cell(-($myrow['amount']));
				end_row();
			}

			end_table();
		}
	}

	//--------------------------------------------------------------------------------------

	function display_wo_details($woid, $suppress_view_link = false) {
		global $wo_types_array;

		$myrow = get_work_order($woid);

		if (strlen($myrow[0]) == 0) {
			ui_msgs::display_note(_("The work order number sent is not valid."));
			exit;
		}

		start_table(Config::get('tables.style') . "  width=90%");

		if ($myrow["released"] == true)
			$th = array(_("#"), _("Reference"), _("Type"), _("Manufactured Item"),
				_("Into Location"), _("Date"), _("Required By"), _("Quantity Required"),
				_("Released Date"), _("Manufactured")
			);
		else
			$th = array(_("#"), _("Reference"), _("Type"), _("Manufactured Item"),
				_("Into Location"), _("Date"), _("Required By"), _("Quantity Required")
			);

		table_header($th);
		start_row();
		if ($suppress_view_link)
			label_cell($myrow["id"]);
		else
			label_cell(ui_view::get_trans_view_str(ST_WORKORDER, $myrow["id"]));
		label_cell($myrow["wo_ref"]);
		label_cell($wo_types_array[$myrow["type"]]);
		ui_view::view_stock_status_cell($myrow["stock_id"], $myrow["StockItemName"]);
		label_cell($myrow["location_name"]);
		label_cell(Dates::sql2date($myrow["date_"]));
		label_cell(Dates::sql2date($myrow["required_by"]));
		$dec = get_qty_dec($myrow["stock_id"]);
		qty_cell($myrow["units_reqd"], false, $dec);

		if ($myrow["released"] == true) {
			label_cell(Dates::sql2date($myrow["released_date"]));
			qty_cell($myrow["units_issued"], false, $dec);
		}
		end_row();

		ui_view::comments_display_row(ST_WORKORDER, $woid);

		end_table();

		if ($myrow["closed"] == true) {
			ui_msgs::display_note(_("This work order is closed."));
		}
	}

	//--------------------------------------------------------------------------------------

	function display_wo_details_quick($woid, $suppress_view_link = false) {
		global $wo_types_array;

		$myrow = get_work_order($woid);

		if (strlen($myrow[0]) == 0) {
			ui_msgs::display_note(_("The work order number sent is not valid."));
			exit;
		}

		start_table(Config::get('tables.style') . "  width=90%");

		$th = array(_("#"), _("Reference"), _("Type"), _("Manufactured Item"),
			_("Into Location"), _("Date"), _("Quantity")
		);
		table_header($th);

		start_row();
		if ($suppress_view_link)
			label_cell($myrow["id"]);
		else
			label_cell(ui_view::get_trans_view_str(ST_WORKORDER, $myrow["id"]));
		label_cell($myrow["wo_ref"]);
		label_cell($wo_types_array[$myrow["type"]]);
		ui_view::view_stock_status_cell($myrow["stock_id"], $myrow["StockItemName"]);
		label_cell($myrow["location_name"]);
		label_cell(Dates::sql2date($myrow["date_"]));

		qty_cell($myrow["units_issued"], false, get_qty_dec($myrow["stock_id"]));

		end_row();

		ui_view::comments_display_row(ST_WORKORDER, $woid);

		end_table();

		if ($myrow["closed"] == true) {
			ui_msgs::display_note(_("This work order is closed."));
		}
	}

?>