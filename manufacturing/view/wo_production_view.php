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
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	JS::open_window(900, 500);
	Page::start(_($help_context = "View Work Order Production"), true);
	include_once(APP_PATH . "manufacturing/includes/manufacturing_ui.php");
	//-------------------------------------------------------------------------------------------------
	if ($_GET['trans_no'] != "") {
		$wo_production = $_GET['trans_no'];
	}
	//-------------------------------------------------------------------------------------------------
	function display_wo_production($prod_id)
	{
		$myrow = WO_Produce::get($prod_id);
		br(1);
		start_table(Config::get('tables_style'));
		$th = array(
			_("Production #"), _("Reference"), _("For Work Order #"), _("Item"), _("Quantity Manufactured"), _("Date"));
		table_header($th);
		start_row();
		label_cell($myrow["id"]);
		label_cell($myrow["reference"]);
		label_cell(ui_view::get_trans_view_str(ST_WORKORDER, $myrow["workorder_id"]));
		label_cell($myrow["stock_id"] . " - " . $myrow["StockDescription"]);
		qty_cell($myrow["quantity"], false, Num::qty_dec($myrow["stock_id"]));
		label_cell(Dates::sql2date($myrow["date_"]));
		end_row();
		Display::comments_row(ST_MANURECEIVE, $prod_id);
		end_table(1);
		Display::is_voided(ST_MANURECEIVE, $prod_id, _("This production has been voided."));
	}

	//-------------------------------------------------------------------------------------------------
	Display::heading($systypes_array[ST_MANURECEIVE] . " # " . $wo_production);
	display_wo_production($wo_production);
	//-------------------------------------------------------------------------------------------------
	br(2);
	end_page(true);

?>

