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

	//
	//	Entry/Modify Credit Note for selected Sales Invoice
	//
	$page_security = 'SA_SALESCREDITINV';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	JS::open_window(900, 500);
	if (isset($_GET['ModifyCredit'])) {
		$_SESSION['page_title'] = sprintf(_("Modifying Credit Invoice # %d."), $_GET['ModifyCredit']);
		$help_context = "Modifying Credit Invoice";
		Sales_Order::start();
	} elseif (isset($_GET['InvoiceNumber'])) {
		$page_title = _($help_context = "Credit all or part of an Invoice");
		Sales_Order::start();
	}
	Page::start($page_title);

	if (isset($_GET['AddedID'])) {
		$credit_no = $_GET['AddedID'];
		$trans_type = ST_CUSTCREDIT;
		Errors::notice(_("Credit Note has been processed"));
		Display::note(Debtor_UI::trans_view($trans_type, $credit_no, _("&View This Credit Note")), 0, 0);
		Display::note(Reporting::print_doc_link($credit_no, _("&Print This Credit Note"), true, $trans_type), 1);
		Display::note(GL_UI::view($trans_type, $credit_no, _("View the GL &Journal Entries for this Credit Note")), 1);
		Page::footer_exit();
	} elseif (isset($_GET['UpdatedID'])) {
		$credit_no = $_GET['UpdatedID'];
		$trans_type = ST_CUSTCREDIT;
		Errors::notice(_("Credit Note has been updated"));
		Display::note(Debtor_UI::trans_view($trans_type, $credit_no, _("&View This Credit Note")), 0, 0);
		Display::note(Reporting::print_doc_link($credit_no, _("&Print This Credit Note"), true, $trans_type), 1);
		Display::note(GL_UI::view($trans_type, $credit_no, _("View the GL &Journal Entries for this Credit Note")), 1);
		Page::footer_exit();
	} else {
		Sales_Order::check_edit_conflicts();
	}

	function can_process()
		{
			if (!Dates::is_date($_POST['CreditDate'])) {
				Errors::error(_("The entered date is invalid."));
				;
				JS::set_focus('CreditDate');
				return false;
			} elseif (!Dates::is_date_in_fiscalyear($_POST['CreditDate'])) {
				Errors::error(_("The entered date is not in fiscal year."));
				JS::set_focus('CreditDate');
				return false;
			}
			if ($_SESSION['Items']->trans_no == 0) {
				if (!Ref::is_valid($_POST['ref'])) {
					Errors::error(_("You must enter a reference."));
					;
					JS::set_focus('ref');
					return false;
				}
				if (!Ref::is_new($_POST['ref'], ST_CUSTCREDIT)) {
					Errors::error(_("The entered reference is already in use."));
					;
					JS::set_focus('ref');
					return false;
				}
			}
			if (!Validation::is_num('ChargeFreightCost', 0)) {
				Errors::error(_("The entered shipping cost is invalid or less than zero."));
				;
				JS::set_focus('ChargeFreightCost');
				return false;
			}
			if (!check_quantities()) {
				Errors::error(_("Selected quantity cannot be less than zero nor more than quantity not credited yet."));
				return false;
			}
			return true;
		}


	if (isset($_GET['InvoiceNumber']) && $_GET['InvoiceNumber'] > 0) {
		$ci = new Sales_Order(ST_SALESINVOICE, $_GET['InvoiceNumber'], true);
		$ci->trans_type = ST_CUSTCREDIT;
		$ci->src_docs = $ci->trans_no;
		$ci->src_date = $ci->document_date;
		$ci->trans_no = 0;
		$ci->document_date = Dates::new_doc_date();
		$ci->reference = Ref::get_next(ST_CUSTCREDIT);
		for ($line_no = 0; $line_no < count($ci->line_items); $line_no++) {
			$ci->line_items[$line_no]->qty_dispatched = '0';
		}
		$_SESSION['Items'] = $ci;
		copy_from_cart();
	} elseif (isset($_GET['ModifyCredit']) && $_GET['ModifyCredit'] > 0) {
		$_SESSION['Items'] = new Sales_Order(ST_CUSTCREDIT, $_GET['ModifyCredit']);
		copy_from_cart();
	} elseif (!Sales_Order::active()) {
		/* This page can only be called with an invoice number for crediting*/
		die (_("This page can only be opened if an invoice has been selected for crediting."));
	} elseif (!check_quantities()) {
		Errors::error(_("Selected quantity cannot be less than zero nor more than quantity not credited yet."));
	}
	function check_quantities()
		{
			$ok = 1;
			foreach ($_SESSION['Items']->line_items as $line_no => $itm) {
				if ($itm->quantity == $itm->qty_done) {
					continue; // this line was fully credited/removed
				}
				if (isset($_POST['Line' . $line_no])) {
					if (Validation::is_num('Line' . $line_no, 0, $itm->quantity)) {
						$_SESSION['Items']->line_items[$line_no]->qty_dispatched = Validation::input_num('Line' . $line_no);
					}
				} else {
					$ok = 0;
				}
				if (isset($_POST['Line' . $line_no . 'Desc'])) {
					$line_desc = $_POST['Line' . $line_no . 'Desc'];
					if (strlen($line_desc) > 0) {
						$_SESSION['Items']->line_items[$line_no]->description = $line_desc;
					}
				}
			}
			return $ok;
		}


	function copy_to_cart()
		{
			$cart = &$_SESSION['Items'];
			$cart->ship_via = $_POST['ShipperID'];
			$cart->freight_cost = Validation::input_num('ChargeFreightCost');
			$cart->document_date = $_POST['CreditDate'];
			$cart->Location = $_POST['Location'];
			$cart->Comments = $_POST['CreditText'];
			if ($_SESSION['Items']->trans_no == 0) {
				$cart->reference = $_POST['ref'];
			}
		}


	function copy_from_cart()
		{
			$cart = &$_SESSION['Items'];
			$_POST['ShipperID'] = $cart->ship_via;
			$_POST['ChargeFreightCost'] = Num::price_format($cart->freight_cost);
			$_POST['CreditDate'] = $cart->document_date;
			$_POST['Location'] = $cart->Location;
			$_POST['CreditText'] = $cart->Comments;
			$_POST['cart_id'] = $cart->cart_id;
			$_POST['ref'] = $cart->reference;
		}


	if (isset($_POST['ProcessCredit']) && can_process()) {
		$new_credit = ($_SESSION['Items']->trans_no == 0);
		if (!isset($_POST['WriteOffGLCode'])) {
			$_POST['WriteOffGLCode'] = 0;
		}
		copy_to_cart();
		if ($new_credit) {
			Dates::new_doc_date($_SESSION['Items']->document_date);
		}
		$credit_no = $_SESSION['Items']->write($_POST['WriteOffGLCode']);
		Sales_Order::finish();
		if ($new_credit) {
			Display::meta_forward($_SERVER['PHP_SELF'], "AddedID=$credit_no");
		} else {
			Display::meta_forward($_SERVER['PHP_SELF'], "UpdatedID=$credit_no");
		}
	}

	if (isset($_POST['Location'])) {
		$_SESSION['Items']->Location = $_POST['Location'];
	}

	function display_credit_items()
		{
			Display::start_form();
			hidden('cart_id');
			Display::start_table('tablestyle2 width90 pad5');
			echo "<tr><td>"; // outer table
			Display::start_table('tablestyle width100');
			Display::start_row();
			label_cells(_("Customer"), $_SESSION['Items']->customer_name, "class='tableheader2'");
			label_cells(_("Branch"), Sales_Branch::get_name($_SESSION['Items']->Branch), "class='tableheader2'");
			label_cells(_("Currency"), $_SESSION['Items']->customer_currency, "class='tableheader2'");
			Display::end_row();
			Display::start_row();
			//	if (!isset($_POST['ref']))
			//		$_POST['ref'] = Ref::get_next(11);
			if ($_SESSION['Items']->trans_no == 0) {
				ref_cells(_("Reference"), 'ref', '', null, "class='tableheader2'");
			} else {
				label_cells(_("Reference"), $_SESSION['Items']->reference, "class='tableheader2'");
			}
			label_cells(_("Crediting Invoice"),
				Debtor_UI::trans_view(ST_SALESINVOICE, array_keys($_SESSION['Items']->src_docs)), "class='tableheader2'");
			if (!isset($_POST['ShipperID'])) {
				$_POST['ShipperID'] = $_SESSION['Items']->ship_via;
			}
			label_cell(_("Shipping Company"), "class='tableheader2'");
			Sales_UI::shippers_cells(null, 'ShipperID', $_POST['ShipperID']);
			//	if (!isset($_POST['sales_type_id']))
			//	  $_POST['sales_type_id'] = $_SESSION['Items']->sales_type;
			//	label_cell(_("Sales Type"), "class='tableheader2'");
			//	Sales_UI::types_cells(null, 'sales_type_id', $_POST['sales_type_id']);
			Display::end_row();
			Display::end_table();
			echo "</td><td>"; // outer table
			Display::start_table('tablestyle width100');
			label_row(_("Invoice Date"), $_SESSION['Items']->src_date, "class='tableheader2'");
			date_row(_("Credit Note Date"), 'CreditDate', '', $_SESSION['Items']->trans_no == 0, 0, 0, 0, "class='tableheader2'");
			Display::end_table();
			echo "</td></tr>";
			Display::end_table(1); // outer table
			Display::div_start('credit_items');
			Display::start_table('tablestyle width90');
			$th = array(
				_("Item Code"), _("Item Description"), _("Invoiced Quantity"), _("Units"), _("Credit Quantity"), _("Price"), _("Discount %"), _("Total"));
			Display::table_header($th);
			$k = 0; //row colour counter
			foreach ($_SESSION['Items']->line_items as $line_no => $ln_itm) {
				if ($ln_itm->quantity == $ln_itm->qty_done) {
					continue; // this line was fully credited/removed
				}
				Display::alt_table_row_color($k);
				//	stock_status_cell($ln_itm->stock_id); alternative view
				label_cell($ln_itm->stock_id);
				text_cells(null, 'Line' . $line_no . 'Desc', $ln_itm->description, 30, 50);
				$dec = Item::qty_dec($ln_itm->stock_id);
				qty_cell($ln_itm->quantity, false, $dec);
				label_cell($ln_itm->units);
				amount_cells(null, 'Line' . $line_no, Num::format($ln_itm->qty_dispatched, $dec), null, null, $dec);
				$line_total = ($ln_itm->qty_dispatched * $ln_itm->price * (1 - $ln_itm->discount_percent));
				amount_cell($ln_itm->price);
				percent_cell($ln_itm->discount_percent * 100);
				amount_cell($line_total);
				Display::end_row();
			}
			if (!Validation::is_num('ChargeFreightCost')) {
				$_POST['ChargeFreightCost'] = Num::price_format($_SESSION['Items']->freight_cost);
			}
			$colspan = 7;
			Display::start_row();
			label_cell(_("Credit Shipping Cost"), "colspan=$colspan style='text-align:right;'");
			small_amount_cells(null, "ChargeFreightCost", Num::price_format(Display::get_post('ChargeFreightCost', 0)));
			Display::end_row();
			$inv_items_total = $_SESSION['Items']->get_items_total_dispatch();
			$display_sub_total = Num::price_format($inv_items_total + Validation::input_num('ChargeFreightCost'));
			label_row(_("Sub-total"), $display_sub_total, "colspan=$colspan style='text-align:right;'", "class=right");
			$taxes = $_SESSION['Items']->get_taxes(Validation::input_num('ChargeFreightCost'));
			$tax_total = Taxes::edit_items($taxes, $colspan, $_SESSION['Items']->tax_included);
			$display_total = Num::price_format(($inv_items_total + Validation::input_num('ChargeFreightCost') + $tax_total));
			label_row(_("Credit Note Total"), $display_total, "colspan=$colspan style='text-align:right;'", "class=right");
			Display::end_table();
			Display::div_end();
		}


	function display_credit_options()
		{
			$Ajax = Ajax::i();
			echo "<br>";
			if (isset($_POST['_CreditType_update'])) {
				$Ajax->activate('options');
			}
			Display::div_start('options');
			Display::start_table('tablestyle2');
			Sales_Credit::row(_("Credit Note Type"), 'CreditType', null, true);
			if ($_POST['CreditType'] == "Return") {
				/*if the credit note is a return of goods then need to know which location to receive them into */
				if (!isset($_POST['Location'])) {
					$_POST['Location'] = $_SESSION['Items']->Location;
				}
				locations_list_row(_("Items Returned to Location"), 'Location', $_POST['Location']);
			} else {
				/* the goods are to be written off to somewhere */
				GL_UI::all_row(_("Write off the cost of the items to"), 'WriteOffGLCode', null);
			}
			textarea_row(_("Memo"), "CreditText", null, 51, 3);
			echo "</table>";
			Display::div_end();
		}


	if (Display::get_post('Update')) {
		$Ajax->activate('credit_items');
	}

	display_credit_items();
	display_credit_options();
	echo "<br><div class='center'>";
	submit('Update', _("Update"), true, _('Update credit value for quantities entered'), true);
	echo "&nbsp";
	submit('ProcessCredit', _("Process Credit Note"), true, '', 'default');
	echo "</div>";
	Display::end_form();
	end_page();

?>
