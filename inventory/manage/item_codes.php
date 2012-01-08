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

Page::start(_($help_context = "Foreign Item Codes"), SA_FORITEMCODE);
	Validation::check(Validation::PURCHASE_ITEMS, _("There are no inventory items defined in the system."), STOCK_PURCHASED);
	list($Mode,$selected_id) = Page::simple_mode(true);
	if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
		$input_error = 0;
		if ($_POST['stock_id'] == "" || !isset($_POST['stock_id'])) {
			$input_error = 1;
			Errors::error(_("There is no item selected."));
			JS::set_focus('stock_id');
		}
		elseif (!Validation::input_num('quantity')) {
			$input_error = 1;
			Errors::error(_("The price entered was not positive number."));
			JS::set_focus('quantity');
		}
		elseif ($_POST['description'] == '') {
			$input_error = 1;
			Errors::error(_("Item code description cannot be empty."));
			JS::set_focus('description');
		}
		elseif ($selected_id == -1) {
			$kit = Item_Code::get_kit($_POST['item_code']);
			if (DB::num_rows($kit)) {
				$input_error = 1;
				Errors::error(_("This item code is already assigned to stock item or sale kit."));
				JS::set_focus('item_code');
			}
		}
		if ($input_error == 0) {
			if ($Mode == ADD_ITEM) {
				Item_Code::add($_POST['item_code'], $_POST['stock_id'], $_POST['description'], $_POST['category_id'], $_POST['quantity'], 1);
				Errors::notice(_("New item code has been added."));
			}
			else {
				Item_Code::update($selected_id, $_POST['item_code'], $_POST['stock_id'], $_POST['description'], $_POST['category_id'], $_POST['quantity'], 1);
				Errors::notice(_("Item code has been updated."));
			}
			$Mode = MODE_RESET;
		}
	}
	if ($Mode == MODE_DELETE) {
		Item_Code::delete($selected_id);
		Errors::notice(_("Item code has been sucessfully deleted."));
		$Mode = MODE_RESET;
	}
	if ($Mode == MODE_RESET) {
		$selected_id = -1;
		unset($_POST);
	}
	if (list_updated('stock_id')) {
		Ajax::i()->activate('_page_body');
	}
	start_form();
	if (!Input::post('stock_id')) {
		$_POST['stock_id'] = Session::i()->global_stock_id;
	}
	echo "<div class='center'>" . _("Item:") . "&nbsp;";
	echo Item_Purchase::select('stock_id', $_POST['stock_id'], false, true, false, false);
	echo "<hr></div>";
	Session::i()->global_stock_id = $_POST['stock_id'];
	$result = Item_Code::get_defaults($_POST['stock_id']);
	$dec = $result['decimals'];
	$units = $result['units'];
	$dflt_desc = $result['description'];
	$dflt_cat = $result['category_id'];
	$result = Item_Code::get_all($_POST['stock_id']);
	Display::div_start('code_table');
	start_table('tablestyle width60');
	$th = array(
		_("EAN/UPC Code"), _("Quantity"), _("Units"), _("Description"), _("Category"), "", ""
	);
	table_header($th);
	$k = $j = 0; //row colour counter
	while ($myrow = DB::fetch($result)) {
		alt_table_row_color($k);
		label_cell($myrow["item_code"]);
		qty_cell($myrow["quantity"], $dec);
		label_cell($units);
		label_cell($myrow["description"]);
		label_cell($myrow["cat_name"]);
		edit_button_cell("Edit" . $myrow['id'], _("Edit"));
		edit_button_cell("Delete" . $myrow['id'], _("Delete"));
		end_row();
		$j++;
		If ($j == 12) {
			$j = 1;
			table_header($th);
		} //end of page full new headings
	} //end of while loop
	end_table();
	Display::div_end();
	if ($selected_id != '') {
		if ($Mode == MODE_EDIT) {
			$myrow = Item_Code::get($selected_id);
			$_POST['item_code'] = $myrow["item_code"];
			$_POST['quantity'] = $myrow["quantity"];
			$_POST['description'] = $myrow["description"];
			$_POST['category_id'] = $myrow["category_id"];
		}
		hidden('selected_id', $selected_id);
	}
	else {
		$_POST['quantity'] = 1;
		$_POST['description'] = $dflt_desc;
		$_POST['category_id'] = $dflt_cat;
	}
	echo "<br>";
	start_table('tablestyle2');
	hidden('code_id', $selected_id);
	text_row(_("UPC/EAN code:"), 'item_code', null, 20, 21);
	qty_row(_("Quantity:"), 'quantity', null, '', $units, $dec);
	text_row(_("Description:"), 'description', null, 50, 200);
	Item_Category::row(_("Category:"), 'category_id', null);
	end_table(1);
	submit_add_or_update_center($selected_id == -1, '', 'both');
	end_form();
	Page::end();

?>
