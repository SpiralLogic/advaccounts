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
	$page_security = 'SA_REORDER';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	Page::start(_($help_context = "Reorder Levels"));
	Validation::check(Validation::COST_ITEMS,
		_("There are no inventory items defined in the system (Purchased or manufactured items)."), STOCK_SERVICE);

	if (isset($_GET['stock_id'])) {
		$_POST['stock_id'] = $_GET['stock_id'];
	}
	if (list_updated('stock_id')) {
		$Ajax->activate('show_heading');
		$Ajax->activate('reorders');
	}

	Display::start_form();
	if (!Input::post('stock_id')) {
		$_POST['stock_id'] = Session::i()->global_stock_id;
	}
	echo "<div class='center'>" . _("Item:") . "&nbsp;";
	echo stock_costable_items_list('stock_id', $_POST['stock_id'], false, true);
	echo "<hr></div>";
	Display::div_start('show_heading');
	Display::item_heading($_POST['stock_id']);
	Display::br();
	Display::div_end();
	Session::i()->global_stock_id = $_POST['stock_id'];
	Display::div_start('reorders');
	Display::start_table('tablestyle width30');
	$th = array(_("Location"), _("Quantity On Hand"), _("Re-Order Level"));
	Display::table_header($th);
	$j = 1;
	$k = 0; //row colour counter
	$result = Inv_Location::get_details($_POST['stock_id']);
	while ($myrow = DB::fetch($result))
	{
		Display::alt_table_row_color($k);
		if (isset($_POST['UpdateData']) && Validation::is_num($myrow["loc_code"])) {
			$myrow["reorder_level"] = Validation::input_num($myrow["loc_code"]);
			Inv_Location::set_reorder($_POST['stock_id'], $myrow["loc_code"], Validation::input_num($myrow["loc_code"]));
			Errors::notice(_("Reorder levels has been updated."));
		}
		$qoh = Item::get_qoh_on_date($_POST['stock_id'], $myrow["loc_code"]);
		label_cell($myrow["location_name"]);
		$_POST[$myrow["loc_code"]] = Item::qty_format($myrow["reorder_level"], $_POST['stock_id'], $dec);
		qty_cell($qoh, false, $dec);
		qty_cells(null, $myrow["loc_code"], null, null, null, $dec);
		Display::end_row();
		$j++;
		If ($j == 12) {
			$j = 1;
			Display::table_header($th);
		}
	}
	Display::end_table(1);
	Display::div_end();
	submit_center('UpdateData', _("Update"), true, false, 'default');
	Display::end_form();
	end_page();

?>
