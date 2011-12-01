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
	$page_security = 'SA_SALESPRICE';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	Page::start(_($help_context = "Inventory Item Sales prices"), Input::request('frame'));

	Validation::check(Validation::STOCK_ITEMS, _("There are no items defined in the system."));
	Validation::check(Validation::SALES_TYPES,
		_("There are no sales types in the system. Please set up sales types befor entering pricing."));
	Page::simple_mode(true);

	$input_error = 0;
	if (isset($_GET['stock_id'])) {
		$_POST['stock_id'] = $_GET['stock_id'];
	}
	if (isset($_GET['Item'])) {
		$_POST['stock_id'] = $_GET['Item'];
	}
	if (!isset($_POST['curr_abrev'])) {
		$_POST['curr_abrev'] = Banking::get_company_currency();
	}

	if (Input::request('frame')) {
		start_form(false, false, $_SERVER['PHP_SELF'] . '?frame=1');
	} else {
		start_form();
	}
	if (!Input::post('stock_id')) {
		$_POST['stock_id'] = Session::i()->global_stock_id;
	}
	if (!Input::request('frame')) {
		echo "<center>" . _("Item:") . "&nbsp;";
		echo sales_items_list('stock_id', $_POST['stock_id'], false, true, '', array(), true);
		echo "<hr></center>";
	}
	Session::i()->global_stock_id = $_POST['stock_id'];

	if ($Mode == 'ADD_ITEM' || $Mode == 'UPDATE_ITEM') {
		if (!Validation::is_num('price', 0)) {
			$input_error = 1;
			Errors::error(_("The price entered must be numeric."));
			JS::set_focus('price');
		}
		if ($input_error != 1) {
			if ($selected_id != -1) {
				//editing an existing price
				Item_Price::update($selected_id, $_POST['sales_type_id'], $_POST['curr_abrev'], input_num('price'));
				$msg = _("This price has been updated.");
			} else {
				Item_Price::add($_POST['stock_id'], $_POST['sales_type_id'], $_POST['curr_abrev'], input_num('price'));
				$msg = _("The new price has been added.");
			}
			Errors::notice($msg);
			$Mode = 'RESET';
		}
	}

	if ($Mode == 'Delete') {
		//the link to delete a selected record was clicked
		Item_Price::delete($selected_id);
		Errors::notice(_("The selected price has been deleted."));
		$Mode = 'RESET';
	}
	if ($Mode == 'RESET') {
		$selected_id = -1;
	}
	if (list_updated('stock_id')) {
		$Ajax->activate('price_table');
		$Ajax->activate('price_details');
	}
	if (list_updated('stock_id') || isset($_POST['_curr_abrev_update']) || isset($_POST['_sales_type_id_update'])) {
		// after change of stock, currency or salestype selector
		// display default calculated price for new settings.
		// If we have this price already in db it is overwritten later.
		unset($_POST['price']);
		$Ajax->activate('price_details');
	}

	$prices_list = Item_Price::get_all($_POST['stock_id']);
	div_start('price_table');
	if (Input::request('frame')) {
		start_table(Config::get('tables_style') . "  width=90%");
	} else {
		start_table(Config::get('tables_style') . "  width=30%");
	}
	$th = array(_("Currency"), _("Sales Type"), _("Price"), "", "");
	table_header($th);
	$k = 0; //row colour counter
	$calculated = false;
	while ($myrow = DB::fetch($prices_list)) {
		alt_table_row_color($k);
		label_cell($myrow["curr_abrev"]);
		label_cell($myrow["sales_type"]);
		amount_cell($myrow["price"]);
		edit_button_cell("Edit" . $myrow['id'], _("Edit"));
		delete_button_cell("Delete" . $myrow['id'], _("Delete"));
		end_row();
	}
	end_table();
	if (DB::num_rows($prices_list) == 0) {
		if (DB_Company::get_pref('add_pct') != -1) {
			$calculated = true;
		}
		Errors::warning(_("There are no prices set up for this part."), 1);
	}
	div_end();

	echo "<br>";
	if ($Mode == 'Edit') {
		$myrow = Item_Price::get($selected_id);
		$_POST['curr_abrev'] = $myrow["curr_abrev"];
		$_POST['sales_type_id'] = $myrow["sales_type_id"];
		$_POST['price'] = Num::price_format($myrow["price"]);
	}
	hidden('selected_id', $selected_id);
	div_start('price_details');
	start_table('class="tableinfo"');
	currencies_list_row(_("Currency:"), 'curr_abrev', null, true);
	sales_types_list_row(_("Sales Type:"), 'sales_type_id', null, true);
	if (!isset($_POST['price'])) {
		$_POST['price'] = Num::price_format(Item_Price::get_kit(get_post('stock_id'), get_post('curr_abrev'),
			get_post('sales_type_id')));
	}
	$kit = Item_Code::get_defaults($_POST['stock_id']);
	small_amount_row(_("Price:"), 'price', null, '', _('per') . ' ' . $kit["units"]);
	end_table(1);
	if ($calculated) {
		Errors::warning(_("The price is calculated."), 0, 1);
	}
	submit_add_or_update_center($selected_id == -1, '', 'both');
	div_end();
	end_form();
	if (Input::request('frame')) {
		end_page(true, true, true);
	} else {
		end_page();
	}