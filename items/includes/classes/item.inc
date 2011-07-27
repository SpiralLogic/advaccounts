<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 12/4/10
 * Time: 7:37 PM
 * To change this template use File | Settings | File Templates.
 */
	class Item extends DB_abstract {


		public $id;
		public $name;
		public $category_id;
		public $description;
		public $last_cost;
		public $uom;
		public $units;
		public $salePrices = array();
		public $purchPrices = array();
		protected $stockLevels = array();

		public function __construct($id = null) {
			parent::__construct($id);
			$this->stock_id = &$this->id;
			$this->uom = &$this->units;

		}

		protected function _read($id = 0) {
			$sql = "SELECT * FROM stock_master WHERE stock_id = " . db_escape($id);

			$result = db_query($sql, 'Could not retrieve stock information');
			if (db_num_rows($result) == 1) {
				$myrow = db_fetch_assoc($result);
				$this->id = $myrow['stock_id'];
				$this->name = $myrow['description'];
				$this->category_id = $myrow['category_id'];
				$this->description = $myrow['long_description'];
				$this->last_cost = $myrow['last_cost'];
				$this->units = $myrow['units'];
			}

		}

		protected function _canProcess() {
return true;
		}

		protected function _countTransactions() {
			// TODO: Implement _countTransactions() method.
		}

		protected function _defaults() {
			// TODO: Implement _defaults() method.
		}

		protected function _new() {
			// TODO: Implement _new() method.
		}

		protected function _saveNew() {
			// TODO: Implement _saveNew() method.
		}

		public function delete() {
			// TODO: Implement delete() method.
		}

		function save($changes = null) {
			if (is_array($changes)) {
				$this->setFromArray($changes);
			}
			if (!$this->_canProcess()) {
				return false;
			}
			if ($this->id == 0) {
				$this->_saveNew();
			}
			begin_transaction();
			$sql = "UPDATE stock_master SET description=" . db_escape($this->name) . ",
					long_description=" . db_escape($this->description) . ",
					category_id=" . db_escape($this->category_id) . ",
					stock_id=" . db_escape($this->id) . ",
					units=" . db_escape($this->units) . "
		            WHERE stock_id = " . db_escape($this->id);

			db_query($sql, "The item could not be updated");
			$sql = "UPDATE item_codes SET stock_id=" . db_escape($this->id) . ",
							category_id=" . db_escape($this->category_id) . ",
							description=" . db_escape($this->name) . ",
							item_code=" . db_escape($this->id) . "
							WHERE stock_id = " . db_escape($this->id);
			db_query($sql, "The item could not be updated");
			commit_transaction();
			return $this->_status(true, 'Processing', "Item has been updated.");
		}

		function  getSalePrices() {
			$sql = "SELECT * FROM prices WHERE stock_id = " . $this->id;
			$result = db_query($sql, 'Could not get item pricing');
			while ($row = db_fetch_assoc($result)) {
				$this->prices[$row['id']] = array("curr" => $row['curr_abrev'], "type" => $row['type'], "price" => $row['price']);
			}
		}

		function  getPurchPrices() {
			$sql = "SELECT * FROM purch_data WHERE stock_id = " . $this->id;
			$result = db_query($sql, 'Could not get item pricing');
			while ($row = db_fetch_assoc($result)) {
				$this->prices[$row['supplier_id']] =
						array("code" => $row['supplier_description'], "price" => $row['price'], "suppliers_uom" => $row['uom'], "conv" => $row['conversion_factor']);
			}
		}

		function  getStockLevels() {
			if (!$this->id > 0) {
				return;
			}
			$sql = "SELECT l.loc_code, l.location_name, i.stock_id as id, r.reorder_level, o.demand, (qty-o.demand) as available, p.onorder, qty
			FROM locations l
			LEFT JOIN (SELECT stock_id, loc_code, SUM(qty) as qty FROM stock_moves WHERE stock_id=" . db_escape($this->id) . " AND tran_date <= '" . date2sql(Today()) . "' GROUP BY loc_code) i ON l.loc_code = i.loc_code
					LEFT JOIN loc_stock r ON r.loc_code = l.loc_code AND r.stock_id = " . db_escape($this->id) . "
					LEFT JOIN (SELECT SUM(sales_order_details.quantity - sales_order_details.qty_sent) AS demand , sales_orders.from_stk_loc AS loc_code FROM sales_order_details, sales_orders
					WHERE sales_order_details.order_no= sales_orders.order_no AND sales_orders.trans_type=30 AND sales_orders.trans_type=sales_order_details.trans_type AND sales_order_details.stk_code = " . db_escape($this->id) . "
					GROUP BY sales_orders.from_stk_loc) o ON o.loc_code=l.loc_code
					LEFT JOIN (SELECT SUM(purch_order_details.quantity_ordered - purch_order_details.quantity_received) AS onorder , purch_orders.into_stock_location AS loc_code
					FROM purch_order_details, purch_orders	WHERE purch_order_details.order_no= purch_orders.order_no AND purch_order_details.item_code = " . db_escape($this->id) . " GROUP BY purch_orders.into_stock_location) p ON p.loc_code=l.loc_code";

			$result = db_query($sql, 'Could not get item stock levels');
			while ($row = db_fetch_assoc($result)) {
				$this->stockLevels[] = $row;
			}
			return $this->stockLevels;
		}

		function getStockOnOrder() {
			$sql = "SELECT SUM(sales_order_details.quantity - sales_order_details.qty_sent) AS demand , sales_orders.from_stk_loc AS loc_code FROM sales_order_details, sales_orders WHERE sales_order_details.order_no= sales_orders.order_no AND sales_orders.trans_type=30 AND sales_orders.trans_type=sales_order_details.trans_type AND sales_order_details.stk_code = " . db_escape($this->id) . "' GROUP BY sales_orders.from_stk_loc";

			$result = db_query($sql, "No transactions were returned");
			$row = db_fetch($result);
			if ($row === false) {
				return 0;
			}
			return $row['QtyDemand'];
		}

		static function search($term) {
			$sql = "SELECT stock_id as id, description as label, stock_id as value FROM stock_master " . "where stock_id LIKE '%" . $term . "%' OR description LIKE '%" . $term . "%' LIMIT 200";
			$result = db_query($sql, 'Couldn\'t Get Items');
			while ($row = db_fetch_assoc($result)) {
				$data[] = $row;
			}
			return $data;
		}

		static function addEditDialog($options = array()) {
			$default = array('page' => 0);
			$options = array_merge($default, $options);
			extract($options);
			$stockbox = new Dialog('Item Edit', 'stockbox', '');
			$stockbox->addButtons(array('Save' => 'Item.save()', 'Close' => '$(this).dialog("close");'));

			$stockbox->setOptions(array('autoopen' => false, 'modal' => true, 'width' => '"75%"', 'resizeable' => true));
			$stockbox->show();
			$action = <<<JS
		$('#stockbox').html("<iframe src='/items/quickitems.php?id="+$(this).data('stock_id')+"&page={$page}' width='100%' height='600' scrolling='no' style='border:none' frameborder='0'></iframe>").dialog('open');
JS;
			JS::addLiveEvent('.stock', 'click', $action, "#wrapper");

		}
	}
