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
	JS::open_window(900, 500);
	Page::start(_($help_context = "Receive Purchase Order Items"), SA_GRN);
	if (isset($_GET[ADDED_ID])) {
		$grn = $_GET[ADDED_ID];
		$trans_type = ST_SUPPRECEIVE;
		Event::success(_("Purchase Order Delivery has been processed"));
		Display::note(GL_UI::trans_view($trans_type, $grn, _("&View this Delivery")));
		Display::link_params("/purchases/supplier_invoice.php", _("Entry purchase &invoice for this receival"), "New=1");
		Display::link_no_params("/purchases/inquiry/po_search.php", _("Select a different &purchase order for receiving items against"));
		Page::footer_exit();
	}
	$order = Orders::session_get() ? : null;
	if (isset($_GET['PONumber']) && $_GET['PONumber'] > 0 && !isset($_POST['Update'])) {
		$order = new Purch_Order($_GET['PONumber']);
	}
	elseif ((!isset($_GET['PONumber']) || $_GET['PONumber'] == 0) && !isset($_POST['order_id'])) {
		Event::error(_("This page can only be opened if a purchase order has been selected. Please select a purchase order first."));
		Page::footer_exit();
	}
	$order = Purch_Order::check_edit_conflicts($order);
	$_POST['order_id'] = $order->order_id;
	Orders::session_set($order);
	/*read in all the selected order into the Items order */
	if (isset($_POST['Update']) || isset($_POST['ProcessGoodsReceived'])) {
		/* if update quantities button is hit page has been called and ${$line->line_no} would have be
								set from the post to the quantity to be received in this receival*/
		foreach ($order->line_items as $line) {
			if (($line->quantity - $line->qty_received) > 0) {
				$_POST[$line->line_no] = max($_POST[$line->line_no], 0);
				if (!Validation::is_num($line->line_no)) {
					$_POST[$line->line_no] = Num::format(0, Item::qty_dec($line->stock_id));
				}
				if (!isset($_POST['DefaultReceivedDate']) || $_POST['DefaultReceivedDate'] == "") {
					$_POST['DefaultReceivedDate'] = Dates::new_doc_date();
				}
				$order->line_items[$line->line_no]->receive_qty = Validation::input_num($line->line_no);
				if (isset($_POST[$line->stock_id . "Desc"]) && strlen($_POST[$line->stock_id . "Desc"]) > 0) {
					$order->line_items[$line->line_no]->description = $_POST[$line->stock_id . "Desc"];
				}
			}
		}
		Ajax::i()->activate('grn_items');
	}
	if (isset($_POST['ProcessGoodsReceived'])) {
		process_receive_po($order);
	}
	start_form();
	hidden('order_id');
	Purch_GRN::display($order, true);
	Display::heading(_("Items to Receive"));
	display_po_receive_items($order);
	Display::link_params("/purchases/po_entry_items.php", _("Edit This Purchase Order"), "ModifyOrder=" . $order->order_no);
	echo '<br>';
	submit_center_first('Update', _("Update Totals"), '', true);
	submit_center_last('ProcessGoodsReceived', _("Process Receive Items"), _("Clear all GL entry fields"), 'default');
	end_form();
	Page::end();
	function display_po_receive_items($order) {
		Display::div_start('grn_items');
		start_table('tablestyle width90');
		$th = array(
			_("Item Code"), _("Description"), _("Ordered"), _("Units"), _("Received"), _("Outstanding"), _("This Delivery"), _("Price"), _('Discount %'), _("Total")
		);
		table_header($th);
		/*show the line items on the order with the quantity being received for modification */
		$total = 0;
		$k = 0; //row colour counter
		if (count($order->line_items) > 0) {
			foreach ($order->line_items as $line) {
				alt_table_row_color($k);
				$qty_outstanding = $line->quantity - $line->qty_received;
				if (!isset($_POST['Update']) && !isset($_POST['ProcessGoodsReceived']) && $line->receive_qty == 0) { //If no quantites yet input default the balance to be received
					$line->receive_qty = $qty_outstanding;
				}
				$line_total = ($line->receive_qty * $line->price * (1 - $line->discount));
				$total += $line_total;
				label_cell($line->stock_id);
				if ($qty_outstanding > 0) {
					text_cells(null, $line->stock_id . "Desc", $line->description, 30, 50);
				}
				else {
					label_cell($line->description);
				}
				$dec = Item::qty_dec($line->stock_id);
				qty_cell($line->quantity, false, $dec);
				label_cell($line->units);
				qty_cell($line->qty_received, false, $dec);
				qty_cell($qty_outstanding, false, $dec);
				if ($qty_outstanding > 0) {
					qty_cells(null, $line->line_no, Num::format($line->receive_qty, $dec), "class='right'", null, $dec);
				}
				else {
					label_cell(Num::format($line->receive_qty, $dec), "class='right'");
				}
				amount_decimal_cell($line->price);
				percent_cell($line->discount * 100);
				amount_cell($line_total);
				end_row();
			}
		}
		label_cell(_("Freight"), "colspan=9 class='right'");
		small_amount_cells(null, 'freight', Num::price_format($order->freight));
		$display_total = Num::format($total + $_POST['freight'], User::price_dec());
		label_row(_("Total value of items received"), $display_total, "colspan=9 class='right'", ' class="right nowrap"');
		end_table();
		Display::div_end();
	}

	function check_po_changed($order) {
		/*Now need to check that the order details are the same as they were when they were read into the Items array. If they've changed then someone else must have altered them */
		// Sherifoz 22.06.03 Compare against COMPLETED items only !!
		// Otherwise if you try to fullfill item quantities separately will give error.
		$sql = "SELECT item_code, quantity_ordered, quantity_received, qty_invoiced
			FROM purch_order_details
			WHERE order_no=" . DB::escape($order->order_no) . " ORDER BY po_detail_item";
		$result = DB::query($sql, "could not query purch order details");
		$line_no = 1;
		while ($myrow = DB::fetch($result)) {
			$ln_item = $order->line_items[$line_no];
			// only compare against items that are outstanding
			$qty_outstanding = $ln_item->quantity - $ln_item->qty_received;
			if ($qty_outstanding > 0) {
				if ($ln_item->qty_inv != $myrow["qty_invoiced"] || $ln_item->stock_id != $myrow["item_code"] || $ln_item->quantity != $myrow["quantity_ordered"] || $ln_item->qty_received != $myrow["quantity_received"]
				) {
					return true;
				}
			}
			$line_no++;
		} /*loop through all line items of the order to ensure none have been invoiced */
		return false;
	}

	function can_process($order) {
		if (count($order->line_items) <= 0) {
			Event::error(_("You are not currenty receiving an order."));
			Display::link_no_params("/purchases/inquiry/po_search.php", _("Select a purchase order to receive goods."));
			Page::footer_exit();
		}
		if (!Dates::is_date($_POST['DefaultReceivedDate'])) {
			Event::error(_("The entered date is invalid."));
			JS::set_focus('DefaultReceivedDate');
			return false;
		}
		if (!Validation::is_num('freight', 0)) {
			Event::error(_("The freight entered must be numeric and not less than zero."));
			JS::set_focus('freight');
			return false;
		}
		if (!Ref::is_valid($_POST['ref'])) {
			Event::error(_("You must enter a reference."));
			JS::set_focus('ref');
			return false;
		}
		if (!Ref::is_new($_POST['ref'], ST_SUPPRECEIVE)) {
			$_POST['ref'] = Ref::get_next(ST_SUPPRECEIVE);
		}
		$something_received = 0;
		foreach ($order->line_items as $order_line) {
			if ($order_line->receive_qty > 0) {
				$something_received = 1;
				break;
			}
		}
		// Check whether trying to deliver more items than are recorded on the actual purchase order (+ overreceive allowance)
		$delivery_qty_too_large = 0;
		foreach ($order->line_items as $order_line) {
			if ($order_line->receive_qty + $order_line->qty_received > $order_line->quantity * (1 + (DB_Company::get_pref('po_over_receive') / 100))) {
				$delivery_qty_too_large = 1;
				break;
			}
		}
		if ($something_received == 0) { /*Then dont bother proceeding cos nothing to do ! */
			Event::error(_("There is nothing to process. Please enter valid quantities greater than zero."));
			return false;
		}
		elseif ($delivery_qty_too_large == 1) {
			Event::error(_("Entered quantities cannot be greater than the quantity entered on the purchase order including the allowed over-receive percentage") . " (" . DB_Company::get_pref('po_over_receive') . "%).<br>" . _("Modify the ordered items on the purchase order if you wish to increase the quantities."));
			return false;
		}
		return true;
	}

	function process_receive_po($order) {
		if (!can_process($order)) {
			return;
		}
		if (check_po_changed($order)) {
			Event::error(_("This order has been changed or invoiced since this delivery was started to be actioned. Processing halted. To enter a delivery against this purchase order, it must be re-selected and re-read again to update the changes made by the other user."));
			Display::link_no_params("/purchases/inquiry/po_search.php", _("Select a different purchase order for receiving goods against"));
			Display::link_params("/purchases/po_receive_items.php", _("Re-Read the updated purchase order for receiving goods against"), "PONumber=" . $order->order_no);
			unset($order->line_items);
			unset($order);
			unset($_POST['ProcessGoodsReceived']);
			Ajax::i()->activate('_page_body');
			Page::footer_exit();
		}
		$_SESSION['supplier_id'] = $order->supplier_id;
		$grn = Purch_GRN::add($order, $_POST['DefaultReceivedDate'], $_POST['ref'], $_POST['Location']);
		$_SESSION['delivery_po'] = $order->order_no;
		Dates::new_doc_date($_POST['DefaultReceivedDate']);
		unset($order->line_items);
		unset($order);
		Display::meta_forward($_SERVER['PHP_SELF'], "AddedID=$grn");
	}

?>
