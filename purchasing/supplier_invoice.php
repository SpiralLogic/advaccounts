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
	$page_security = 'SA_SUPPLIERINVOICE';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	include_once(APP_PATH . "purchasing/includes/purchasing_db.php");
	include_once(APP_PATH . "purchasing/includes/purchasing_ui.php");
	JS::open_window(900, 500);
	Page::start(_($help_context = "Enter Supplier Invoice"));
	//----------------------------------------------------------------------------------------
	Validation::check(Validation::SUPPLIERS, _("There are no suppliers defined in the system."));
	//---------------------------------------------------------------------------------------------------------------
	if (isset($_GET['AddedID'])) {
		$invoice_no = $_GET['AddedID'];
		$trans_type = ST_SUPPINVOICE;
		echo "<center>";
		Errors::notice(_("Supplier invoice has been processed."));
		Display::note(ui_view::get_trans_view_str($trans_type, $invoice_no, _("View this Invoice")));
		hyperlink_no_params("/purchasing/inquiry/po_search.php", _("Purchase Order Maintainants"));
		hyperlink_params($_SERVER['PHP_SELF'], _("Enter Another Invoice"), "New=1");
		hyperlink_no_params("/purchasing/supplier_payment.php", _("Entry supplier &payment for this invoice"));
		Display::note(ui_view::get_gl_view_str($trans_type, $invoice_no, _("View the GL Journal Entries for this Invoice")), 1);
		hyperlink_params("/admin/attachments.php", _("Add an Attachment"), "filterType=$trans_type&trans_no=$invoice_no");
		Page::footer_exit();
	}
	//--------------------------------------------------------------------------------------------------
	if (isset($_GET['New'])) {
		//session_register("SuppInv");
		//session_register("supp_trans");
		Purchase_Trans::instance(true);
		Purchase_Trans::instance()->is_invoice = true;
		if (isset($_GET['SuppID'])) {
			$_SESSION['supplier_id'] = $_GET['SuppID'];
		}
	}
	//--------------------------------------------------------------------------------------------------
	function clear_fields()
	{
		$Ajax = Ajax::instance();
		unset($_POST['gl_code']);
		unset($_POST['dimension_id']);
		unset($_POST['dimension2_id']);
		unset($_POST['amount']);
		unset($_POST['memo_']);
		unset($_POST['AddGLCodeToTrans']);
		$Ajax->activate('gl_items');
		JS::set_focus('gl_code');
	}

	//------------------------------------------------------------------------------------------------
	//	GL postings are often entered in the same form to two accounts
	//  so fileds are cleared only on user demand.
	//
	if (isset($_POST['ClearFields'])) {
		clear_fields();
	}
	if (isset($_POST['AddGLCodeToTrans'])) {
		$Ajax->activate('gl_items');
		$input_error = false;
		$sql = "SELECT account_code, account_name FROM chart_master WHERE account_code=" . DB::escape($_POST['gl_code']);
		$result = DB::query($sql, "get account information");
		if (DB::num_rows($result) == 0) {
			Errors::error(_("The account code entered is not a valid code, this line cannot be added to the transaction."));
			JS::set_focus('gl_code');
			$input_error = true;
		}
		else {
			$myrow = DB::fetch_row($result);
			$gl_act_name = $myrow[1];
			if (!Validation::is_num('amount')) {
				Errors::error(_("The amount entered is not numeric. This line cannot be added to the transaction."));
				JS::set_focus('amount');
				$input_error = true;
			}
		}
		if (!Tax_Types::is_tax_gl_unique(get_post('gl_code'))) {
			Errors::error(_("Cannot post to GL account used by more than one tax type."));
			JS::set_focus('gl_code');
			$input_error = true;
		}
		if ($input_error == false) {
			Purchase_Trans::instance()->add_gl_codes_to_trans($_POST['gl_code'], $gl_act_name, null, null, input_num('amount'), $_POST['memo_']);
			$taxexists = false;
			foreach (
				Purchase_Trans::instance()->gl_codes as &$gl_item
			) {
				if ($gl_item->gl_code == 2430) {
					$taxexists = true;
					$gl_item->amount += input_num('amount') * .1;
					break;
				}
			}
			if (!$taxexists) {
				Purchase_Trans::instance()->add_gl_codes_to_trans(2430, 'GST Paid', 0, 0, input_num('amount') * .1, 'GST TAX Paid');
			}
			JS::set_focus('gl_code');
		}
	}
	//------------------------------------------------------------------------------------------------
	function check_data()
	{
		if (!Purchase_Trans::instance()->is_valid_trans_to_post()) {
			Errors::error(_("The invoice cannot be processed because the there are no items or values on the invoice.  Invoices are expected to have a charge."));
			return false;
		}
		if (!Refs::is_valid(Purchase_Trans::instance()->reference)) {
			Errors::error(_("You must enter an invoice reference."));
			JS::set_focus('reference');
			return false;
		}
		while (!is_new_reference(Purchase_Trans::instance()->reference, ST_SUPPINVOICE)) {
			//Errors::error(_("The entered reference is already in use."));
			//JS::set_focus('reference');
			//return false;
			Purchase_Trans::instance()->reference = Refs::get_next(ST_SUPPINVOICE);
		}
		if (!Refs::is_valid(Purchase_Trans::instance()->supp_reference)) {
			Errors::error(_("You must enter a supplier's invoice reference."));
			JS::set_focus('supp_reference');
			return false;
		}
		if (!Dates::is_date(Purchase_Trans::instance()->tran_date)) {
			Errors::error(_("The invoice as entered cannot be processed because the invoice date is in an incorrect format."));
			JS::set_focus('trans_date');
			return false;
		}
		elseif (!Dates::is_date_in_fiscalyear(Purchase_Trans::instance()->tran_date)) {
			Errors::error(_("The entered date is not in fiscal year."));
			JS::set_focus('trans_date');
			return false;
		}
		if (!Dates::is_date(Purchase_Trans::instance()->due_date)) {
			Errors::error(_("The invoice as entered cannot be processed because the due date is in an incorrect format."));
			JS::set_focus('due_date');
			return false;
		}
		$sql = "SELECT Count(*) FROM supp_trans WHERE supplier_id=" . DB::escape(Purchase_Trans::instance()->supplier_id) . " AND supp_reference=" . DB::escape($_POST['supp_reference']) . " AND ov_amount!=0"; // ignore voided invoice references
		$result = DB::query($sql, "The sql to check for the previous entry of the same invoice failed");
		$myrow = DB::fetch_row($result);
		if ($myrow[0] == 1) { /*Transaction reference already entered */
			Errors::error(_("This invoice number has already been entered. It cannot be entered again. (" . $_POST['supp_reference'] . ")"));
			return false;
		}
		return true;
	}

	//--------------------------------------------------------------------------------------------------
	function handle_commit_invoice()
	{
		copy_to_trans(Purchase_Trans::instance());
		if (!check_data()) {
			return;
		}
		if (get_post('ChgTax', 0) != 0) {
			$taxexists = false;
			foreach (
				Purchase_Trans::instance()->gl_codes as &$gl_item
			) {
				if ($gl_item->gl_code == 2430) {
					$taxexists = true;
					$gl_item->amount += get_post('ChgTax');
					break;
				}
			}
			if (!$taxexists) {
				Purchase_Trans::instance()->add_gl_codes_to_trans(2430, 'GST Paid', 0, 0, get_post('ChgTax'), 'GST Correction');
			}
		}
		if (get_post('ChgTotal', 0) != 0) {
			Purchase_Trans::instance()->add_gl_codes_to_trans(DB_Company::get_pref('default_cogs_act'), 'Cost of Goods Sold', 0, 0, get_post('ChgTotal'), 'Rounding Correction');
		}
		$invoice_no = add_supp_invoice(Purchase_Trans::instance());
		Purchase_Trans::instance()->clear_items();
		Purchase_Trans::killInstance();
		meta_forward($_SERVER['PHP_SELF'], "AddedID=$invoice_no");
	}

	//--------------------------------------------------------------------------------------------------
	if (isset($_POST['PostInvoice'])) {
		handle_commit_invoice();
	}
	function check_item_data($n)
	{
		if (!Validation::is_num('this_quantity_inv' . $n, 0) || input_num('this_quantity_inv' . $n) == 0) {
			Errors::error(_("The quantity to invoice must be numeric and greater than zero."));
			JS::set_focus('this_quantity_inv' . $n);
			return false;
		}
		if (!Validation::is_num('ChgPrice' . $n)) {
			Errors::error(_("The price is not numeric."));
			JS::set_focus('ChgPrice' . $n);
			return false;
		}
		if (!Validation::is_num('ExpPrice' . $n)) {
			Errors::error(_("The price is not numeric."));
			JS::set_focus('ExpPrice' . $n);
			return false;
		}
		$margin = SysPrefs::over_charge_allowance();
		if (Config::get('valid_charged_to_delivered_price') == True && $margin != 0) {
			if ($_POST['order_price' . $n] != input_num('ChgPrice' . $n)) {
				if ($_POST['order_price' . $n] == 0 || input_num('ChgPrice' . $n) / $_POST['order_price' . $n] > (1 + ($margin / 100))) {
					if ($_SESSION['err_over_charge'] != true) {
						Errors::error(
							_("The price being invoiced is more than the purchase order price by more than the allowed over-charge percentage. The system is set up to prohibit this. See the system administrator to modify the set up parameters if necessary.") . _(
								"The over-charge percentage allowance is :"
							) . $margin . "%"
						);
						JS::set_focus('ChgPrice' . $n);
						$_SESSION['err_over_charge'] = true;
						return false;
					}
					else {
						$_SESSION['err_over_charge'] = false;
					}
				}
			}
		}
		if (Config::get('valid_charged_to_delivered_qty') == True) {
			if (input_num('this_quantity_inv' . $n) / ($_POST['qty_recd' . $n] - $_POST['prev_quantity_inv' . $n]) > (1 + ($margin / 100))) {
				Errors::error(
					_("The quantity being invoiced is more than the outstanding quantity by more than the allowed over-charge percentage. The system is set up to prohibit this. See the system administrator to modify the set up parameters if necessary.") . _("The over-charge percentage allowance is :")
					 . $margin . "%"
				);
				JS::set_focus('this_quantity_inv' . $n);
				return false;
			}
		}
		return true;
	}

	function commit_item_data($n)
	{
		if (check_item_data($n)) {
			if (input_num('this_quantity_inv' . $n) >= ($_POST['qty_recd' . $n] - $_POST['prev_quantity_inv' . $n])) {
				$complete = true;
			}
			else {
				$complete = false;
			}
			$_SESSION['err_over_charge'] = false;
			Purchase_Trans::instance()->add_grn_to_trans(
				$n, $_POST['po_detail_item' . $n], $_POST['item_code' . $n], $_POST['description' . $n], $_POST['qty_recd' . $n],
				$_POST['prev_quantity_inv' . $n], input_num('this_quantity_inv' . $n), $_POST['order_price' . $n], input_num('ChgPrice' . $n),
				$complete, $_POST['std_cost_unit' . $n], "", input_num('ChgDiscount' . $n), input_num('ExpPrice' . $n)
			);
		}
	}

	//-----------------------------------------------------------------------------------------
	$id = find_submit('grn_item_id');
	if ($id != -1) {
		commit_item_data($id);
	}
	if (isset($_POST['InvGRNAll'])) {
		foreach (
			$_POST as $postkey => $postval
		) {
			if (strpos($postkey, "qty_recd") === 0) {
				$id = substr($postkey, strlen("qty_recd"));
				$id = (int)$id;
				commit_item_data($id);
			}
		}
	}
	//--------------------------------------------------------------------------------------------------
	$id3 = find_submit('Delete');
	if ($id3 != -1) {
		Purchase_Trans::instance()->remove_grn_from_trans($id3);
		$Ajax->activate('grn_items');
		$Ajax->activate('inv_tot');
	}
	$id4 = find_submit('Delete2');
	if ($id4 != -1) {
		if (!isset($taxtotal)) {
			$taxtotal = 0;
		}
		Purchase_Trans::instance()->remove_gl_codes_from_trans($id4);
		foreach (
			Purchase_Trans::instance()->gl_codes as $key => $gl_item
		) {
			if ($gl_item->gl_code == 2430) {
				$taxrecord = $key;
				continue;
			}
			$taxtotal += $gl_item->amount;
		}
		if (!is_null($taxrecord)) {
			Purchase_Trans::instance()->gl_codes[$taxrecord]->amount = $taxtotal * .1;
		}
		clear_fields();
		$Ajax->activate('gl_items');
		$Ajax->activate('inv_tot');
	}
	$id2 = -1;
	if (User::get()->can_access('SA_GRNDELETE')) {
		$id2 = find_submit('void_item_id');
		if ($id2 != -1) {
			DB::begin_transaction();
			$myrow = get_grn_item_detail($id2);
			$grn = get_grn_batch($myrow['grn_batch_id']);
			$sql
			 = "UPDATE purch_order_details
			SET quantity_received = qty_invoiced, quantity_ordered = qty_invoiced WHERE po_detail_item = " . $myrow["po_detail_item"];
			DB::query($sql, "The quantity invoiced of the purchase order line could not be updated");
			$sql
			 = "UPDATE grn_items
	    	SET qty_recd = quantity_inv WHERE id = " . $myrow["id"];
			DB::query($sql, "The quantity invoiced off the items received record could not be updated");
			update_average_material_cost($grn["supplier_id"], $myrow["item_code"], $myrow["unit_price"], -$myrow["QtyOstdg"], Dates::Today());
			add_stock_move(
				ST_SUPPRECEIVE, $myrow["item_code"], $myrow['grn_batch_id'], $grn['loc_code'], Dates::sql2date($grn["delivery_date"]), "", -$myrow["QtyOstdg"],
				$myrow['std_cost_unit'], $grn["supplier_id"], 1, $myrow['unit_price']
			);
			DB::commit_transaction();
			Errors::notice(sprintf(_('All yet non-invoiced items on delivery line # %d has been removed.'), $id2));
		}
	}
	if (isset($_POST['go'])) {
		$Ajax->activate('gl_items');
		ui_view::display_quick_entries(Purchase_Trans::instance(), $_POST['qid'], input_num('totamount'), QE_SUPPINV);
		$_POST['totamount'] = Num::price_format(0);
		$Ajax->activate('totamount');
		$Ajax->activate('inv_tot');
	}
	start_form();
	invoice_header(Purchase_Trans::instance());
	if ($_SESSION['supplier_id']) {
		$_POST['supplier_id'] = $_SESSION['supplier_id'];
		if (Purchase_Trans::instance()) {
			unset($_SESSION['supplier_id']);
			unset($_SESSION['delivery_po']);
		}
	}
	if ($_POST['supplier_id'] == '') {
		Errors::error(_("There is no supplier selected."));
	}
	else {
		display_grn_items(Purchase_Trans::instance(), 1);
		display_gl_items(Purchase_Trans::instance(), 1);
		invoice_totals(Purchase_Trans::instance());
		div_end();
	}
	//-----------------------------------------------------------------------------------------
	if ($id != -1 || $id2 != -1) {
		$Ajax->activate('grn_items');
		$Ajax->activate('inv_tot');
	}
	if (get_post('AddGLCodeToTrans')) {
		$Ajax->activate('inv_tot');
	}
	br();
	submit_center('PostInvoice', _("Enter Invoice"), true, '', 'default');
	br();
	end_form();
	//--------------------------------------------------------------------------------------------------
	Item::addEditDialog();
	JS::onload(
		<<<JS
	    $("#wrapper").delegate('.amount','change',function() {
      var feild = $(this), ChgTax=$('[name="ChgTax"]'),ChgTotal=$('[name="ChgTotal"]'),invTotal=$('#invoiceTotal'), feilds = $(this).parent().parent(), fv = {}, nodes = {
         qty: $('[name^="this_quantity"]',feilds),
         price: $('[name^="ChgPrice"]',feilds),
         discount: $('[name^="ChgDiscount"]',feilds),
         total: $('[id^="ChgTotal"]',feilds),
					eachprice: $('[id^="Ea"]',feilds)
      };
      if (feilds.hasClass('grid')) {
      $.each(nodes,function(k,v) {
         if (v && v.val()) fv[k] = Number(v.val().replace(',',''));
      });
      if (feild.attr('id') == nodes.total.attr('id')) {
         if (fv.price == 0 && fv.discount==0) {
            fv.price = fv.total / fv.qty;
         } else {
            fv.discount = 100*(1-(fv.total)/(fv.price*fv.qty));
         		fv.discount = Math.round(fv.discount*1)/1;
         }
         nodes.price.val(fv.price);
         nodes.discount.val(fv.discount);
      } else if (fv.qty > 0 && fv.price > 0) {
         fv.total = fv.qty*fv.price*((100-fv.discount)/100);
         nodes.total.val(Math.round(fv.total*100)/100 );
       };
       price_format(nodes.eachprice.attr('id'),(fv.total/fv.qty),2,true);
       } else {
	if (feild.attr('name')=='ChgTotal' || feild.attr('name')=='ChgTax') {
	var total = Number(invTotal.data('total'));
	var ChgTax =  Number(ChgTax.val().replace(',',''));
	var ChgTotal = Number(ChgTotal.val().replace(',',''));
	price_format(invTotal.attr('id'),total+ChgTax+ChgTotal,2,true); }
}});
JS
	);
	end_page();
?>
