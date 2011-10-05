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
	$page_security = 'SA_MANUFTRANSVIEW';

	include_once($_SERVER['DOCUMENT_ROOT'] . "/includes/session.inc");

	$js = "";
	if (Config::get('ui.windows.popups'))
		$js .= ui_view::get_js_open_window(900, 500);
	page(_($help_context = "View Work Order Production"), true, false, "", $js);

	include_once(APP_PATH . "includes/manufacturing.inc");

	include_once(APP_PATH . "manufacturing/includes/manufacturing_ui.inc");

	//-------------------------------------------------------------------------------------------------

	if ($_GET['trans_no'] != "") {
		$wo_production = $_GET['trans_no'];
	}

	//-------------------------------------------------------------------------------------------------

	function display_wo_production($prod_id) {

		$myrow = get_work_order_produce($prod_id);

		br(1);
		start_table(Config::get('tables.style'));
		$th = array(_("Production #"), _("Reference"), _("For Work Order #"),
			_("Item"), _("Quantity Manufactured"), _("Date")
		);
		table_header($th);

		start_row();
		label_cell($myrow["id"]);
		label_cell($myrow["reference"]);
		label_cell(ui_view::get_trans_view_str(ST_WORKORDER, $myrow["workorder_id"]));
		label_cell($myrow["stock_id"] . " - " . $myrow["StockDescription"]);
		qty_cell($myrow["quantity"], false, get_qty_dec($myrow["stock_id"]));
		label_cell(Dates::sql2date($myrow["date_"]));
		end_row();

		ui_view::comments_display_row(ST_MANURECEIVE, $prod_id);

		end_table(1);

		ui_view::is_voided_display(ST_MANURECEIVE, $prod_id, _("This production has been voided."));
	}

	//-------------------------------------------------------------------------------------------------

	ui_msgs::display_heading($systypes_array[ST_MANURECEIVE] . " # " . $wo_production);

	display_wo_production($wo_production);

	//-------------------------------------------------------------------------------------------------

	br(2);

	end_page(true);

?>

