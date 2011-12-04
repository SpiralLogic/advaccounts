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
	$page_security = 'SA_MANUFTRANSVIEW';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	JS::open_window(900, 500);
	Page::start(_($help_context = "View Work Order Production"), true);

	if ($_GET['trans_no'] != "") {
		$wo_production = $_GET['trans_no'];
	}

	function display_wo_production($prod_id)
		{
			$myrow = WO_Produce::get($prod_id);
			Display::br(1);
			Display::start_table('tablestyle');
			$th = array(
				_("Production #"), _("Reference"), _("For Work Order #"), _("Item"), _("Quantity Manufactured"), _("Date"));
			Display::table_header($th);
			Display::start_row();
			label_cell($myrow["id"]);
			label_cell($myrow["reference"]);
			label_cell(GL_UI::trans_view(ST_WORKORDER, $myrow["workorder_id"]));
			label_cell($myrow["stock_id"] . " - " . $myrow["StockDescription"]);
			qty_cell($myrow["quantity"], false, Item::qty_dec($myrow["stock_id"]));
			label_cell(Dates::sql2date($myrow["date_"]));
			Display::end_row();
			DB_Comments::display_row(ST_MANURECEIVE, $prod_id);
			Display::end_table(1);
			Display::is_voided(ST_MANURECEIVE, $prod_id, _("This production has been voided."));
		}


	Display::heading($systypes_array[ST_MANURECEIVE] . " # " . $wo_production);
	display_wo_production($wo_production);

	Display::br(2);
	end_page(true);

?>

