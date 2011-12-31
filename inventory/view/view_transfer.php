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
	$page_security = 'SA_ITEMSTRANSVIEW';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	Page::start(_($help_context = "View Inventory Transfer"), true);
	if (isset($_GET["trans_no"])) {
		$trans_no = $_GET["trans_no"];
	}
	$transfer_items = Inv_Transfer::get($trans_no);
	$from_trans = $transfer_items[0];
	$to_trans = $transfer_items[1];
	Display::heading($systypes_array[ST_LOCTRANSFER] . " #$trans_no");
	echo "<br>";
	start_table('tablestyle2 width90');
	start_row();
	label_cells(_("Item"), $from_trans['stock_id'] . " - " . $from_trans['description'], "class='tableheader2'");
	label_cells(_("From Location"), $from_trans['location_name'], "class='tableheader2'");
	label_cells(_("To Location"), $to_trans['location_name'], "class='tableheader2'");
	end_row();
	start_row();
	label_cells(_("Reference"), $from_trans['reference'], "class='tableheader2'");
	$adjustment_type = Inv_Movement::get_type($from_trans['person_id']);
	label_cells(_("Adjustment Type"), $adjustment_type['name'], "class='tableheader2'");
	label_cells(_("Date"), Dates::sql2date($from_trans['tran_date']), "class='tableheader2'");
	end_row();
	DB_Comments::display_row(ST_LOCTRANSFER, $trans_no);
	end_table(1);
	echo "<br>";
	start_table('tablestyle width90');
	$th = array(_("Item"), _("Description"), _("Quantity"), _("Units"));
	table_header($th);
	$transfer_items = Inv_Movement::get(ST_LOCTRANSFER, $trans_no);
	$k = 0;
	while ($item = DB::fetch($transfer_items)) {
		if ($item['loc_code'] == $to_trans['loc_code']) {
			alt_table_row_color($k);
			label_cell($item['stock_id']);
			label_cell($item['description']);
			qty_cell($item['qty'], false, Item::qty_dec($item['stock_id']));
			label_cell($item['units']);
			end_row();
			;
		}
	}
	end_table(1);
	Display::is_voided(ST_LOCTRANSFER, $trans_no, _("This transfer has been voided."));
	Page::end(true);
?>