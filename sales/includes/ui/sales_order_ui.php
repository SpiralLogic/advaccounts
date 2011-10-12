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

	//--------------------------------------------------------------------------------
	function add_to_order(&$order, $new_item, $new_item_qty, $price, $discount, $description, $no_errors = false) {
		// calculate item price to sum of kit element prices factor for
		// value distribution over all exploded kit items
		$item = is_item_kit($new_item);
		if (DBOld::num_rows($item) == 1) {
			$item = DBOld::fetch($item);
			if (!$item['is_foreign'] && $item['item_code'] == $item['stock_id']) {
				foreach ($order->line_items as $order_item) {
					if (strcasecmp($order_item->stock_id, $item['stock_id']) == 0 && !$no_errors) {
						ui_msgs::display_warning(_("For Part :") . $item['stock_id'] . " " . _("This item is already on this document. You have been warned."));
						break;
					}
				}
				$order->add_to_cart(count($order->line_items), $item['stock_id'], $new_item_qty * $item['quantity'], $price, $discount, 0, 0, $description);
				return;
			}
		}
		$std_price = get_kit_price($new_item, $order->customer_currency, $order->sales_type, $order->price_factor, get_post('OrderDate'), true);
		if ($std_price == 0) {
			$price_factor = 0;
		}
		else {
			$price_factor = $price / $std_price;
		}
		$kit = get_item_kit($new_item);
		$item_num = DBOld::num_rows($kit);
		while ($item = DBOld::fetch($kit)) {
			$std_price = get_kit_price($item['stock_id'], $order->customer_currency, $order->sales_type, $order->price_factor, get_post('OrderDate'), true);
			// rounding differences are included in last price item in kit
			$item_num--;
			if ($item_num) {
				$price -= $item['quantity'] * $std_price * $price_factor;
				$item_price = $std_price * $price_factor;
			}
			else {
				if ($item['quantity']) {
					$price = $price / $item['quantity'];
				}
				$item_price = $price;
			}
			$item_price = round($item_price, user_price_dec());
			if (!$item['is_foreign'] && $item['item_code'] != $item['stock_id']) { // this is sales kit - recurse
				add_to_order($order, $item['stock_id'], $new_item_qty * $item['quantity'], $item_price, $discount, $std_price);
			}
			else { // stock item record eventually with foreign code
				// check duplicate stock item
				foreach ($order->line_items as $order_item) {
					if (strcasecmp($order_item->stock_id, $item['stock_id']) == 0) {
						ui_msgs::display_warning(_("For Part :") . $item['stock_id'] . " " . _("This item is already on this document. You have been warned."));
						break;
					}
				}
				$order->add_to_cart(count($order->line_items), $item['stock_id'], $new_item_qty * $item['quantity'], $item_price, $discount);
			}
		}
	}

	//---------------------------------------------------------------------------------
	function get_customer_details_to_order(&$order, $customer_id, $branch_id) {
		$ret_error = "";
		$myrow = get_customer_to_order($customer_id);
		$name = $myrow['name'];
		if ($myrow['dissallow_invoices'] == 1) {
			$ret_error = _("The selected customer account is currently on hold. Please contact the credit control personnel to discuss.");
		}
		$deliver = $myrow['address']; // in case no branch address use company address
		$order->set_customer($customer_id, $name, $myrow['curr_code'],
			$myrow['discount'], $myrow['payment_terms'], $myrow['pymt_discount']); // the sales type determines the price list to be used by default
		$order->set_sales_type($myrow['salestype'], $myrow['sales_type'], $myrow['tax_included'], $myrow['factor']);
		if ($order->trans_type != ST_SALESORDER && $order->trans_type != ST_SALESQUOTE) {
			$order->dimension_id = $myrow['dimension_id'];
			$order->dimension2_id = $myrow['dimension2_id'];
		}
		$result = get_branch_to_order($customer_id, $branch_id);
		if (DBOld::num_rows($result) == 0) {

			return _("The selected customer and branch are not valid, or the customer does not have any branches.");
		}
		$myrow = DBOld::fetch($result);

		$order->set_branch($branch_id, $myrow["tax_group_id"], $myrow["tax_group_name"], $myrow["phone"], $myrow["email"]);
		//$address = trim($myrow["br_post_address"]) != '' ? $myrow["br_post_address"] : (trim($myrow["br_address"]) != '' ?		$myrow["br_address"] : $deliver);
		$address = $myrow['br_address'] . "\n";
		if ($myrow['city']) {
			$address .= $myrow['city'];
		}
		if ($myrow['state']) {
			$address .= ", " . strtoupper($myrow['state']);
		}
		if ($myrow['postcode']) {
			$address .= ", " . $myrow['postcode'];
		}
		$order->set_delivery($myrow["default_ship_via"], $name, $address);
		if ($order->trans_type == ST_SALESINVOICE) {
			$order->due_date = get_invoice_duedate($customer_id, $order->document_date);
			if ($order->pos != -1) {
				$order->cash = Dates::date_diff2($order->due_date, Dates::Today(), 'd') < 2;
			}
			if ($order->due_date == Dates::Today()) {
				$order->pos == -1;
			}
		}
		if ($order->cash) {
			if ($order->pos != -1) {
				$paym = get_sales_point($order->pos);
				$order->set_location($paym["pos_location"], $paym["location_name"]);
			}
		}
		else {
			$order->set_location($myrow["default_location"], $myrow["location_name"]);
		}
		return $ret_error;
	}

	//---------------------------------------------------------------------------------
	function display_order_summary($title, &$order, $editable_items = false) {

		ui_msgs::display_heading($title);
		div_start('items_table');
		if (count($_SESSION['Items']->line_items) > 0) {
			start_outer_table(" width=90%");
			table_section(1);
			hyperlink_params_separate("/purchasing/po_entry_items.php", _("Create PO from this order"), "NewOrder=Yes&UseOrder=1' class='button'", true, true);
			table_section(2);
			hyperlink_params_separate("/purchasing/po_entry_items.php", _("Dropship this order"), "NewOrder=Yes&UseOrder=1&DS=1' class='button'", true, true);
			end_outer_table(1);
		}
		start_table(Config::get('tables.style') . "  colspan=7 width=90%");
		$th = array(_("Item Code"), _("Item Description"), _("Quantity"), _("Delivered"), _("Unit"), _("Price"), _("Discount %"), _("Total"), "");
		if ($order->trans_no == 0) {
			unset($th[3]);
		}
		if (count($order->line_items)) {
			$th[] = '';
		}
		table_header($th);
		$total_discount = $total = 0;
		$k = 0; //row colour counter
		$id = find_submit('Edit');
		$has_marked = false;
		foreach ($order->line_items as $line_no => $stock_item) {
			$line_total = round($stock_item->qty_dispatched * $stock_item->price * (1 - $stock_item->discount_percent), user_price_dec());
			$line_discount = round($stock_item->qty_dispatched * $stock_item->price, user_price_dec()) - $line_total;
			$qoh_msg = '';
			if (!$editable_items || $id != $line_no) {
				if (!SysPrefs::allow_negative_stock() && is_inventory_item($stock_item->stock_id)) {
					$qoh = get_qoh_on_date($stock_item->stock_id, $_POST['Location'], $_POST['OrderDate']);
					if ($stock_item->qty_dispatched > $qoh) {
						// oops, we don't have enough of one of the component items
						start_row("class='stockmankobg'");
						$qoh_msg .= $stock_item->stock_id . " - " . $stock_item->description . ": " . _("Quantity On Hand") . " = " . number_format2($qoh, get_qty_dec($stock_item->stock_id)) . '<br>';
						$has_marked = true;
					} else {
						alt_table_row_color($k);
					}
				} else {
					alt_table_row_color($k);
				}
				label_cell($stock_item->stock_id, "class='stock' data-stock_id='{$stock_item->stock_id}'");
				//label_cell($stock_item->description, "nowrap" );
				description_cell($stock_item->description);
				$dec = get_qty_dec($stock_item->stock_id);
				qty_cell($stock_item->qty_dispatched, false, $dec);
				if ($order->trans_no != 0) {
					qty_cell($stock_item->qty_done, false, $dec);
				}
				label_cell($stock_item->units);
				amount_cell($stock_item->price);
				percent_cell($stock_item->discount_percent * 100);
				amount_cell($line_total);
				if ($editable_items) {
					edit_button_cell("Edit$line_no", _("Edit"), _('Edit document line'));
					delete_button_cell("Delete$line_no", _("Delete"), _('Remove line from document'));
				}
				end_row();
			} else {
				sales_order_item_controls($order, $k, $line_no);
			}
			$total += $line_total;
			$total_discount += $line_discount;
		}
		if ($id == -1 && $editable_items) {
			sales_order_item_controls($order, $k);
		}
		$colspan = 6;
		if ($order->trans_no != 0) {
			++$colspan;
		}
		start_row();
		label_cell(_("Shipping Charge"), "colspan=$colspan align=right");
		small_amount_cells(null, 'freight_cost', price_format(get_post('freight_cost', 0)));
		label_cell('', 'colspan=2');
		end_row();
		$display_sub_total = price_format($total + input_num('freight_cost'));
		start_row();
		label_cells(_("Total Discount"), $total_discount, "colspan=$colspan align=right", "align=right");
		HTML::td(true)->button('discountall', 'Discount All', array('name' => 'discountall'), false);
		hidden('_discountall', '0', true);
		HTML::td();
		$action = <<<JS
		var discount = prompt("Discount Percent?",''); if (!discount) return false; $("[name='_discountall']").val(Number(discount)); e=$(this);save_focus(e);
                        JsHttpRequest.request(this);
                    return false;
JS;

		JS::addLiveEvent('#discountall', 'click', $action);
		end_row();
		label_row(_("Sub-total"), $display_sub_total, "colspan=$colspan align=right", "align=right", 2);
		$taxes = $order->get_taxes(input_num('freight_cost'));
		$tax_total = ui_view::display_edit_tax_items($taxes, $colspan, $order->tax_included, 2);
		$display_total = price_format(($total + input_num('freight_cost') + $tax_total));
		start_row();
		label_cells(_("Amount Total"), $display_total, "colspan=$colspan align=right", "align=right");
		submit_cells('update', _("Update"), "colspan=2", _("Refresh"), true);
		end_row();
		end_table();
		if ($has_marked) {
			ui_msgs::display_note(_("Marked items have insufficient quantities in stock as on day of delivery."), 0, 1, "class='stockmankofg'");
			if ($order->trans_type != 30 && !SysPrefs::allow_negative_stock()) {
				ui_msgs::display_error(_("The delivery cannot be processed because there is an insufficient quantity for item:") . '<br>' . $qoh_msg);
			}
		}
		div_end();
	}

	// ------------------------------------------------------------------------------
	function display_order_header(&$order, $editable, $date_text, $display_tax_group = false) {

		$Ajax = Ajax::instance();
		start_outer_table("width=90% " . Config::get('tables.style2'));
		table_section(1);
		$customer_error = "";
		$change_prices = 0;
		if (isset($order) && !$editable) {
			// can't change the customer/branch if items already received on this order
			//echo $order->customer_name . " - " . $order->deliver_to;
			label_row(null, $order->customer_name . " - " . $order->deliver_to);
			hidden('customer_id', $order->customer_id);
			hidden('branch_id', $order->Branch);
			hidden('sales_type', $order->sales_type);
			//		if ($order->trans_type != ST_SALESORDER  && $order->trans_type != ST_SALESQUOTE) {
			hidden('dimension_id', $order->dimension_id); // 2008-11-12 Joe Hunt
			hidden('dimension2_id', $order->dimension2_id);
			//		}
		} else {
			customer_list_row(_("Customer:"), 'customer_id', null, false, true, false, true);
			if ($order->customer_id != get_post('customer_id', -1)) {
				// customer has changed
				$Ajax->activate('branch_id');
			}
			customer_branches_list_row(_("Branch:"), $_POST['customer_id'], 'branch_id', null, false, true, true, true);
			if (($order->customer_id != get_post('customer_id', -1)) || ($order->Branch != get_post('branch_id', -1)) || list_updated('customer_id')) {
				if (!isset($_POST['branch_id']) || $_POST['branch_id'] == "") {
					// ignore errors on customer search box call
					if ($_POST['customer_id'] == '') {
						$customer_error = _("No customer found for entered text.");
					}
					else {
						$customer_error = _("The selected customer does not have any branches. Please create at least one branch.");
					}
					unset($_POST['branch_id']);
					$order->Branch = 0;
				}
				else {
					$old_order = (PHP_VERSION < 5) ? $order : clone($order);
					$customer_error = get_customer_details_to_order($order, $_POST['customer_id'], $_POST['branch_id']);
					$_POST['Location'] = $order->Location;
					$_POST['deliver_to'] = $order->deliver_to;
					$_POST['delivery_address'] = $order->delivery_address;
					$_POST['name'] = $order->name;
					$_POST['phone'] = $order->phone;
					if (get_post('cash') !== $order->cash) {
						$_POST['cash'] = $order->cash;
						$Ajax->activate('delivery');
						$Ajax->activate('cash');
					}
					else {
						if ($order->trans_type == ST_SALESINVOICE) {
							$_POST['delivery_date'] = $order->due_date;
							$Ajax->activate('delivery_date');
						}
						$Ajax->activate('Location');
						$Ajax->activate('deliver_to');
						$Ajax->activate('name');
						$Ajax->activate('phone');
						$Ajax->activate('delivery_address');
					}
					// change prices if necessary
					// what about discount in template case?
					if ($old_order->customer_currency != $order->customer_currency) {
						$change_prices = 1;
					}
					if ($old_order->sales_type != $order->sales_type) {
						//  || $old_order->default_discount!=$order->default_discount
						$_POST['sales_type'] = $order->sales_type;
						$Ajax->activate('sales_type');
						$change_prices = 1;
					}
					if ($old_order->dimension_id != $order->dimension_id) {
						$_POST['dimension_id'] = $order->dimension_id;
						$Ajax->activate('dimension_id');
					}
					if ($old_order->dimension2_id != $order->dimension2_id) {
						$_POST['dimension2_id'] = $order->dimension2_id;
						$Ajax->activate('dimension2_id');
					}
					unset($old_order);
				}
				ui_globals::set_global_customer($_POST['customer_id']);
			} // changed branch
			else {
				$row = get_customer_to_order($_POST['customer_id']);
				if ($row['dissallow_invoices'] == 1) {
					$customer_error = _("The selected customer account is currently on hold. Please contact the credit control personnel to discuss.");
				}
			}
		}
		ref_row(_("Reference") . ':', 'ref', _('Reference number unique for this document type'), null, '');
		if (!Banking::is_company_currency($order->customer_currency)) {
			table_section(2);
			label_row(_("Customer Currency:"), $order->customer_currency);
			ui_view::exchange_rate_display($order->customer_currency, Banking::get_company_currency(), ($editable ? $_POST['OrderDate'] : $order->document_date));
		}
		table_section(3);
		customer_credit_row($_POST['customer_id'], $order->credit);
		if ($editable) {
			$str = sales_types_list_row(_("Price List"), 'sales_type', null, true);
		}
		else {
			label_row(_("Price List:"), $order->sales_type_name);
		}
		if ($order->sales_type != $_POST['sales_type']) {
			$myrow = get_sales_type($_POST['sales_type']);
			$order->set_sales_type($myrow['id'], $myrow['sales_type'], $myrow['tax_included'], $myrow['factor']);
			$Ajax->activate('sales_type');
			$change_prices = 1;
		}
		label_row(_("Customer Discount:"), ($order->default_discount * 100) . "%");
		table_section(4);
		if ($editable) {
			if (!isset($_POST['OrderDate']) || $_POST['OrderDate'] == "") {
				$_POST['OrderDate'] = $order->document_date;
			}
			date_row($date_text, 'OrderDate', null, $order->trans_no == 0, 0, 0, 0, null, true);
			if (isset($_POST['_OrderDate_changed'])) {
				if (!Banking::is_company_currency($order->customer_currency) && (DB_Company::get_base_sales_type() > 0)) {
					$change_prices = 1;
				}
				$Ajax->activate('_ex_rate');
				if ($order->trans_type == ST_SALESINVOICE) {
					$_POST['delivery_date'] = get_invoice_duedate(get_post('customer_id'), get_post('OrderDate'));
				}
				else {
					$_POST['delivery_date'] = Dates::add_days(get_post('OrderDate'), SysPrefs::default_delivery_required_by());
				}
				$Ajax->activate('items_table');
				$Ajax->activate('delivery_date');
			}
			if ($order->trans_type != ST_SALESORDER && $order->trans_type != ST_SALESQUOTE) { // 2008-11-12 Joe Hunt added dimensions
				$dim = DB_Company::get_pref('use_dimension');
				if ($dim > 0) {
					dimensions_list_row(_("Dimension") . ":", 'dimension_id', null, true, ' ', false, 1, false);
				}
				else {
					hidden('dimension_id', 0);
				}
				if ($dim > 1) {
					dimensions_list_row(_("Dimension") . " 2:", 'dimension2_id', null, true, ' ', false, 2, false);
				}
				else {
					hidden('dimension2_id', 0);
				}
			}
		}
		else {
			label_row($date_text, $order->document_date);
			hidden('OrderDate', $order->document_date);
		}
		if ($display_tax_group) {
			label_row(_("Tax Group:"), $order->tax_group_name);
			hidden('tax_group_id', $order->tax_group_id);
		}

		sales_persons_list_row(_("Sales Person:"), 'salesman', (isset($order->salesman)) ? $order->salesman : $_SESSION['wa_current_user']->salesmanid);
		end_outer_table(1); // outer table
		if ($change_prices != 0) {
			foreach ($order->line_items as $line_no => $item) {
				$line = &$order->line_items[$line_no];
				$line->price = get_kit_price($line->stock_id, $order->customer_currency, $order->sales_type, $order->price_factor, get_post('OrderDate'));
				//		$line->discount_percent = $order->default_discount;
			}
			$Ajax->activate('items_table');
		}
		return $customer_error;
	}

	//--------------------------------------------------------------------------------
	function sales_order_item_controls(&$order, &$rowcounter, $line_no = -1) {
		$Ajax = Ajax::instance();
		alt_table_row_color($rowcounter);
		$id = find_submit('Edit');
		if ($line_no != -1 && $line_no == $id) // edit old line
		{
			$_POST['stock_id'] = $order->line_items[$id]->stock_id;
			$dec = get_qty_dec($_POST['stock_id']);
			$_POST['qty'] = number_format2($order->line_items[$id]->qty_dispatched, $dec);
			$_POST['price'] = price_format($order->line_items[$id]->price);
			$_POST['Disc'] = percent_format($order->line_items[$id]->discount_percent * 100);
			$_POST['description'] = $order->line_items[$id]->description;
			$units = $order->line_items[$id]->units;
			hidden('stock_id', $_POST['stock_id']);
			label_cell($_POST['stock_id'], 'class="stock"');
			textarea_cells(null, 'description', null, 50, 5);
			$Ajax->activate('items_table');
		}
		else // prepare new line
		{
			sales_items_list_cells(null, 'stock_id', null, false, false, array('description' => ''));
			if (list_updated('stock_id')) {
				$Ajax->activate('price');
				$Ajax->activate('description');
				$Ajax->activate('units');
				$Ajax->activate('qty');
				$Ajax->activate('line_total');
			}
			$item_info = get_item_edit_info(Input::post('stock_id'));
			$units = $item_info["units"];
			$dec = $item_info['decimals'];
			$_POST['qty'] = number_format2(1, $dec);
			$price = get_kit_price(Input::post('stock_id'), $order->customer_currency, $order->sales_type, $order->price_factor, get_post('OrderDate'));
			$_POST['price'] = price_format($price);
			$_POST['Disc'] = percent_format($order->default_discount * 100);
		}
		qty_cells(null, 'qty', $_POST['qty'], null, null, $dec);
		if ($order->trans_no != 0) {
			qty_cell($line_no == -1 ? 0 : $order->line_items[$line_no]->qty_done, false, $dec);
		}
		label_cell($units, '', 'units');
		$str = amount_cells(null, 'price');
		small_amount_cells(null, 'Disc', percent_format($_POST['Disc']), null, null, user_percent_dec());
		$line_total = input_num('qty') * input_num('price') * (1 - input_num('Disc') / 100);
		amount_cell($line_total, false, '', 'line_total');
		if ($id != -1) {
			button_cell('UpdateItem', _("Update"), _('Confirm changes'), ICON_UPDATE);
			button_cell('CancelItemChanges', _("Cancel"), _('Cancel changes'), ICON_CANCEL);
			hidden('LineNo', $line_no);
			ui_view::set_focus('qty');
		}
		else {
			submit_cells('AddItem', _("Add Item"), "colspan=2", _('Add new item to document'), true);
		}
		end_row();
	}

	//--------------------------------------------------------------------------------
	function display_delivery_details(&$order) {
		$Ajax = Ajax::instance();
		div_start('delivery');
		if (get_post('cash', 0)) { // Direct payment sale
			$Ajax->activate('items_table');
			ui_msgs::display_heading(_('Cash payment'));
			start_table(Config::get('tables.style2') . " width=60%");
			label_row(_("Deliver from Location:"), $order->location_name);
			hidden('Location', $order->Location);
			label_row(_("Cash account:"), $order->account_name);
			textarea_row(_("Comments:"), "Comments", $order->Comments, 31, 5);
			end_table();
		}
		else {
			if ($order->trans_type == ST_SALESINVOICE) {
				$title = _("Delivery Details");
				$delname = _("Due Date") . ':';
			}
			elseif ($order->trans_type == ST_CUSTDELIVERY) {
				$title = _("Invoice Delivery Details");
				$delname = _("Invoice before") . ':';
			}
			elseif ($order->trans_type == ST_SALESQUOTE) {
				$title = _("Quotation Delivery Details");
				$delname = _("Valid until") . ':';
			}
			else {
				$title = _("Order Delivery Details");
				$delname = _("Required Delivery Date") . ':';
			}
			ui_msgs::display_heading($title);
			start_outer_table(Config::get('tables.style2') . " width=90%");
			table_section(1);
			locations_list_row(_("Deliver from Location:"), 'Location', null, false, true);
			if (list_updated('Location')) {
				$Ajax->activate('items_table');
			}
			date_row($delname, 'delivery_date', $order->trans_type == ST_SALESORDER ? _('Enter requested day of delivery') : $order->trans_type == ST_SALESQUOTE
				 ? _('Enter Valid until Date') : '');
			text_row(_("Deliver To:"), 'deliver_to', $order->deliver_to, 40, 40, _('Additional identifier for delivery e.g. name of receiving person'));
			textarea_row("<a href='#'>Address:</a>", 'delivery_address', $order->delivery_address, 35, 5, _('Delivery address. Default is address of customer branch'), null, 'id="address_map"');
			if (strlen($order->delivery_address) > 10) {
				//JS::gmap("#address_map", $order->delivery_address, $order->delivery_to);
			}
			table_section(2);
			text_row(_("Person ordering:"), 'name', $order->name, 25, 25, 'Ordering person&#39;s name');
			text_row(_("Contact Phone Number:"), 'phone', $order->phone, 25, 25, _('Phone number of ordering person. Defaults to branch phone number'));
			text_row(_("Customer Purchase Order #:"), 'cust_ref', $order->cust_ref, 25, 25, _('Customer reference number for this order (if any)'));
			textarea_row(_("Comments:"), "Comments", $order->Comments, 31, 5);
			shippers_list_row(_("Shipping Company:"), 'ship_via', $order->ship_via);
			end_outer_table(1);
		}
		div_end();
	}

?>