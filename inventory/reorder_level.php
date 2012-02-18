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
	require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");

Page::start(_($help_context = "Reorder Levels"), SA_REORDER, Input::request('frame'));
	Validation::check(Validation::COST_ITEMS, _("There are no inventory items defined in the system (Purchased or manufactured items)."), STOCK_SERVICE);
	if (isset($_GET['stock_id'])) {
		$_POST['stock_id'] = $_GET['stock_id'];
	}
	if (list_updated('stock_id')) {
		Ajax::i()->activate('show_heading');
		Ajax::i()->activate('reorders');
	}
	start_form(false, $_SERVER['REQUEST_URI']);
	if (!Input::post('stock_id')) {
		$_POST['stock_id'] = Session::i()->global_stock_id;
	}
	if (!Input::request('frame')) {
		echo "<div class='center'>" . _("Item:") . "&nbsp;";
		echo Item_UI::costable('stock_id', $_POST['stock_id'], false, true);
		echo "<hr></div>";
		Display::div_start('show_heading');
		Display::item_heading($_POST['stock_id']);
		Display::br();
		Display::div_end();
		Session::i()->global_stock_id = $_POST['stock_id'];
	}
	Display::div_start('reorders');
	start_table('tablestyle width30');
	$th = array(_("Location"), _("Quantity On Hand"), _("Primary Shelf"), _("Secondary Shelf"), _("Re-Order Level"));
	table_header($th);
	$j = 1;
	$k = 0; //row colour counter
	$result = Inv_Location::get_details($_POST['stock_id']);
	$updated = false;
	while ($myrow = DB::fetch($result)) {
		if (isset($_POST['UpdateData']) && Validation::is_num($myrow["loc_code"])) {
			$myrow["reorder_level"] = Validation::input_num($myrow["loc_code"]);
			Inv_Location::set_reorder($_POST['stock_id'], $myrow["loc_code"], Validation::input_num($myrow["loc_code"]));
			Inv_Location::set_shelves($_POST['stock_id'], $myrow["loc_code"], $_POST['shelf_primary' . $myrow["loc_code"]], $_POST["shelf_secondary" . $myrow["loc_code"]]);
			$updated = true;
		}
		alt_table_row_color($k);
		$qoh = Item::get_qoh_on_date($_POST['stock_id'], $myrow["loc_code"]);
		label_cell($myrow["location_name"]);
		$_POST[$myrow["loc_code"]] = Item::qty_format($myrow["reorder_level"], $_POST['stock_id'], $dec);
		qty_cell($qoh, false, $dec);
		text_cells(null, 'shelf_primary' . $myrow["loc_code"], $myrow["shelf_primary"]);
		text_cells(null, 'shelf_secondary' . $myrow["loc_code"], $myrow["shelf_secondary"]);
		qty_cells(null, $myrow["loc_code"], null, null, null, $dec);
		end_row();
		$j++;
		If ($j == 12) {
			$j = 1;
			table_header($th);
		}
	}
	if ($updated) {
		Event::success(_("Reorder levels have been updated."));
	}
	end_table(1);
	Display::div_end();
	submit_center('UpdateData', _("Update"), true, false, 'default');
	end_form();
	if (Input::request('frame')) {
		Page::end(true);
	}
	else {
		Page::end();
	}
?>
