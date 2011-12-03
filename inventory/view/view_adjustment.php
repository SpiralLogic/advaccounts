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
	Page::start(_($help_context = "View Inventory Adjustment"), true);
	if (isset($_GET["trans_no"])) {
		$trans_no = $_GET["trans_no"];
	}
	Display::heading($systypes_array[ST_INVADJUST] . " #$trans_no");
	Display::br(1);
	$adjustment_items = Inv_Adjustment::get($trans_no);
	$k = 0;
	$header_shown = false;
	while ($adjustment = DB::fetch($adjustment_items))
	{
		if (!$header_shown) {
			$adjustment_type = Inv_Movement::get_type($adjustment['person_id']);
			Display::start_table(Config::get('tables_style2') . " width=90%");
			Display::start_row();
			label_cells(_("At Location"), $adjustment['location_name'], "class='tableheader2'");
			label_cells(_("Reference"), $adjustment['reference'], "class='tableheader2'", "colspan=6");
			label_cells(_("Date"), Dates::sql2date($adjustment['tran_date']), "class='tableheader2'");
			label_cells(_("Adjustment Type"), $adjustment_type['name'], "class='tableheader2'");
			Display::end_row();
			DB_Comments::display_row(ST_INVADJUST, $trans_no);
			Display::end_table();
			$header_shown = true;
			echo "<br>";
			Display::start_table(Config::get('tables_style') . "  width=90%");
			$th = array(
				_("Item"), _("Description"), _("Quantity"),
				_("Units"), _("Unit Cost")
			);
			Display::table_header($th);
		}
		Display::alt_table_row_color($k);
		label_cell($adjustment['stock_id']);
		label_cell($adjustment['description']);
		qty_cell($adjustment['qty'], false, Item::qty_dec($adjustment['stock_id']));
		label_cell($adjustment['units']);
		amount_decimal_cell($adjustment['standard_cost']);
		Display::end_row();
	}
	Display::end_table(1);
	Display::is_voided(ST_INVADJUST, $trans_no, _("This adjustment has been voided."));
	end_page(true);
?>