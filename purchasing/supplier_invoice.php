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
	include_once(APP_PATH . "/purchasing/includes/purchasing_db.php");

	include_once(APP_PATH . "purchasing/includes/purchasing_ui.php");
	$js = "";
	if (Config::get('ui.windows.popups')) {
		$js .= ui_view::get_js_open_window(900, 500);
	}

	page(_($help_context = "Enter Supplier Invoice"), false, false, "", $js);
	//----------------------------------------------------------------------------------------
	Validation::check(Validation::SUPPLIERS, _("There are no suppliers defined in the system."));
	//---------------------------------------------------------------------------------------------------------------
	if (isset($_GET['AddedID'])) {
		$invoice_no = $_GET['AddedID'];
		$trans_type = ST_SUPPINVOICE;
		echo "<center>";
		ui_msgs::display_notification_centered(_("Supplier invoice has been processed."));
		ui_msgs::display_note(ui_view::get_trans_view_str($trans_type, $invoice_no, _("View this Invoice")));
		hyperlink_no_params("/purchasing/inquiry/po_search.php", _("Purchase Order Maintainants"));
		hyperlink_params($_SERVER['PHP_SELF'], _("Enter Another Invoice"), "New=1");
		hyperlink_no_params("/purchasing/supplier_payment.php", _("Entry supplier &payment for this invoice"));
		ui_msgs::display_note(ui_view::get_gl_view_str($trans_type, $invoice_no, _("View the GL Journal Entries for this Invoice")), 1);
		hyperlink_params("/admin/attachments.php", _("Add an Attachment"), "filterType=$trans_type&trans_no=$invoice_no");
		ui_view::display_footer_exit();
	}
	//--------------------------------------------------------------------------------------------------
	if (isset($_GET['New'])) {
		if (isset($_SESSION['supp_trans'])) {
			unset ($_SESSION['supp_trans']->grn_items);
			unset ($_SESSION['supp_trans']->gl_codes);
			unset ($_SESSION['supp_trans']);
		}
		//session_register("SuppInv");
		//session_register("supp_trans");
		$_SESSION['supp_trans']             = new suppTrans;
		$_SESSION['supp_trans']->is_invoice = true;
		if (isset($_GET['SuppID'])) {
			$_SESSION['wa_global_supplier_id'] = $_GET['SuppID'];
		}
	}
	//--------------------------------------------------------------------------------------------------
	function clear_fields() {
		$Ajax = Ajax::instance();
		unset($_POST['gl_code']);
		unset($_POST['dimension_id']);
		unset($_POST['dimension2_id']);
		unset($_POST['amount']);
		unset($_POST['memo_']);
		unset($_POST['AddGLCodeToTrans']);
		$Ajax->activate('gl_items');
		ui_view::set_focus('gl_code');
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
		$sql         = "SELECT account_code, account_name FROM chart_master WHERE account_code=" . DBOld::escape($_POST['gl_code']);
		$result      = DBOld::query($sql, "get account information");
		if (DBOld::num_rows($result) == 0) {
			ui_msgs::display_error(_("The account code entered is not a valid code, this line cannot be added to the transaction."));
			ui_view::set_focus('gl_code');
			$input_error = true;
		}
		else {
			$myrow       = DBOld::fetch_row($result);
			$gl_act_name = $myrow[1];
			if (!check_num('amount')) {
				ui_msgs::display_error(_("The amount entered is not numeric. This line cannot be added to the transaction."));
				ui_view::set_focus('amount');
				$input_error = true;
			}
		}
		if (!Tax_Types::is_tax_gl_unique(get_post('gl_code'))) {
			ui_msgs::display_error(_("Cannot post to GL account used by more than one tax type."));
			ui_view::set_focus('gl_code');
			$input_error = true;
		}
		if ($input_error == false) {
			$_SESSION['supp_trans']->add_gl_codes_to_trans($_POST['gl_code'], $gl_act_name, null, null, input_num('amount'), $_POST['memo_']);
			$taxexists = false;
			foreach ($_SESSION['supp_trans']->gl_codes as &$gl_item) {
				if ($gl_item->gl_code == 2430) {
					$taxexists = true;
					$gl_item->amount += input_num('amount') * .1;
					break;
				}
			}
			if (!$taxexists) {
				$_SESSION['supp_trans']->add_gl_codes_to_trans(2430, 'GST Paid', 0, 0, input_num('amount') * .1, 'GST TAX Paid');
			}
			ui_view::set_focus('gl_code');
		}
	}
	//------------------------------------------------------------------------------------------------
	function check_data() {

		if (!$_SESSION['supp_trans']->is_valid_trans_to_post()) {
			ui_msgs::display_error(_("The invoice cannot be processed because the there are no items or values on the invoice.  Invoices are expected to have a charge."));
			return false;
		}
		if (!Refs::is_valid($_SESSION['supp_trans']->reference)) {
			ui_msgs::display_error(_("You must enter an invoice reference."));
			ui_view::set_focus('reference');
			return false;
		}
		while (!is_new_reference($_SESSION['supp_trans']->reference, ST_SUPPINVOICE)) {
			//ui_msgs::display_error(_("The entered reference is already in use."));
			//ui_view::set_focus('reference');
			//return false;
			$_SESSION['supp_trans']->reference = Refs::get_next(ST_SUPPINVOICE);
		}
		if (!Refs::is_valid($_SESSION['supp_trans']->supp_reference)) {
			ui_msgs::display_error(_("You must enter a supplier's invoice reference."));
			ui_view::set_focus('supp_reference');
			return false;
		}
		if (!Dates::is_date($_SESSION['supp_trans']->tran_date)) {
			ui_msgs::display_error(_("The invoice as entered cannot be processed because the invoice date is in an incorrect format."));
			ui_view::set_focus('trans_date');
			return false;
		}
		elseif (!Dates::is_date_in_fiscalyear($_SESSION['supp_trans']->tran_date)) {
			ui_msgs::display_error(_("The entered date is not in fiscal year."));
			ui_view::set_focus('trans_date');
			return false;
		}
		if (!Dates::is_date($_SESSION['supp_trans']->due_date)) {
			ui_msgs::display_error(_("The invoice as entered cannot be processed because the due date is in an incorrect format."));
			ui_view::set_focus('due_date');
			return false;
		}
		$sql = "SELECT Count(*) FROM supp_trans WHERE supplier_id=" . DBOld::escape($_SESSION['supp_trans']->supplier_id) . " AND supp_reference=" . DBOld::escape($_POST['supp_reference']) . " AND ov_amount!=0"; // ignore voided invoice references
		$result = DBOld::query($sql, "The sql to check for the previous entry of the same invoice failed");
		$myrow  = DBOld::fetch_row($result);
		if ($myrow[0] == 1) { /*Transaction reference already entered */
			ui_msgs::display_error(_("This invoice number has already been entered. It cannot be entered again." . " (" . $_POST['supp_reference'] . ")"));
			return false;
		}
		return true;
	}

	//--------------------------------------------------------------------------------------------------
	function handle_commit_invoice() {
		copy_to_trans($_SESSION['supp_trans']);
		if (!check_data()) {
			return;
		}
		if (get_post('ChgTax', 0) != 0) {
			$taxexists = false;
			foreach ($_SESSION['supp_trans']->gl_codes as &$gl_item) {
				if ($gl_item->gl_code == 2430) {
					$taxexists = true;
					$gl_item->amount += get_post('ChgTax');
					break;
				}
			}
			if (!$taxexists) {
				$_SESSION['supp_trans']->add_gl_codes_to_trans(2430, 'GST Paid', 0, 0, get_post('ChgTax'), 'GST Correction');
			}
		}
		if (get_post('ChgTotal', 0) != 0) {
			$_SESSION['supp_trans']->add_gl_codes_to_trans(DB_Company::get_pref('default_cogs_act'), 'Cost of Goods Sold', 0, 0, get_post('ChgTotal'), 'Rounding Correction');
		}
		$invoice_no = add_supp_invoice($_SESSION['supp_trans']);
		$_SESSION['supp_trans']->clear_items();
		unset($_SESSION['supp_trans']);
		meta_forward($_SERVER['PHP_SELF'], "AddedID=$invoice_no");
	}

	//--------------------------------------------------------------------------------------------------
	if (isset($_POST['PostInvoice'])) {
		handle_commit_invoice();
	}
	function check_item_data($n) {

		if (!check_num('this_quantity_inv' . $n, 0) || input_num('this_quantity_inv' . $n) == 0) {
			ui_msgs::display_error(_("The quantity to invoice must be numeric and greater than zero."));
			ui_view::set_focus('this_quantity_inv' . $n);
			return false;
		}
		if (!check_num('ChgPrice' . $n)) {
			ui_msgs::display_error(_("The price is not numeric."));
			ui_view::set_focus('ChgPrice' . $n);
			return false;
		}
		if (!check_num('ExpPrice' . $n)) {
			ui_msgs::display_error(_("The price is not numeric."));
			ui_view::set_focus('ExpPrice' . $n);
			return false;
		}
		$margin = SysPrefs::over_charge_allowance();
		if (Config::get('valid.charged_to_delivered.price') == True && $margin != 0) {
			if ($_POST['order_price' . $n] != input_num('ChgPrice' . $n)) {
				if ($_POST['order_price' . $n] == 0 || input_num('ChgPrice' . $n) / $_POST['order_price' . $n] > (1 + ($margin / 100))) {
					if ($_SESSION['err_over_charge'] != true) {
						ui_msgs::display_error(_("The price being invoiced is more than the purchase order price by more than the allowed over-charge percentage. The system is set up to prohibit this. See the system administrator to modify the set up parameters if necessary.") . _("The over-charge percentage allowance is :") . $margin . "%");
						ui_view::set_focus('ChgPrice' . $n);
						$_SESSION['err_over_charge'] = true;
						return false;
					}
					else {
						$_SESSION['err_over_charge'] = false;
					}
				}
			}
		}
		if (Config::get('valid.charged_to_delivered.qty') == True) {
			if (input_num('this_quantity_inv' . $n) / ($_POST['qty_recd' . $n] - $_POST['prev_quantity_inv' . $n]) > (1 + ($margin / 100))) {
				ui_msgs::display_error(_("The quantity being invoiced is more than the outstanding quantity by more than the allowed over-charge percentage. The system is set up to prohibit this. See the system administrator to modify the set up parameters if necessary.") . _("The over-charge percentage allowance is :") . $margin . "%");
				ui_view::set_focus('this_quantity_inv' . $n);
				return false;
			}
		}
		return true;
	}

	function commit_item_data($n) {
		if (check_item_data($n)) {
			if (input_num('this_quantity_inv' . $n) >= ($_POST['qty_recd' . $n] - $_POST['prev_quantity_inv' . $n])) {
				$complete = true;
			}
			else {
				$complete = false;
			}
			$_SESSION['err_over_charge'] = false;
			$_SESSION['supp_trans']->add_grn_to_trans($n, $_POST['po_detail_item' . $n], $_POST['item_code' . $n], $_POST['description' . $n], $_POST['qty_recd' . $n],
				$_POST['prev_quantity_inv' . $n], input_num('this_quantity_inv' . $n), $_POST['order_price' . $n], input_num('ChgPrice' . $n),
				$complete, $_POST['std_cost_unit' . $n], "", input_num('ChgDiscount' . $n), input_num('ExpPrice' . $n));
		}
	}

	//-----------------------------------------------------------------------------------------
	$id = find_submit('grn_item_id');
	if ($id != -1) {
		commit_item_data($id);
	}
	if (isset($_POST['InvGRNAll'])) {
		foreach ($_POST as $postkey => $postval) {
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
		$_SESSION['supp_trans']->remove_grn_from_trans($id3);
		$Ajax->activate('grn_items');
		$Ajax->activate('inv_tot');
	}
	$id4 = find_submit('Delete2');
	if ($id4 != -1) {
		if (!isset($taxtotal)) {
			$taxtotal = 0;
		}
		$_SESSION['supp_trans']->remove_gl_codes_from_trans($id4);
		foreach ($_SESSION['supp_trans']->gl_codes as $key => $gl_item) {
			if ($gl_item->gl_code == 2430) {
				$taxrecord = $key;
				continue;
			}
			$taxtotal += $gl_item->amount;
		}
		if (!is_null($taxrecord)) {
			$_SESSION['supp_trans']->gl_codes[$taxrecord]->amount = $taxtotal * .1;
		}
		clear_fields();
		$Ajax->activate('gl_items');
		$Ajax->activate('inv_tot');
	}
	$id2 = -1;
	if ($_SESSION["wa_current_user"]->can_access('SA_GRNDELETE')) {
		$id2 = find_submit('void_item_id');
		if ($id2 != -1) {
			DBOld::begin_transaction();
			$myrow = get_grn_item_detail($id2);
			$grn   = get_grn_batch($myrow['grn_batch_id']);
			$sql   = "UPDATE purch_order_details
			SET quantity_received = qty_invoiced, quantity_ordered = qty_invoiced WHERE po_detail_item = " . $myrow["po_detail_item"];
			DBOld::query($sql, "The quantity invoiced of the purchase order line could not be updated");
			$sql = "UPDATE grn_items
	    	SET qty_recd = quantity_inv WHERE id = " . $myrow["id"];
			DBOld::query($sql, "The quantity invoiced off the items received record could not be updated");
			update_average_material_cost($grn["supplier_id"], $myrow["item_code"], $myrow["unit_price"], -$myrow["QtyOstdg"], Dates::Today());
			add_stock_move(ST_SUPPRECEIVE, $myrow["item_code"], $myrow['grn_batch_id'], $grn['loc_code'], Dates::sql2date($grn["delivery_date"]), "", -$myrow["QtyOstdg"],
				$myrow['std_cost_unit'], $grn["supplier_id"], 1, $myrow['unit_price']);
			DBOld::commit_transaction();
			ui_msgs::display_notification(sprintf(_('All yet non-invoiced items on delivery line # %d has been removed.'), $id2));
		}
	}
	if (isset($_POST['go'])) {
		$Ajax->activate('gl_items');
		ui_view::display_quick_entries($_SESSION['supp_trans'], $_POST['qid'], input_num('totamount'), QE_SUPPINV);
		$_POST['totamount'] = price_format(0);
		$Ajax->activate('totamount');
		$Ajax->activate('inv_tot');
	}
	start_form();
	invoice_header($_SESSION['supp_trans']);
	if ($_SESSION['wa_global_supplier_id']) {
		$_POST['supplier_id'] = $_SESSION['wa_global_supplier_id'];
		if ($_SESSION['supp_trans']) {
			unset($_SESSION['wa_global_supplier_id']);
			unset($_SESSION['delivery_po']);
		}
	}
	if ($_POST['supplier_id'] == '') {
		ui_msgs::display_error(_("There is no supplier selected."));
	}
	else {
		display_grn_items($_SESSION['supp_trans'], 1);
		display_gl_items($_SESSION['supp_trans'], 1);
		div_start('inv_tot');
		invoice_totals($_SESSION['supp_trans']);
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

	JS::onload(<<<JS
   $("#wrapper").delegate('.amount','change',function() {
      var feild = $(this), feilds = $(this).parent().parent(), fv = {}, nodes = {
         qty: $('[name^="this_quantity"]',feilds),
         price: $('[name^="ChgPrice"]',feilds),
         discount: $('[name^="ChgDiscount"]',feilds),
         total: $('[id^="ChgTotal"]',feilds),
					eachprice: $('[id^="Ea"]',feilds)
      };
      $.each(nodes,function(k,v) {
         fv[k] = Number(v.val().replace(',',''));
      });
      if (feild.attr('id') == nodes.total.attr('id')) {
         if (fv.price == 0 && fv.discount==0) {
            fv.discount = fv.total / fv.qty;
         } else {
            fv.discount = 100*(1-(fv.total)/(fv.price*fv.qty));
         }
         fv.discount = Math.round(fv.discount*1)/1
         nodes.discount.val(fv.discount);
      } else if (fv.qty > 0 && fv.price > 0) {
         fv.total = fv.qty*fv.price*((100-fv.discount)/100);
         nodes.total.val(Math.round(fv.total*100)/100 );
       };
       console.log(nodes);
       price_format(nodes.eachprice.attr('id'),(fv.total/fv.qty),2,true);

});
JS
	);

	end_page();
?>
