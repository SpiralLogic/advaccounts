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
	$page_security = 'SA_ITEMSTRANSVIEW';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	JS::open_window(800, 500);
	Page::start(_($help_context = "Inventory Item Movement"));
	//------------------------------------------------------------------------------------------------
	Validation::check(Validation::STOCK_ITEMS, _("There are no items defined in the system."));
	if (get_post('ShowMoves')) {
		$Ajax->activate('doc_tbl');
	}
	if (isset($_GET['stock_id'])) {
		$_POST['stock_id'] = $_GET['stock_id'];
	}
	start_form();
	if (!Input::post('stock_id')) {
		$_POST['stock_id'] = Session::get()->global_stock_id;
	}
	start_table("class='tablestyle_noborder'");
	stock_items_list_cells(_("Select an item:"), 'stock_id', $_POST['stock_id'], false, true, false);
	locations_list_cells(_("From Location:"), 'StockLocation', null);
	date_cells(_("From:"), 'AfterDate', '', null, -30);
	date_cells(_("To:"), 'BeforeDate');
	submit_cells('ShowMoves', _("Show Movements"), '', _('Refresh Inquiry'), 'default');
	end_table();
	end_form();
	Session::get()->global_stock_id = $_POST['stock_id'];
	$before_date = Dates::date2sql($_POST['BeforeDate']);
	$after_date = Dates::date2sql($_POST['AfterDate']);
	$sql
	 = "SELECT type, trans_no, tran_date, person_id, qty, reference
	FROM stock_moves
	WHERE loc_code=" . DB::escape($_POST['StockLocation']) . "
	AND tran_date >= '" . $after_date . "'
	AND tran_date <= '" . $before_date . "'
	AND stock_id = " . DB::escape($_POST['stock_id']) . " ORDER BY tran_date,trans_id";
	$result = DB::query($sql, "could not query stock moves");
	Errors::check_db_error("The stock movements for the selected criteria could not be retrieved", $sql);
	div_start('doc_tbl');
	start_table(Config::get('tables_style'));
	$th = array(
		_("Type"), _("#"), _("Reference"), _("Date"), _("Detail"),
		_("Quantity In"), _("Quantity Out"), _("Quantity On Hand")
	);
	table_header($th);
	$sql = "SELECT SUM(qty) FROM stock_moves WHERE stock_id=" . DB::escape($_POST['stock_id']) . "
	AND loc_code=" . DB::escape($_POST['StockLocation']) . "
	AND tran_date < '" . $after_date . "'";
	$before_qty = DB::query($sql, "The starting quantity on hand could not be calculated");
	$before_qty_row = DB::fetch_row($before_qty);
	$after_qty = $before_qty = $before_qty_row[0];
	if (!isset($before_qty_row[0])) {
		$after_qty = $before_qty = 0;
	}
	start_row("class='inquirybg'");
	label_cell("<b>" . _("Quantity on hand before") . " " . $_POST['AfterDate'] . "</b>", "align=center colspan=5");
	label_cell("&nbsp;", "colspan=2");
	$dec = Num::qty_dec($_POST['stock_id']);
	qty_cell($before_qty, false, $dec);
	end_row();
	$j = 1;
	$k = 0; //row colour counter
	$total_in = 0;
	$total_out = 0;
	while ($myrow = DB::fetch($result))
	{
		alt_table_row_color($k);
		$trandate = Dates::sql2date($myrow["tran_date"]);
		$type_name = $systypes_array[$myrow["type"]];
		if ($myrow["qty"] > 0) {
			$quantity_formatted = Num::format($myrow["qty"], $dec);
			$total_in += $myrow["qty"];
		} else {
			$quantity_formatted = Num::format(-$myrow["qty"], $dec);
			$total_out += -$myrow["qty"];
		}
		$after_qty += $myrow["qty"];
		label_cell($type_name);
		label_cell(ui_view::get_trans_view_str($myrow["type"], $myrow["trans_no"]));
		label_cell(ui_view::get_trans_view_str($myrow["type"], $myrow["trans_no"], $myrow["reference"]));
		label_cell($trandate);
		$person = $myrow["person_id"];
		$gl_posting = "";
		if (($myrow["type"] == ST_CUSTDELIVERY) || ($myrow["type"] == ST_CUSTCREDIT)) {
			$cust_row = Sales_Trans::get_details($myrow["type"], $myrow["trans_no"]);
			if (strlen($cust_row['name']) > 0) {
				$person = $cust_row['name'] . " (" . $cust_row['br_name'] . ")";
			}
		}
		elseif ($myrow["type"] == ST_SUPPRECEIVE || $myrow['type'] == ST_SUPPCREDIT)
		{
			// get the supplier name
			$sql = "SELECT supp_name FROM suppliers WHERE supplier_id = '" . $myrow["person_id"] . "'";
			$supp_result = DB::query($sql, "check failed");
			$supp_row = DB::fetch($supp_result);
			if (strlen($supp_row['supp_name']) > 0) {
				$person = $supp_row['supp_name'];
			}
		}
		elseif ($myrow["type"] == ST_LOCTRANSFER || $myrow["type"] == ST_INVADJUST)
		{
			// get the adjustment type
			$movement_type = Inv_Movement::get_type( $myrow["person_id"]);
			$person = $movement_type["name"];
		}
		elseif ($myrow["type"] == ST_WORKORDER || $myrow["type"] == ST_MANUISSUE
		 || $myrow["type"] == ST_MANURECEIVE
		)
		{
			$person = "";
		}
		label_cell($person);
		label_cell((($myrow["qty"] >= 0) ? $quantity_formatted : ""), "nowrap align=right");
		label_cell((($myrow["qty"] < 0) ? $quantity_formatted : ""), "nowrap align=right");
		qty_cell($after_qty, false, $dec);
		end_row();
		$j++;
		If ($j == 12) {
			$j = 1;
			table_header($th);
		}
		//end of page full new headings if
	}
	//end of while loop
	start_row("class='inquirybg'");
	label_cell("<b>" . _("Quantity on hand after") . " " . $_POST['BeforeDate'] . "</b>", "align=center colspan=5");
	qty_cell($total_in, false, $dec);
	qty_cell($total_out, false, $dec);
	qty_cell($after_qty, false, $dec);
	end_row();
	end_table(1);
	div_end();
	end_page();

?>
