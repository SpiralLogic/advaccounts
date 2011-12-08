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
	class Purch_Order
	{
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
		public $order_no; /*Only used for modification of existing orders otherwise only established when order committed */
		public $lines_on_order;
		public $freight;
		public $salesman;
		public $reference;

		public function __construct() {
			/*Constructor function initialises a new purchase order object */
			$this->line_items = array();
			$this->lines_on_order = $this->order_no = $this->supplier_id = 0;
			$this->set_salesman();
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
				foreach ($this->line_items as $ordered_items)
				{
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
				foreach ($this->line_items as $ordered_items)
				{
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
				if ($this->line_items[$line_no]->qty_received != 0
				 || $this->line_items[$line_no]->qty_inv != 0
				) {
					return 1;
				}
			}
			return 0;
		}

		//--------	--------------------------------------------------------------------------------
		public static function delete($po) {
			$sql = "DELETE FROM purch_orders WHERE order_no=" . DB::escape($po);
			DB::query($sql, "The order header could not be deleted");
			$sql = "DELETE FROM purch_order_details WHERE order_no =" . DB::escape($po, false, false);
			DB::query($sql, "The order detail lines could not be deleted");
		}

		public static function add(&$po_obj) {
			DB::begin_transaction();
			/*Insert to purchase order header record */
			$sql = "INSERT INTO purch_orders (supplier_id, Comments, ord_date, reference, requisition_no, into_stock_location, delivery_address, freight, salesman) VALUES(";
			$sql .= DB::escape($po_obj->supplier_id) . "," .
			 DB::escape($po_obj->Comments) . ",'" .
			 Dates::date2sql($po_obj->orig_order_date) . "', " .
			 DB::escape($po_obj->reference) . ", " .
			 DB::escape($po_obj->requisition_no) . ", " .
			 DB::escape($po_obj->Location) . ", " .
			 DB::escape($po_obj->delivery_address) . ", " .
			 DB::escape($po_obj->freight) . ", " .
			 DB::escape($po_obj->salesman) . ")";
			DB::query($sql, "The purchase order header record could not be inserted");
			/*Get the auto increment value of the order number created from the sql above */
			$po_obj->order_no = DB::insert_id();
			/*Insert the purchase order detail records */
			foreach ($po_obj->line_items as $po_line)
			{
				if ($po_line->Deleted == false) {
					$sql = "INSERT INTO purch_order_details (order_no, item_code, description, delivery_date, unit_price, quantity_ordered, discount) VALUES (";
					$sql .= $po_obj->order_no . ", " . DB::escape($po_line->stock_id) . "," .
					 DB::escape($po_line->description) . ",'" .
					 Dates::date2sql($po_line->req_del_date) . "'," .
					 DB::escape($po_line->price) . ", " .
					 DB::escape($po_line->quantity) . ", " .
					 DB::escape($po_line->discount) . ")";
					DB::query($sql, "One of the purchase order detail records could not be inserted");
				}
			}
			Ref::save(ST_PURCHORDER, $po_obj->reference);
			//DB_Comments::add(ST_PURCHORDER, $po_obj->order_no, $po_obj->orig_order_date, $po_obj->Comments);
			DB_AuditTrail::add(ST_PURCHORDER, $po_obj->order_no, $po_obj->orig_order_date);
			DB::commit_transaction();
			return $po_obj->order_no;
		}

		public static function update(&$po_obj) {
			DB::begin_transaction();
			/*Update the purchase order header with any changes */
			$sql = "UPDATE purch_orders SET Comments=" . DB::escape($po_obj->Comments) . ",
			requisition_no= " . DB::escape($po_obj->requisition_no) . ",
			into_stock_location=" . DB::escape($po_obj->Location) . ",
			ord_date='" . Dates::date2sql($po_obj->orig_order_date) . "',
			delivery_address=" . DB::escape($po_obj->delivery_address) . ",
			freight=" . DB::escape($po_obj->freight) . ",
			salesman=" . DB::escape($po_obj->salesman);
			$sql .= " WHERE order_no = " . $po_obj->order_no;
			DB::query($sql, "The purchase order could not be updated");
			/*Now Update the purchase order detail records */
			foreach ($po_obj->line_items as $po_line)
			{
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
					$sql .= $po_obj->order_no . "," .
					 DB::escape($po_line->stock_id) . "," .
					 DB::escape($po_line->description) . ",'" .
					 Dates::date2sql($po_line->req_del_date) . "'," .
					 DB::escape($po_line->price) . ", " .
					 DB::escape($po_line->quantity) . ", " .
					 DB::escape($po_line->discount) .
					 ")";
				} else {
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
			//DB_Comments::add(ST_PURCHORDER, $po_obj->order_no, $po_obj->orig_order_date, $po_obj->Comments);
			DB::commit_transaction();
			return $po_obj->order_no;
		}

		public static function get_header($order_no, &$order) {
			$sql = "SELECT purch_orders.*, suppliers.supp_name,
	 		suppliers.curr_code, locations.location_name
			FROM purch_orders, suppliers, locations
			WHERE purch_orders.supplier_id = suppliers.supplier_id
			AND locations.loc_code = into_stock_location
			AND purch_orders.order_no = " . DB::escape($order_no);
			$result = DB::query($sql, "The order cannot be retrieved");
			if (DB::num_rows($result) == 1) {
				$myrow = DB::fetch($result);
				$order->order_no = $order_no;
				$order->supplier_id = $myrow["supplier_id"];
				$order->supplier_name = $myrow["supp_name"];
				$order->curr_code = $myrow["curr_code"];
				$order->orig_order_date = Dates::sql2date($myrow["ord_date"]);
				$order->Comments = $myrow["comments"];
				$order->Location = $myrow["into_stock_location"];
				$order->requisition_no = $myrow["requisition_no"];
				$order->reference = $myrow["reference"];
				$order->delivery_address = $myrow["delivery_address"];
				$order->freight = $myrow["freight"];
				$order->salesman = $myrow['salesman'];
				return true;
			}
			Errors::show_db_error("FATAL : duplicate purchase order found", "", true);
			return false;
		}

		public static function get_items($order_no, Purch_Order $order, $open_items_only = false) {
			/*now populate the line po array with the purchase order details records */
			$sql = "SELECT purch_order_details.*, units
			FROM purch_order_details
			LEFT JOIN stock_master
			ON purch_order_details.item_code=stock_master.stock_id
			WHERE order_no =" . DB::escape($order_no);
			if ($open_items_only) {
				$sql .= " AND (purch_order_details.quantity_ordered > purch_order_details.quantity_received) ";
			}
			$sql .= " ORDER BY po_detail_item";
			$result = DB::query($sql, "The lines on the purchase order cannot be retrieved");
			if (DB::num_rows($result) > 0) {
				while ($myrow = DB::fetch($result))
				{
					$data = Purch_Order::get_data($order->supplier_id, $myrow['item_code']);
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
					} else {
						$units = $myrow["units"];
					}
					if ($order->add_to_order($order->lines_on_order + 1, $myrow["item_code"],
						$myrow["quantity_ordered"], $myrow["description"],
						$myrow["unit_price"], $units, Dates::sql2date($myrow["delivery_date"]),
						$myrow["qty_invoiced"], $myrow["quantity_received"], $myrow["discount"])
					) {
						$order->line_items[$order->lines_on_order]->po_detail_rec = $myrow["po_detail_item"];
						$order->line_items[$order->lines_on_order]->standard_cost =
						 $myrow["std_cost_unit"]; /*Needed for receiving goods and GL interface */
					}
				} /* line po from purchase order details */
			} //end of checks on returned data set
		}

		public static function get($order_no, &$order, $open_items_only = false) {
			$result = Purch_Order::get_header($order_no, $order);
			if ($result) {
				Purch_Order::get_items($order_no, $order, $open_items_only);
			}
		}

		public static function	 add_freight(&$po, $date_) {
			$sql = "INSERT INTO purch_order_details (order_no, item_code, description, delivery_date, unit_price, quantity_ordered, discount) VALUES (";
			$sql .= $po->order_no . "," .
			 DB::escape('freight') . "," .
			 DB::escape('Freight Charges') . ",'" .
			 Dates::date2sql($date_) . "'," .
			 DB::escape($po->freight) . ", " .
			 DB::escape(1) . ", " .
			 DB::escape(0) .
			 ")";
			DB::query($sql, "One of the purchase order detail records could not be updated");
			return DB::insert_id();
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
			$data = Purch_Order::get_data($supplier_id, $stock_id);
			if ($data === false) {
				$supplier_code = $stock_id;
				$sql
				 = "INSERT INTO purch_data (supplier_id, stock_id, price, suppliers_uom,
					conversion_factor, supplier_description) VALUES (" . DB::escape($supplier_id)
				 . ", " . DB::escape($stock_id) . ", " . DB::escape($price) . ", "
				 . DB::escape($uom) . ", 1, " . DB::escape($supplier_code) . ")";
				DB::query($sql, "The supplier purchasing details could not be added");
				return;
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

		// ------------------------------------------------------------------------------
		public static function supplier_to_order($order, $supplier_id) {
			$sql = "SELECT * FROM suppliers
			WHERE supplier_id = '$supplier_id'";
			$result = DB::query($sql, "The supplier details could not be retreived");
			$myrow = DB::fetch_assoc($result);
			$order->supplier_details = $myrow;
			$order->curr_code = $_POST['curr_code'] = $myrow["curr_code"];
			$order->supplier_name = $_POST['supplier_name'] = $myrow["supp_name"];
			$order->supplier_id = $_POST['supplier_id'] = $supplier_id;
		}

		public static function create() {
			if (isset($_SESSION['PO'])) {
				unset($_SESSION['PO']->line_items);
				$_SESSION['PO']->lines_on_order = 0;
				unset($_SESSION['PO']);
			}
			//session_register("PO");
			$_SESSION['PO'] = new Purch_Order;
			$_POST['OrderDate'] = Dates::new_doc_date();
			if (!Dates::is_date_in_fiscalyear($_POST['OrderDate'])) {
				$_POST['OrderDate'] = Dates::end_fiscalyear();
			}
			$_SESSION['PO']->orig_order_date = $_POST['OrderDate'];
		}

		public static function header($order) {
			$Ajax = Ajax::i();
			$editable = ($order->order_no == 0);
			start_outer_table('tablestyle2 width90');
			table_section(1);
			if ($editable) {
				if (!isset($_POST['supplier_id']) && Session::i()->supplier_id) {
					$_POST['supplier_id'] = Session::i()->supplier_id;
				}
				Purch_Creditor::row(_("Supplier:"), 'supplier_id', null, false, true, false, true);
			} else {
				if (isset($_POST['supplier_id'])) {
					Purch_Order::supplier_to_order($order, $_POST['supplier_id']);
				}
				hidden('supplier_id', $order->supplier_id);
				label_row(_("Supplier:"), $order->supplier_name, 'class="label" name="supplier_name"');
			}
			if ($order->supplier_id != get_post('supplier_id', -1)) {
				$old_supp = $order->supplier_id;
				Purch_Order::supplier_to_order($order, $_POST['supplier_id']);
				// supplier default price update
				foreach ($order->line_items as $line_no => $item) {
					$line = &$order->line_items[$line_no];
					$line->price = Item_Price::get_purchase($order->supplier_id, $line->stock_id);
					$line->quantity = $line->quantity / Purch_Trans::get_conversion_factor($old_supp,
						$line->stock_id) * Purch_Trans::get_conversion_factor($order->supplier_id, $line->stock_id);
				}
				$Ajax->activate('items_table');
			}
			Session::i()->supplier_id = $_POST['supplier_id'];
			if (!Bank_Currency::is_company($order->curr_code)) {
				label_row(_("Supplier Currency:"), $order->curr_code);
				GL_ExchangeRate::display($order->curr_code, Bank_Currency::for_company(), $_POST['OrderDate']);
			}
			if ($editable) {
				ref_row(_("Purchase Order #:"), 'ref', '', Ref::get_next(ST_PURCHORDER));
			} else {
				hidden('ref', $order->reference);
				label_row(_("Purchase Order #:"), $order->reference);
			}
			Sales_UI::persons_row(_("Sales Person:"), 'salesman', $order->salesman);
			table_section(2);
			date_row(_("Order Date:"), 'OrderDate', '', true, 0, 0, 0, null, true);
			if (isset($_POST['_OrderDate_changed'])) {
				$Ajax->activate('_ex_rate');
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
					$Ajax->activate('delivery_address');
					$_SESSION['PO']->Location = $_POST['StkLocation'];
					$_SESSION['PO']->delivery_address = $_POST['delivery_address'];
				} else { /* The default location of the user is crook */
					Errors::error(_("The default stock location set up for this user is not a currently defined stock location. Your system administrator needs to amend your user record."));
				}
			}
			textarea_row(_("Deliver to:"), 'delivery_address', $_POST['delivery_address'], 35, 4);
			end_outer_table(); // outer table
		}

		public static function display_items($order, $editable = true) {
			$Ajax = Ajax::i();
			Display::heading(_("Order Items"));
			Display::div_start('items_table');
			start_table('tablestyle width90');
			$th = array(
				_("Item Code"), _("Description"), _("Quantity"), _("Received"), _("Unit"), _("Required Date"), _("Price"), _('Discount %'), _("Total"), "");
			if (count($order->line_items)) {
				$th[] = '';
			}
			table_header($th);
			$id = find_submit('Edit');
			$total = 0;
			$k = 0;
			foreach ($order->line_items as $line_no => $po_line) {
				if ($po_line->Deleted == false) {
					$line_total = round($po_line->quantity * $po_line->price * (1 - $po_line->discount), User::price_dec(),
						PHP_ROUND_HALF_EVEN);
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
					} else {
						Purch_Order::item_controls($order, $po_line->stock_id);
					}
					$total += $line_total;
				}
			}
			if ($id == -1 && $editable) {
				Purch_Order::item_controls($order);
			}
			label_cell(_("Freight"), "colspan=8 class=right");
			small_amount_cells(null, 'freight', Num::price_format(get_post('freight', 0)));
			$display_total = Num::price_format($total + Validation::input_num('freight'));
			start_row();
			label_cells(_("Total Excluding Shipping/Tax"), $display_total, "colspan=8 class=right",
				"nowrap class=right _nofreight='$total'", 2);
			end_row();
			end_table(1);
			Display::div_end();
		}

		public static function summary(&$po, $is_self = false, $editable = false) {
			start_table('tablestyle2 width90');
			echo "<tr class='tableheader2 top'><th colspan=4>";
			Display::heading(_("Purchase Order") . " #" . $_GET['trans_no']);
			echo "</td></tr>";
			start_row();
			label_cells(_("Supplier"), $po->supplier_name, "class='label'");
			label_cells(_("Reference"), $po->reference, "class='label'");
			if (!Bank_Currency::is_company($po->curr_code)) {
				label_cells(_("Order Currency"), $po->curr_code, "class='label'");
			}
			if (!$is_self) {
				label_cells(_("Purchase Order"), GL_UI::trans_view(ST_PURCHORDER, $po->order_no), "class='label'");
			}
			end_row();
			start_row();
			label_cells(_("Date"), $po->orig_order_date, "class='label'");
			if ($editable) {
				if (!isset($_POST['Location'])) {
					$_POST['Location'] = $po->Location;
				}
				label_cell(_("Deliver Into Location"), "class='label'");
				Inv_Location::cells(null, 'Location', $_POST['Location']);
			} else {
				label_cells(_("Deliver Into Location"), Inv_Location::get_name($po->Location), "class='label'");
			}
			end_row();
			if (!$editable) {
				label_row(_("Delivery Address"), $po->delivery_address, "class='label'", "colspan=9");
			}
			if ($po->Comments != "") {
				label_row(_("Order Comments"), $po->Comments, "class='label'", "colspan=9");
			}
			end_table(1);
		}

		public static function item_controls($order, $stock_id = null) {
			$Ajax = Ajax::i();
			start_row();
			$dec2 = 0;
			$id = find_submit('Edit');
			if (($id != -1) && $stock_id != null) {
				hidden('line_no', $id);
				$_POST['stock_id'] = $order->line_items[$id]->stock_id;
				$dec = Item::qty_dec($_POST['stock_id']);
				$_POST['qty'] = Item::qty_format($order->line_items[$id]->quantity, $_POST['stock_id'], $dec);
				//$_POST['price'] = Num::price_format($order->line_items[$id]->price);
				$_POST['price'] = Num::price_decimal($order->line_items[$id]->price, $dec2);
				$_POST['discount'] = Num::percent_format($order->line_items[$id]->discount * 100);
				$_POST['req_del_date'] = $order->line_items[$id]->req_del_date;
				$_POST['description'] = $order->line_items[$id]->description;
				$_POST['units'] = $order->line_items[$id]->units;
				hidden('stock_id', $_POST['stock_id']);
				label_cell($_POST['stock_id'], " class='stock' data-stock_id='{$_POST['stock_id']}'");
				textarea_cells(null, 'description', null, 50, 5);
				$Ajax->activate('items_table');
				$qty_rcvd = $order->line_items[$id]->qty_received;
			} else {
				hidden('line_no', ($_SESSION['PO']->lines_on_order + 1));
				Item_Purchase::cells(null, 'stock_id', null, false, true, true);
				if (list_updated('stock_id')) {
					$Ajax->activate('price');
					$Ajax->activate('units');
					$Ajax->activate('description');
					$Ajax->activate('qty');
					$Ajax->activate('discount');
					$Ajax->activate('req_del_date');
					$Ajax->activate('line_total');
				}
				$item_info = Item::get_edit_info(Input::post('stock_id'));
				$_POST['units'] = $item_info["units"];
				$_POST['description'] = '';
				$dec = $item_info["decimals"];
				$_POST['qty'] = Num::format(Purch_Trans::get_conversion_factor($order->supplier_id, Input::post('stock_id')), $dec);
				$_POST['price'] = Num::price_decimal(Item_Price::get_purchase($order->supplier_id, Input::post('stock_id')), $dec2);
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
			//$line_total = $_POST['qty'] * $_POST['price'] * (1 - $_POST['Disc'] / 100);
			$line_total = Validation::input_num('qty') * Validation::input_num('price') * (1 - Validation::input_num('discount') / 100);
			amount_cell($line_total, false, '', 'line_total');
			if ($id != -1) {
				button_cell('UpdateLine', _("Update"), _('Confirm changes'), ICON_UPDATE);
				button_cell('CancelUpdate', _("Cancel"), _('Cancel changes'), ICON_CANCEL);
				JS::set_focus('qty');
			} else {
				submit_cells('EnterLine', _("Add Item"), "colspan=2", _('Add new item to document'), true);
			}
			end_row();
		}
	} /* end of class defintion */


