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
	/* Definition of the purch_order class to hold all the information for a purchase order and delivery
	 */
	class Purch_Order {
		public $supplier_id;
		public $supplier_details;
		public $line_items; /*array of objects of class Sales_Line using the product id as the pointer */
		public $curr_code;
		public $requisition_no;
		public $delivery_address;
		public $Comments;
		public $Location;
		public $supplier_name;
		public $orig_order_date;
		public $trans_no;
		public $order_no; /*Only used for modification of existing orders otherwise only established when order committed */
		protected $uniqueid;
		public $lines_on_order;
		public $order_id;
		public $freight;
		public $salesman;
		public $reference;

		public function __construct($order_no = 0, $view = false) {
			/*Constructor function initialises a new purchase order object */
			$this->line_items = array();
			$this->lines_on_order = $this->order_no = $this->supplier_id = 0;
			$this->set_salesman();
			$this->order_no = $order_no;
			$this->read($order_no, $view);
			$_POST['OrderDate'] = Dates::new_doc_date();
			if (!Dates::is_date_in_fiscalyear($_POST['OrderDate'])) {
				$_POST['OrderDate'] = Dates::end_fiscalyear();
			}
			$this->orig_order_date = $_POST['OrderDate'];
			$this->uniqueid = uniqid();
			$this->order_id = ST_PURCHORDER . '.' . sha1(ST_PURCHORDER . serialize($this->order_no));
		}

		public function add_to_order($line_no, $stock_id, $qty, $item_descr, $price, $uom, $req_del_date, $qty_inv, $qty_recd, $discount) {
			if ($qty != 0 && isset($qty)) {
				$this->line_items[$line_no] = new Purch_Line($line_no, $stock_id, $item_descr, $qty, $price, $uom, $req_del_date, $qty_inv, $qty_recd, $discount);
				$this->lines_on_order++;
				Return 1;
			}
			Return 0;
		}

		public function set_salesman($salesman_code = null) {
			if ($salesman_code == null) {
				$salesman_name = $_SESSION['current_user']->name;
				$sql = "SELECT salesman_code FROM salesman WHERE salesman_name = " . DB::escape($salesman_name);
				$query = DB::query($sql, 'Couldn\'t find current salesman');
				$result = DB::fetch_assoc($query);
				if (!empty($result['salesman_code'])) {
					$salesman_code = $result['salesman_code'];
				}
			}
			if ($salesman_code != null) {
				$this->salesman = $salesman_code;
			}
		}

		public function update_order_item($line_no, $qty, $price, $req_del_date, $item_descr = '', $discount = 0) {
			$this->line_items[$line_no]->quantity = $qty;
			$this->line_items[$line_no]->price = $price;
			$this->line_items[$line_no]->discount = $discount;
			if (!empty($item_descr)) {
				$this->line_items[$line_no]->description = $item_descr;
			}
			$this->line_items[$line_no]->req_del_date = $req_del_date;
			$this->line_items[$line_no]->price = $price;
		}

		public function remove_from_order($line_no) {
			$this->line_items[$line_no]->Deleted = true;
		}

		public function order_has_items() {
			if (count($this->line_items) > 0) {
				foreach ($this->line_items as $ordered_items) {
					if ($ordered_items->Deleted == false) {
						return true;
					}
				}
			}
			return false;
		}

		public function clear_items() {
			unset($this->line_items);
			$this->line_items = array();
			$this->lines_on_order = 0;
			$this->order_no = 0;
		}

		public function any_already_received() {
			/* Checks if there have been deliveries or invoiced entered against any of the line items */
			if (count($this->line_items) > 0) {
				foreach ($this->line_items as $ordered_items) {
					if ($ordered_items->qty_received != 0 || $ordered_items->qty_inv != 0) {
						return 1;
					}
				}
			}
			return 0;
		}

		public function some_already_received($line_no) {
			/* Checks if there have been deliveries or amounts invoiced against a specific line item */
			if (count($this->line_items) > 0) {
				if ($this->line_items[$line_no]->qty_received != 0 || $this->line_items[$line_no]->qty_inv != 0
				) {
					return 1;
				}
			}
			return 0;
		}

		public function delete() {
			$sql = "DELETE FROM purch_orders WHERE order_no=" . DB::escape($this->order_no);
			DB::query($sql, "The order header could not be deleted");
			$sql = "DELETE FROM purch_order_details WHERE order_no =" . DB::quote($this->order_no);
			DB::query($sql, "The order detail lines could not be deleted");
			Orders::session_delete($this->order_no);
		}

		public function add() {
			DB::begin();
			/*Insert to purchase order header record */
			$sql = "INSERT INTO purch_orders (supplier_id, Comments, ord_date, reference, requisition_no, into_stock_location, delivery_address, freight, salesman) VALUES(";
			$sql .= DB::escape($this->supplier_id) . "," . DB::escape($this->Comments) . ",'" . Dates::date2sql($this->orig_order_date) . "', " . DB::escape($this->reference) . ", " . DB::escape($this->requisition_no) . ", " . DB::escape($this->Location) . ", " . DB::escape($this->delivery_address) . ", " . DB::escape($this->freight) . ", " . DB::escape($this->salesman) . ")";
			DB::query($sql, "The purchase order header record could not be inserted");
			/*Get the auto increment value of the order number created from the sql above */
			$this->order_no = DB::insert_id();
			/*Insert the purchase order detail records */
			foreach ($this->line_items as $po_line) {
				if ($po_line->Deleted == false) {
					$sql = "INSERT INTO purch_order_details (order_no, item_code, description, delivery_date, unit_price, quantity_ordered, discount) VALUES (";
					$sql .= $this->order_no . ", " . DB::escape($po_line->stock_id) . "," . DB::escape($po_line->description) . ",'" . Dates::date2sql($po_line->req_del_date) . "'," . DB::escape($po_line->price) . ", " . DB::escape($po_line->quantity) . ", " . DB::escape($po_line->discount) . ")";
					DB::query($sql, "One of the purchase order detail records could not be inserted");
				}
			}
			Ref::save(ST_PURCHORDER, $this->reference);
			//DB_Comments::add(ST_PURCHORDER, $this->order_no, $this->orig_order_date, $this->Comments);
			DB_AuditTrail::add(ST_PURCHORDER, $this->order_no, $this->orig_order_date);
			DB::commit();
			Orders::session_delete($this->order_id);
			return $this->order_no;
		}

		public function update() {
			DB::begin();
			/*Update the purchase order header with any changes */
			$sql = "UPDATE purch_orders SET Comments=" . DB::escape($this->Comments) . ",
			requisition_no= " . DB::escape($this->requisition_no) . ",
			into_stock_location=" . DB::escape($this->Location) . ",
			ord_date='" . Dates::date2sql($this->orig_order_date) . "',
			delivery_address=" . DB::escape($this->delivery_address) . ",
			freight=" . DB::escape($this->freight) . ",
			salesman=" . DB::escape($this->salesman);
			$sql .= " WHERE order_no = " . $this->order_no;
			DB::query($sql, "The purchase order could not be updated");
			/*Now Update the purchase order detail records */
			foreach ($this->line_items as $po_line) {
				if ($po_line->Deleted == True) {
					// Sherifoz 21.06.03 Handle deleting existing lines
					if ($po_line->po_detail_rec != '') {
						$sql = "DELETE FROM purch_order_details WHERE po_detail_item=" . DB::escape($po_line->po_detail_rec);
						DB::query($sql, "could not query purch order details");
					}
				}
				else if ($po_line->po_detail_rec == '') {
					// Sherifoz 21.06.03 Handle adding new lines vs. updating. if no key(po_detail_rec) then it's a new line
					$sql = "INSERT INTO purch_order_details (order_no, item_code, description, delivery_date, unit_price, quantity_ordered, discount) VALUES (";
					$sql .= $this->order_no . "," . DB::escape($po_line->stock_id) . "," . DB::escape($po_line->description) . ",'" . Dates::date2sql($po_line->req_del_date) . "'," . DB::escape($po_line->price) . ", " . DB::escape($po_line->quantity) . ", " . DB::escape($po_line->discount) . ")";
				}
				else {
					$sql = "UPDATE purch_order_details SET item_code=" . DB::escape($po_line->stock_id) . ",
					description =" . DB::escape($po_line->description) . ",
					delivery_date ='" . Dates::date2sql($po_line->req_del_date) . "',
					unit_price=" . DB::escape($po_line->price) . ",
					quantity_ordered=" . DB::escape($po_line->quantity) . ",
					discount=" . DB::escape($po_line->discount) . "
					WHERE po_detail_item=" . DB::escape($po_line->po_detail_rec);
				}
				DB::query($sql, "One of the purchase order detail records could not be updated");
			}
			//DB_Comments::add(ST_PURCHORDER, $this->order_no, $this->orig_order_date, $this->Comments);
			DB::commit();
			Orders::session_delete($this->order_id);
			return $this->order_no;
		}

		public function get_header($order_no) {
			$sql
			 = "SELECT purch_orders.*, suppliers.supp_name,
	 		suppliers.curr_code, locations.location_name
			FROM purch_orders, suppliers, locations
			WHERE purch_orders.supplier_id = suppliers.supplier_id
			AND locations.loc_code = into_stock_location
			AND purch_orders.order_no = " . DB::escape($order_no);
			$result = DB::query($sql, "The order cannot be retrieved");
			if (DB::num_rows($result) == 1) {
				$myrow = DB::fetch($result);
				$this->order_no = $order_no;
				$this->supplier_id = $myrow["supplier_id"];
				$this->supplier_name = $myrow["supp_name"];
				$this->curr_code = $myrow["curr_code"];
				$this->orig_order_date = Dates::sql2date($myrow["ord_date"]);
				$this->Comments = $myrow["comments"];
				$this->Location = $myrow["into_stock_location"];
				$this->requisition_no = $myrow["requisition_no"];
				$this->reference = $myrow["reference"];
				$this->delivery_address = $myrow["delivery_address"];
				$this->freight = $myrow["freight"];
				$this->salesman = $myrow['salesman'];
				return true;
			}
			Errors::show_db_error("FATAL : duplicate purchase order found", "", true);
			return false;
		}

		public function get_items($order_no, $view = false) {
			/*now populate the line po array with the purchase order details records */
			$sql
			 = "SELECT purch_order_details.*, units
			FROM purch_order_details
			LEFT JOIN stock_master
			ON purch_order_details.item_code=stock_master.stock_id
			WHERE order_no =" . DB::escape($order_no);
			if ($view) {
				$sql .= " AND (purch_order_details.quantity_ordered > purch_order_details.quantity_received) ";
			}
			$sql .= " ORDER BY po_detail_item";
			$result = DB::query($sql, "The lines on the purchase order cannot be retrieved");
			if (DB::num_rows($result) > 0) {
				while ($myrow = DB::fetch($result)) {
					$data = static::get_data($this->supplier_id, $myrow['item_code']);
					if ($data !== false) {
						if ($data['supplier_description'] != "") {
							$myrow['supplier_description'] = $data['supplier_description'];
						}
						if ($data['suppliers_uom'] != "") {
							$myrow['units'] = $data['suppliers_uom'];
						}
					}
					if (is_null($myrow["units"])) {
						$units = "";
					}
					else {
						$units = $myrow["units"];
					}
					if ($this->add_to_order($this->lines_on_order + 1, $myrow["item_code"], $myrow["quantity_ordered"], $myrow["description"], $myrow["unit_price"], $units, Dates::sql2date($myrow["delivery_date"]), $myrow["qty_invoiced"], $myrow["quantity_received"], $myrow["discount"])
					) {
						$this->line_items[$this->lines_on_order]->po_detail_rec = $myrow["po_detail_item"];
						$this->line_items[$this->lines_on_order]->standard_cost
						 = $myrow["std_cost_unit"]; /*Needed for receiving goods and GL interface */
					}
				} /* line po from purchase order details */
			} //end of checks on returned data set
		}

		public function read($order_no, $view = false) {
			$result = $this->get_header($order_no);
			if ($result) {
				$this->get_items($order_no, $view);
			}
		}

		public function	 add_freight($date_) {
			$sql = "INSERT INTO purch_order_details (order_no, item_code, description, delivery_date, unit_price, quantity_ordered, discount) VALUES (";
			$sql .= $this->order_no . "," . DB::escape('freight') . "," . DB::escape('Freight Charges') . ",'" . Dates::date2sql($date_) . "'," . DB::escape($this->freight) . ", " . DB::escape(1) . ", " . DB::escape(0) . ")";
			DB::query($sql, "One of the purchase order detail records could not be updated");
			return DB::insert_id();
		}

		public function supplier_to_order($supplier_id) {
			$sql
			 = "SELECT * FROM suppliers
			WHERE supplier_id = '$supplier_id'";
			$result = DB::query($sql, "The supplier details could not be retreived");
			$myrow = DB::fetch_assoc($result);
			$this->supplier_details = $myrow;
			$this->curr_code = $_POST['curr_code'] = $myrow["curr_code"];
			$this->supplier_name = $_POST['supplier_name'] = $myrow["supp_name"];
			$this->supplier_id = $_POST['supplier_id'] = $supplier_id;
		}

		/*
																 Check if the order was not destroyed during opening the edition page in
																 another browser tab.
															 */
		public static function check_edit_conflicts($order) {
			if (!isset($_POST['order_id'])) {
				$_POST['order_id'] = $order->order_id;
			}
			$session_order = Orders::session_get();
			if ($session_order && $session_order->uniqueid != $order->uniqueid) {
				if (!$session_order->trans_no && count($session_order->line_items) > 0) {
					Errors::warning(_('You were in the middle of creating a new order, this order has been continued. If you would like to start a completely new order, push the cancel changes button at the bottom of the page'));
				} elseif ($session_order->trans_no) {
					Errors::error(_('You were previously editing this order in another tab, those changes have been applied to this tab'));
				}
				return $session_order;
			}
			return $order;
		}

		public function header() {
			$editable = ($this->order_no == 0);
			start_outer_table('tablestyle2 width90');
			table_section(1);
			if ($editable) {
				if (!isset($_POST['supplier_id']) && Session::i()->supplier_id) {
					$_POST['supplier_id'] = Session::i()->supplier_id;
				}
				Purch_Creditor::row(_("Supplier:"), 'supplier_id', null, false, true, false, true);
			}
			else {
				if (isset($_POST['supplier_id'])) {
					$this->supplier_to_order($_POST['supplier_id']);
				}
				hidden('supplier_id', $this->supplier_id);
				label_row(_("Supplier:"), $this->supplier_name, 'class="label" name="supplier_name"');
			}
			if ($this->supplier_id != get_post('supplier_id', -1)) {
				$old_supp = $this->supplier_id;
				$this->supplier_to_order($_POST['supplier_id']);
				// supplier default price update
				foreach ($this->line_items as $line) {
					$line->price = Item_Price::get_purchase($this->supplier_id, $line->stock_id);
					$line->quantity = $line->quantity / Purch_Trans::get_conversion_factor($old_supp, $line->stock_id) * Purch_Trans::get_conversion_factor($this->supplier_id, $line->stock_id);
				}
				Ajax::i()->activate('items_table');
			}
			Session::i()->supplier_id = $_POST['supplier_id'];
			if (!Bank_Currency::is_company($this->curr_code)) {
				label_row(_("Supplier Currency:"), $this->curr_code);
				GL_ExchangeRate::display($this->curr_code, Bank_Currency::for_company(), $_POST['OrderDate']);
			}
			if ($editable) {
				ref_row(_("Purchase Order #:"), 'ref', '', Ref::get_next(ST_PURCHORDER));
			}
			else {
				hidden('ref', $this->reference);
				label_row(_("Purchase Order #:"), $this->reference);
			}
			Sales_UI::persons_row(_("Sales Person:"), 'salesman', $this->salesman);
			table_section(2);
			date_row(_("Order Date:"), 'OrderDate', '', true, 0, 0, 0, null, true);
			if (isset($_POST['_OrderDate_changed'])) {
				Ajax::i()->activate('_ex_rate');
			}
			text_row(_("Supplier's Order #:"), 'Requisition', null, 16, 15);
			Inv_Location::row(_("Receive Into:"), 'StkLocation', null, false, true);
			table_section(3);
			if (!isset($_POST['StkLocation']) || $_POST['StkLocation'] == "" || isset($_POST['_StkLocation_update']) || !isset($_POST['delivery_address']) || $_POST['delivery_address'] == ""
			) {
				$sql = "SELECT delivery_address, phone FROM locations WHERE loc_code='" . $_POST['StkLocation'] . "'";
				$result = DB::query($sql, "could not get location info");
				if (DB::num_rows($result) == 1) {
					$loc_row = DB::fetch($result);
					$_POST['delivery_address'] = $loc_row["delivery_address"];
					Ajax::i()->activate('delivery_address');
					$_SESSION['PO']->Location = $_POST['StkLocation'];
					$_SESSION['PO']->delivery_address = $_POST['delivery_address'];
				}
				else { /* The default location of the user is crook */
					Errors::error(_("The default stock location set up for this user is not a currently defined stock location. Your system administrator needs to amend your user record."));
				}
			}
			textarea_row(_("Deliver to:"), 'delivery_address', $_POST['delivery_address'], 35, 4);
			end_outer_table(); // outer table
		}

		public function display_items($editable = true) {
			Display::heading(_("Order Items"));
			Display::div_start('items_table');
			start_table('tablestyle width90');
			$th = array(
				_("Item Code"), _("Description"), _("Quantity"), _("Received"), _("Unit"), _("Required Date"), _("Price"),
				_('Discount %'), _("Total"), "");
			if (count($this->line_items)) {
				$th[] = '';
			}
			table_header($th);
			$id = find_submit('Edit');
			$total = 0;
			$k = 0;
			foreach ($this->line_items as $line_no => $po_line) {
				if ($po_line->Deleted == false) {
					$line_total = round($po_line->quantity * $po_line->price * (1 - $po_line->discount), User::price_dec(), PHP_ROUND_HALF_EVEN);
					if (!$editable || ($id != $line_no)) {
						alt_table_row_color($k);
						label_cell($po_line->stock_id, " class='stock' data-stock_id='{$po_line->stock_id}'");
						label_cell($po_line->description);
						qty_cell($po_line->quantity, false, Item::qty_dec($po_line->stock_id));
						qty_cell($po_line->qty_received, false, Item::qty_dec($po_line->stock_id));
						label_cell($po_line->units);
						label_cell($po_line->req_del_date);
						amount_decimal_cell($po_line->price);
						percent_cell($po_line->discount * 100);
						amount_cell($line_total);
						if ($editable) {
							edit_button_cell("Edit$line_no", _("Edit"), _('Edit document line'));
							delete_button_cell("Delete$line_no", _("Delete"), _('Remove line from document'));
						}
						end_row();
					}
					else {
						$this->item_controls($po_line->stock_id);
					}
					$total += $line_total;
				}
			}
			if ($id == -1 && $editable) {
				$this->item_controls();
			}
			small_amount_row(_("Freight"), 'freight', Num::price_format(get_post('freight', 0)), "colspan=8 class='bold right'", null, null, 3);
			$display_total = Num::price_format($total + Validation::input_num('freight'));
			label_row(_("Total Excluding Shipping/Tax"), $display_total, "colspan=8 class='bold right'", "nowrap class=right _nofreight='$total'", 2);
			end_table(1);
			Display::div_end();
		}

		public function summary($is_self = false, $editable = false) {
			start_table('tablestyle2 width90');
			echo "<tr class='tableheader2 top'><th colspan=4>";
			Display::heading(_("Purchase Order") . " #" . $_GET['trans_no']);
			echo "</td></tr>";
			start_row();
			label_cells(_("Supplier"), $this->supplier_name, "class='label'");
			label_cells(_("Reference"), $this->reference, "class='label'");
			if (!Bank_Currency::is_company($this->curr_code)) {
				label_cells(_("Order Currency"), $this->curr_code, "class='label'");
			}
			if (!$is_self) {
				label_cells(_("Purchase Order"), GL_UI::trans_view(ST_PURCHORDER, $this->order_no), "class='label'");
			}
			end_row();
			start_row();
			label_cells(_("Date"), $this->orig_order_date, "class='label'");
			if ($editable) {
				if (!isset($_POST['Location'])) {
					$_POST['Location'] = $this->Location;
				}
				label_cell(_("Deliver Into Location"), "class='label'");
				Inv_Location::cells(null, 'Location', $_POST['Location']);
			}
			else {
				label_cells(_("Deliver Into Location"), Inv_Location::get_name($this->Location), "class='label'");
			}
			end_row();
			if (!$editable) {
				label_row(_("Delivery Address"), $this->delivery_address, "class='label'", "colspan=9");
			}
			if ($this->Comments != "") {
				label_row(_("Order Comments"), $this->Comments, "class='label'", "colspan=9");
			}
			end_table(1);
		}

		public function item_controls($stock_id = null) {
			start_row();
			$dec2 = 0;
			$id = find_submit('Edit');
			if (($id != -1) && $stock_id != null) {
				hidden('line_no', $id);
				$_POST['stock_id'] = $this->line_items[$id]->stock_id;
				$dec = Item::qty_dec($_POST['stock_id']);
				$_POST['qty'] = Item::qty_format($this->line_items[$id]->quantity, $_POST['stock_id'], $dec);
				//$_POST['price'] = Num::price_format($this->line_items[$id]->price);
				$_POST['price'] = Num::price_decimal($this->line_items[$id]->price, $dec2);
				$_POST['discount'] = Num::percent_format($this->line_items[$id]->discount * 100);
				$_POST['req_del_date'] = $this->line_items[$id]->req_del_date;
				$_POST['description'] = $this->line_items[$id]->description;
				$_POST['units'] = $this->line_items[$id]->units;
				hidden('stock_id', $_POST['stock_id']);
				label_cell($_POST['stock_id'], " class='stock' data-stock_id='{$_POST['stock_id']}'");
				textarea_cells(null, 'description', null, 50, 5);
				Ajax::i()->activate('items_table');
				$qty_rcvd = $this->line_items[$id]->qty_received;
			}
			else {
				hidden('line_no', ($this->lines_on_order + 1));
				Item_Purchase::cells(null, 'stock_id', null, false, true, true);
				if (list_updated('stock_id')) {
					Ajax::i()->activate('price');
					Ajax::i()->activate('units');
					Ajax::i()->activate('description');
					Ajax::i()->activate('qty');
					Ajax::i()->activate('discount');
					Ajax::i()->activate('req_del_date');
					Ajax::i()->activate('line_total');
				}
				$item_info = Item::get_edit_info(Input::post('stock_id'));
				$_POST['units'] = $item_info["units"];
				$_POST['description'] = '';
				$dec = $item_info["decimals"];
				$_POST['qty'] = Num::format(Purch_Trans::get_conversion_factor($this->supplier_id, Input::post('stock_id')), $dec);
				$_POST['price'] = Num::price_decimal(Item_Price::get_purchase($this->supplier_id, Input::post('stock_id')), $dec2);
				$_POST['req_del_date'] = Dates::add_days(Dates::Today(), 10);
				$_POST['discount'] = Num::percent_format(0);
				$qty_rcvd = '';
			}
			qty_cells(null, 'qty', null, null, null, $dec);
			qty_cell($qty_rcvd, false, $dec);
			label_cell($_POST['units'], '', 'units');
			date_cells(null, 'req_del_date', '', null, 0, 0, 0);
			amount_cells(null, 'price', null, null, null, $dec2);
			small_amount_cells(null, 'discount', Num::percent_format($_POST['discount']), null, null, User::percent_dec());
			$line_total = Validation::input_num('qty') * Validation::input_num('price') * (1 - Validation::input_num('discount') / 100);
			amount_cell($line_total, false, '', 'line_total');
			if ($id != -1) {
				button_cell('UpdateLine', _("Update"), _('Confirm changes'), ICON_UPDATE);
				button_cell('CancelUpdate', _("Cancel"), _('Cancel changes'), ICON_CANCEL);
				JS::set_focus('qty');
			}
			else {
				submit_cells('EnterLine', _("Add Item"), "colspan=2", _('Add new item to document'), true);
			}
			end_row();
		}

		public static function get_data($supplier_id, $stock_id) {
			$sql
			 = "SELECT * FROM purch_data
				WHERE supplier_id = " . DB::escape($supplier_id) . "
				AND stock_id = " . DB::escape($stock_id);
			$result = DB::query($sql, "The supplier pricing details for " . $stock_id . " could not be retrieved");
			return DB::fetch($result);
		}

		public static function add_or_update_data($supplier_id, $stock_id, $price, $supplier_code = "", $uom = "") {
			$data = static::get_data($supplier_id, $stock_id);
			if ($data === false) {
				$supplier_code = $stock_id;
				$sql
				 = "INSERT INTO purch_data (supplier_id, stock_id, price, suppliers_uom,
					conversion_factor, supplier_description) VALUES (" . DB::escape($supplier_id) . ", " . DB::escape($stock_id) . ", " . DB::escape($price) . ", " . DB::escape($uom) . ", 1, " . DB::escape($supplier_code) . ")";
				DB::query($sql, "The supplier purchasing details could not be added");
				return false;
			}
			$price = round($price * $data['conversion_factor'], User::price_dec());
			$sql = "UPDATE purch_data SET price=" . DB::escape($price);
			if ($uom != "") {
				$sql .= ",suppliers_uom=" . DB::escape($uom);
			}
			if ($supplier_code != "") {
				$sql .= ",supplier_description=" . DB::escape($supplier_code);
			}
			$sql .= " WHERE stock_id=" . DB::escape($stock_id) . " AND supplier_id=" . DB::escape($supplier_id);
			DB::query($sql, "The supplier purchasing details could not be updated");
			return true;
		}
	} /* end of class defintion */
