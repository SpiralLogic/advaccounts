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

		public function __construct($id = 0) {
			$this->id=$id;
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
			// TODO: Implement _canProcess() method.
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
			// TODO: Implement save() method.
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
			$sql = "SELECT l.loc_code, l.location_name, r.stock_id as id, r.reorder_level
		FROM locations l LEFT JOIN loc_stock r ON r.loc_code=l.loc_code AND r.stock_id = " . db_escape($this->id)
					. " LEFT JOIN (SELECT stock_id, SUM(qty) as qty FROM stock_moves
		WHERE stock_id=" . db_escape($this->id) . " AND tran_date <= '".Today()."') i
		ON i.stock_id=r.stock_id ORDER BY r.loc_code";
			FB::info($sql);
			$result = db_query($sql, 'Could not get item stock levels');
			while ($row = db_fetch_assoc($result)) {
				$this->stockLevels[] = $row;
			}
			return $this->stockLevels;
		}

		static function search($term) {
			$sql = "SELECT stock_id as id, description as label, stock_id as value FROM stock_master " . "where stock_id LIKE '%" . $term . "%' OR description LIKE '%" . $term . "%' LIMIT 200";
			$result = db_query($sql, 'Couldn\'t Get Items');
			while ($row = db_fetch_assoc($result)) {
				$data[] = $row;
			}
			return $data;
		}

	}
