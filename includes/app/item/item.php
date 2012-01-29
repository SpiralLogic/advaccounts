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
	 **********************************************************************
	 */
	class Item extends DB_abstract
	{
		public $id = 0;
		public $stock_id;
		public $tax_type_id = 1;
		public $mb_flag = STOCK_MANUFACTURE;
		public $sales_account = null;
		public $inventory_accounts = null;
		public $cogs_account = null;
		public $adjustment_account = null;
		public $assembly_account = null;
		public $dimension_id = 0;
		public $dimension2_id = 0;
		public $actual_cost = 0;
		public $last_cost = 0;
		public $material_cost = 0;
		public $labour_cost = 0;
		public $overhead_cost = 0;
		public $inactive = false;
		public $no_sale = false;
		public $editable = 0;
		public $tax_type_name = 'GST';
		public $name;
		public $category_id = 6;
		public $description;
		public $long_description;
		public $uom;
		public $units = 'ea';
		public $salePrices = array();
		public $purchPrices = array();
		protected $stockLevels = array();
		static public $qoh_stock;
		/***
		 * @param Item $id
		 */
		public function __construct($id = 0) {
			parent::__construct($id);
			$this->uom = &$this->units;
			$this->name = &$this->description;
		}
		protected function _read($id = 0) {
			if (!is_numeric($id)) {
				$stockid = static::getStockID((string)$id);
				if ($stockid) {
					$id = $stockid;
				}
			}
			$result = DB::select()->from('stock_master')->where('id=', $id)->fetch()->intoObject($this);
		}
		protected function _canProcess() {
			return true;
		}
		protected function _countTransactions() {
			// TODO: Implement _countTransactions() method.
		}
		protected function _defaults() {
			$this->id = 0;
			$this->stock_id = null;
			$this->tax_type_id = 1;
			$this->mb_flag = STOCK_PURCHASED;
			$this->sales_account = DB_Company::i()->default_inv_sales_act;
			if (STOCK_MANUFACTURE || STOCK_PURCHASED) {
				$this->inventory_accounts = DB_Company::i()->default_inventory_act;
			}
			$this->cogs_account = DB_Company::i()->default_cogs_act;
			$this->adjustment_account = DB_Company::i()->default_adj_act;
			if (STOCK_MANUFACTURE) {
				$this->assembly_account = DB_Company::i()->default_assembly_act;
			}
			$this->actual_cost = 0;
			$this->last_cost = 0;
			$this->material_cost = 0;
			$this->labour_cost = 0;
		}
		protected function _new() {
			$this->_defaults();
			return $this->_status(true, 'Initialize new Item', 'Now working with a new Item');
		}
		protected function _saveNew() {
			//	DB::begin();
			$data = (array)$this;
			unset($data['id']);
			$this->id = DB::insert('stock_master')->values($data)->exec();
			$sql = "INSERT INTO stock_location (loc_code, stock_id) SELECT locations.loc_code, " . DB::escape($this->stock_id) . " FROM locations";
			$result = DB::query($sql, "The item locstock could not be added");
			DB::fetch($result);
			Item_Code::add($this->stock_id, $this->stock_id, $this->description, $this->category_id, 1, 0);
			//	DB::commit();
			return $this->_status(true, 'Processing', "Item has been updated.");
		}
		public function delete() {
			// TODO: Implement delete() method.
		}
		public function save($changes = null) {
			if (is_array($changes)) {
				$this->setFromArray($changes);
			}
			if (!$this->_canProcess()) {
				return false;
			}
			if ($this->id == 0) {
				return $this->_saveNew();
			}
			DB::begin();
			$result = DB::update('stock_master')->values((array)$this)->where('id=', $this->id)->exec();
			DB::commit();
			return $this->_status(true, 'Processing', "Item has been updated.");
		}
		public function	getSalePrices() {
			$sql = "SELECT * FROM prices WHERE stockid = " . $this->id;
			$result = DB::query($sql, 'Could not get item pricing');
			while ($row = DB::fetch_assoc($result)) {
				$this->salePrices[$row['id']] = array(
					"curr" => $row['curr_abrev'], "type" => $row['type'], "price" => $row['price']
				);
			}
		}
		public function	getPurchPrices($option = array()) {
			$sql = "SELECT * FROM purch_data WHERE stockid = " . $this->id;
			if ($option['min']) {
				$sql .= " ORDER BY price LIMIT 1";
			}
			$result = DB::query($sql, 'Could not get item pricing');
			if ($option['min']) {
				return DB::fetch_assoc($result);
			}
			while ($row = DB::fetch_assoc($result)) {
				$this->salePrices[$row['supplier_id']] = array(
					"code" => $row['supplier_description'], "price" => $row['price'], //					"suppliers_uom" => $row['uom'],
					"conv" => $row['conversion_factor']
				);
			}
			return $this->salePrices;
		}
		public function	getStockLevels($location = null) {
			if (!$this->id > 0) {
				return false;
			}
			$id = $this->id;
			$sql
			 = "SELECT l.loc_code, l.location_name, r.shelf_primary, r.shelf_secondary, i.stock_id as id, r.reorder_level, o.demand, (qty-o.demand) as available, p.onorder, qty
			FROM locations l
			LEFT JOIN (SELECT stock_id, loc_code, SUM(qty) as qty FROM stock_moves WHERE stockid=$id AND tran_date <= now() GROUP BY loc_code, stock_id) i ON l.loc_code = i.loc_code
			LEFT JOIN stock_location r ON r.loc_code = l.loc_code AND r.stockid = $id
			LEFT JOIN (SELECT SUM(sales_order_details.quantity - sales_order_details.qty_sent) AS demand , sales_orders.from_stk_loc AS loc_code FROM sales_order_details, sales_orders
				WHERE sales_order_details.order_no= sales_orders.order_no AND sales_orders.trans_type=30 AND sales_orders.trans_type=sales_order_details.trans_type
				AND sales_order_details.stockid = $id GROUP BY sales_orders.from_stk_loc) o ON o.loc_code=l.loc_code
			LEFT JOIN (SELECT SUM(purch_order_details.quantity_ordered - purch_order_details.quantity_received) AS onorder , purch_orders.into_stock_location AS loc_code
				FROM purch_order_details, purch_orders	WHERE purch_order_details.order_no= purch_orders.order_no AND purch_order_details.stockid = $id
				GROUP BY purch_orders.into_stock_location) p ON p.loc_code=l.loc_code";
			if ($location !== null) {
				$sql .= " WHERE l.loc_code=" . DB::escape($location);
			}
			$result = DB::query($sql, 'Could not get item stock levels');
			if ($location !== null) {
				return DB::fetch_assoc($result);
			}
			while ($row = DB::fetch_assoc($result)) {
				$row['demand'] = ($row['demand']) ? : 0;
				$row['qty'] = ($row['qty']) ? : 0;
				$row['available'] = ($row['available']) ? : 0;
				$row['onorder'] = ($row['onorder']) ? : 0;
				$this->stockLevels[] = $row;
			}
			return $this->stockLevels;
		}
		public function getStockOnOrder() {
			$sql = "SELECT SUM(sales_order_details.quantity - sales_order_details.qty_sent) AS demand , sales_orders.from_stk_loc AS loc_code FROM sales_order_details, sales_orders WHERE sales_order_details.order_no= sales_orders.order_no AND sales_orders.trans_type=30 AND sales_orders.trans_type=sales_order_details.trans_type AND sales_order_details.stockid = " . DB::escape($this->id) . "' GROUP BY sales_orders.from_stk_loc";
			$result = DB::query($sql, "No transactions were returned");
			$row = DB::fetch($result);
			if ($row === false) {
				return 0;
			}
			return $row['QtyDemand'];
		}
		static public function get_all() {
			$sql = "SELECT * FROM stock_master";
			return DB::query($sql, "items could not be retreived");
		}
		/**
		 * @param			$stock_id
		 * @param null $location
		 * @param null $date_
		 * @param int	$exclude
		 *
		 * @return mixed
		 */
		static public function get_qoh_on_date($stock_id, $location = null, $date_ = null, $exclude = 0) {
			if ($date_ == null) {
				$date_ = Dates::Today();
			}
			$date = Dates::date2sql($date_);
			$sql
			 = "SELECT SUM(qty) FROM stock_moves
	 		WHERE stock_id=" . DB::escape($stock_id) . "
	 		AND tran_date <= '$date'";
			if ($location != null) {
				$sql .= " AND loc_code = " . DB::escape($location);
			}
			$result = DB::query($sql, "QOH calulcation failed");
			$myrow = DB::fetch_row($result);
			if ($exclude > 0) {
				$sql
				 = "SELECT SUM(qty) FROM stock_moves
	 			WHERE stock_id=" . DB::escape($stock_id) . " AND type=" . DB::escape($exclude) . " AND tran_date = '$date'";
				$result = DB::query($sql, "QOH calulcation failed");
				$myrow2 = DB::fetch_row($result);
				if ($myrow2 !== false) {
					$myrow[0] -= $myrow2[0];
				}
			}
			return $myrow[0];
		}
		static public function get_edit_info($stock_id) {
			$sql
			 = "SELECT material_cost + labour_cost + overhead_cost AS standard_cost, units, decimals
	 		FROM stock_master,item_units
	 		WHERE stock_id=" . DB::escape($stock_id) . " AND stock_master.units=item_units.abbr";
			$query = DB::query($sql, "The standard cost cannot be retrieved");
			$result = array(
				'standard_cost' => 0, 'units' => 'ea', 'decimals' => User::price_dec()
			);
			if (DB::num_rows($query) == 0) {
				$result = DB::fetch($query);
			}
			return $result;
		}
		static public function is_inventory_item($stock_id) {
			$sql
			 = "SELECT stock_id FROM stock_master
	 		WHERE stock_id=" . DB::escape($stock_id) . " AND mb_flag <> 'D'";
			$result = DB::query($sql, "Cannot query is inventory item or not");
			return DB::num_rows($result) > 0;
		}
		static public function last_negative_stock_begin_date($stock_id, $to) {
			$to = Dates::date2sql($to);
			$sql = "SET @q = 0";
			DB::query($sql);
			$sql = "SET @flag = 0";
			DB::query($sql);
			$sql
			 = "SELECT SUM(qty), @q:= @q + qty, IF(@q < 0 AND @flag=0, @flag:=1,@flag:=0), IF(@q < 0 AND @flag=1, tran_date,'') AS begin_date
	 		FROM stock_moves
	 		WHERE stock_id=" . DB::escape($stock_id) . " AND tran_date<='$to'
	 		AND qty <> 0
	 		GROUP BY stock_id ORDER BY tran_date";
			$result = DB::query($sql, "The dstock moves could not be retrieved");
			$row = DB::fetch_row($result);
			return $row[3];
		}
		static public function get_deliveries_between($stock_id, $from, $to) {
			$from = Dates::date2sql($from);
			$to = Dates::date2sql($to);
			$sql
			 = "SELECT SUM(-qty), SUM(-qty*standard_cost) FROM stock_moves
	 		WHERE type=" . ST_CUSTDELIVERY . " AND stock_id=" . DB::escape($stock_id) . " AND
	 			tran_date>='$from' AND tran_date<='$to' GROUP BY stock_id";
			$result = DB::query($sql, "The deliveries could not be updated");
			return DB::fetch_row($result);
		}
		static public function adjust_deliveries($stock_id, $material_cost, $to) {
			if (!Item::is_inventory_item($stock_id)) {
				return;
			}
			$from = Item::last_negative_stock_begin_date($stock_id, $to);
			if ($from == false || $from == "") {
				return;
			}
			$from = Dates::sql2date($from);
			$row = Item::get_deliveries_between($stock_id, $from, $to);
			if ($row == false) {
				return;
			}
			$old_cost = $row[1];
			$new_cost = $row[0] * $material_cost;
			$diff = $new_cost - $old_cost;
			if ($diff != 0) {
				$update_no = SysTypes::get_next_trans_no(ST_COSTUPDATE);
				if (!Dates::is_date_in_fiscalyear($to)) {
					$to = Dates::end_fiscalyear();
				}
				$stock_gl_code = Item::get_gl_code($stock_id);
				$memo_ = _("Cost was ") . $old_cost . _(" changed to ") . $new_cost . _(" for item ") . "'$stock_id'";
				GL_Trans::add_std_cost(ST_COSTUPDATE, $update_no, $to, $stock_gl_code["cogs_account"], $stock_gl_code["dimension_id"], $stock_gl_code["dimension2_id"], $memo_, $diff);
				GL_Trans::add_std_cost(ST_COSTUPDATE, $update_no, $to, $stock_gl_code["inventory_account"], 0, 0, $memo_, -$diff);
				DB_AuditTrail::add(ST_COSTUPDATE, $update_no, $to);
			}
		}
		static public function get_gl_code($stock_id) {
			/*Gets the GL Codes relevant to the item account */
			$sql
			 = "SELECT inventory_account, cogs_account,
	 		adjustment_account, sales_account, assembly_account, dimension_id, dimension2_id FROM
	 		stock_master WHERE stock_id = " . DB::escape($stock_id);
			$get = DB::query($sql, "retreive stock gl code");
			return DB::fetch($get);
		}
		/***
		 * @static
		 *
		 * @param $stock_id
		 *
		 * @return string
		 */
		static public function img_name($stock_id) {
			return strtr($stock_id, "><\\/:|*?", '________');
		}
		static public function get_stockid($stock_id) {
			$result = current(DB::select('id')->from('stock_master')->where('stock_id LIKE ', $stock_id)->fetch()->all());
			return $result['id'];
		}
		static public function get_demand($stock_id, $location) {
			$sql = "SELECT SUM(sales_order_details.quantity - " . "sales_order_details.qty_sent) AS QtyDemand
						FROM sales_order_details,
								sales_orders
							WHERE sales_order_details.order_no=" . "sales_orders.order_no AND sales_orders.trans_type=" . ST_SALESORDER . " AND
							sales_orders.trans_type=sales_order_details.trans_type AND ";
			if ($location != "") {
				$sql .= "sales_orders.from_stk_loc =" . DB::escape($location) . " AND ";
			}
			$sql .= "sales_order_details.stk_code = " . DB::escape($stock_id);
			$result = DB::query($sql, "No transactions were returned");
			$row = DB::fetch($result);
			if ($row === false) {
				return 0;
			}
			return $row['QtyDemand'];
		}
		static protected function load_stock_levels($location = '') {
			$date = Dates::date2sql(Dates::Today());
			$sql = "SELECT stock_id, SUM(qty) FROM stock_moves WHERE tran_date <= '$date'";
			if ($location != '') {
				$sql .= " AND loc_code = " . DB::escape($location);
			}
			$sql .= " GROUP BY stock_id";
			$result = DB::query($sql, "QOH calulcation failed");
			while ($row = DB::fetch($result)) {
				static::$qoh_stock[$row[0]] = $row[1];
			}
		}
		static public function search($term) {
			$term = DB::quote("%$term%");
			$sql = "SELECT stock_id AS id, description AS label, stock_id AS value FROM stock_master WHERE stock_id LIKE $term OR description LIKE $term LIMIT 200";
			$result = DB::query($sql, 'Couldn\'t Get Items');
			$data = DB::fetch_all();
			array_walk_recursive($data, function(&$v) {
				$v = htmlspecialchars_decode($v, ENT_QUOTES);
			});
			return $data;
		}
		static public function searchSale($terms) {
			$terms = explode(' ', trim($terms));
			$stockid = array_shift($terms);
			$where = 'OR (s.long_description LIKE ? ';
			$finalterms = array($stockid, $stockid . '%', '%' . $stockid . '%', '%' . $stockid . '%');
			foreach ($terms as $t) {
				$where .= ' AND s.long_description LIKE ? ';
				$finalterms[] = '%' . trim($t) . '%';
			}
			$sql
			 = "SELECT p.price, c.description as category, s.* FROM ((SELECT s.stock_id, i.id, s.description, s.long_description ,
s.category_id, editable, 0 as kit,
										IF(s.stock_id LIKE ?, 0,20) + IF(s.stock_id LIKE ?,0,5) + 0 as weight FROM item_codes i,
										stock_master s
										WHERE (s.stock_id LIKE ? $where)) AND s.inactive = 0 AND s.no_sale =0 AND i.item_code=i.stock_id AND i
										.stockid=s.id
										AND !i.is_foreign ORDER BY weight
										LIMIT 20)";
			$where = 'OR (i.description LIKE ? ';
			$finalterms[] = $stockid;
			$finalterms[] = $stockid . '%';
			$finalterms[] = '%' . $stockid . '%';
			$finalterms[] = '%' . $stockid . '%';
			foreach ($terms as $t) {
				if (strlen(trim($t)) == 0) {
					continue;
				}
				$where .= ' AND s.long_description LIKE ? ';
				$finalterms[] = '%' . trim($t) . '%';
			}
			$sql
			 .= "UNION (SELECT i.item_code as stock_id, i.id, i.description,
						 i.description as long_description, i.category_id, 1 as editable, 1 as kit,
						 IF(i.item_code LIKE ?, 0,20) + IF(i.item_code LIKE ?,0,5) as weight FROM item_codes i
						 WHERE (i.item_code LIKE ? $where)) AND !i.is_foreign AND i.item_code!=i.stock_id
						 AND i.inactive = 0 GROUP BY i.item_code ORDER BY weight
						 LIMIT 5)) as s , stock_category c, prices p WHERE s.id = p.item_code_id AND p.sales_type_id =1 AND
						 s.category_id = c.category_id GROUP BY s.stock_id ORDER BY s.weight, s.category_id, s.stock_id ";
			DB::prepare($sql, true);
			DB::execute($finalterms, true);
			exit();
		}
		static public function searchOrder($term, $UniqueID) {
			$o = Cache::get($UniqueID);
			$term = explode(' ', trim($term));
			$item_code = trim(array_shift($term));
			$terms = array($item_code, '%' . $item_code . '%');
			$terms = array($item_code, $item_code . '%', $terms[1], $terms[1], $terms[1]);
			$termswhere = ' OR i.long_description LIKE ? ';
			$where = '';
			foreach ($term as $t) {
				$where .= ' AND i.long_description LIKE ? ';
				$terms[] = '%' . trim($t) . '%';
			}
			$where .= ($o['inactive'] ? '' : ' AND s.inactive = 0 ') . ($o['no_sale'] ? '' : ' AND i.no_sale =0 ');
			$where2 = (!empty($o['where']) ? ' AND ' . $o['where'] : ' ');
			if ($o['type'] == 'local') {
				$where2 .= " AND !s.is_foreign ";
			}
			$stock_code = " s.item_code as stock_id,";
			$where2 .= ' AND i.id = s.stockid ';
			$sales_type = $prices = '';
			$weight = 'IF(s.item_code LIKE ?, 0,20) + IF(s.item_code LIKE ?,0,5) + IF(s.item_code LIKE ?,0,5) as weight';
			if ($o['purchase']) {
				array_unshift($terms, $item_code);
				$weight = 'IF(s.item_code LIKE ?, 0,20) + IF(p.supplier_description LIKE ?, 0,15) + IF(s.item_code LIKE ?,0,5) as weight';
				$termswhere .= ' OR p.supplier_description LIKE ? ';
				if (Input::session('supplier_id', Input::NUMERIC)) {
					array_unshift($terms, $_SESSION['supplier_id']);
					$weight = ' IF(p.supplier_id = ?,0,20) + ' . $weight;
				}
				$stock_code = ' s.item_code as stock_id, p.supplier_description, MIN(p.price) as price, ';
				$prices = " LEFT OUTER JOIN purch_data p ON i.id = p.stockid ";
			}
			elseif ($o['sale']) {
				$weight = 'IF(s.item_code LIKE ?, 0,20) + IF(s.item_code LIKE ?,0,5) + IF(s.item_code LIKE ?,0,5) as weight';
				$stock_code = " s.item_code as stock_id, p.price,";
				$prices = ", prices p";
				$where .= " AND s.id = p.item_code_id ";
				if (isset($o['sales_type'])) {
					$sales_type = ' AND p.sales_type_id =' . $o['sales_type'];
				}
			}
			elseif ($o['kitsonly']) {
				$where .= " AND s.stock_id!=i.stock_id ";
			}
			$select = ($o['select']) ? $o['select'] : ' ';
			$sql
			 = "SELECT $select $stock_code i.description as item_name, c.description as category, i.long_description as description , editable,
							$weight FROM stock_category c, item_codes s, stock_master i $prices
							WHERE (s.item_code LIKE ? $termswhere) $where
							AND s.category_id = c.category_id $where2 $sales_type GROUP BY s.item_code
							ORDER BY weight, s.category_id, s.item_code LIMIT 30";
			DB::prepare($sql);

			$result = DB::execute($terms);
			return $result;
		}
		static public function addEditDialog($options = array()) {
			$default = array('page' => 0);
			$o = array_merge($default, $options);
			$stockbox = new Dialog('Item Edit', 'stockbox', '');
			$stockbox->addButtons(array(
																 'Save' => 'var item =$("#stockframe")[0].contentWindow.Items; item.save(); if (item.get().id==$("#stock_id").val()){ Adv.Forms.setFormValue("description",
				item.get().description)} $(this).dialog("close")', 'Close' => '$(this).dialog("close");'
														));
			$stockbox->setOptions(array(
																 'autoopen' => false, 'modal' => true, 'width' => 940, 'height' => 630, 'resizeable' => true
														));
			$stockbox->show();
			$action
			 = <<<JS
			$('#stockbox').html("<iframe src='/items/quickitems.php?frame=1&stock_id="+$(this).data('stock_id')+"&page={$o['page']}' id='stockframe' style='width:100%' height='500' scrolling='no' style='border:none' frameborder='0'></iframe>").dialog('open');
JS;
			JS::addLiveEvent('.stock', 'dblclick', $action, "wrapper", true);
			JS::addLiveEvent('label.stock', 'click', $action, "wrapper", true);
		}
		/**
		 * @static
		 *
		 * @param			 $id
		 * @param array $options 'description' => false,<br>
		'disabled' => false,<br>
		'editable' => true,<br>
		'selected' => '',<br>
		'label' => false,<br>
		'cells' => false,<br>
		'inactive' => false,<br>
		'purchase' => false,<br>
		'sale' => false,<br>
		'js' => '',<br>
		'selectjs' => '',<br>
		'submitonselect' => '',<br>
		'sales_type' => 1,<br>
		'no_sale' => false,<br>
		'select' => false,<br>
		'type' => 'local',<br>
		'kits'=>true,<br>
		'where' => '',<br>
		'size'=>'20px'<br>
		 */
		static public function addSearchBox($id, $options = array()) {
			echo UI::searchLine($id, '/items/search.php', $options);
		}
		static public function getStockID($stock_code) {
			return DB::select('id')->from('stock_master')->where('stock_id LIKE', $stock_code)->fetch()->one('id');
		}
		static public function	update($stock_id, $description, $long_description, $category_id, $tax_type_id, $units = '', $mb_flag = '', $sales_account, $inventory_account, $cogs_account, $adjustment_account, $assembly_account, $dimension_id, $dimension2_id, $no_sale) {
			$sql = "UPDATE stock_master SET long_description=" . DB::escape($long_description) . ",
		 		description=" . DB::escape($description) . ",
		 		category_id=" . DB::escape($category_id) . ",
		 		sales_account=" . DB::escape($sales_account) . ",
		 		inventory_account=" . DB::escape($inventory_account) . ",
		 		cogs_account=" . DB::escape($cogs_account) . ",
		 		adjustment_account=" . DB::escape($adjustment_account) . ",
		 		assembly_account=" . DB::escape($assembly_account) . ",
		 		dimension_id=" . DB::escape($dimension_id) . ",
		 		dimension2_id=" . DB::escape($dimension2_id) . ",
		 		tax_type_id=" . DB::escape($tax_type_id) . ",
		 		no_sale=" . DB::escape($no_sale);
			if ($units != '') {
				$sql .= ", units='$units'";
			}
			if ($mb_flag != '') {
				$sql .= ", mb_flag='$mb_flag'";
			}
			$sql .= " WHERE stock_id=" . DB::escape($stock_id);
			DB::query($sql, "The item could not be updated");
			Item_Code::update(-1, $stock_id, $stock_id, $description, $category_id, 1, 0);
		}
		static public function	add($stock_id, $description, $long_description, $category_id, $tax_type_id, $units, $mb_flag, $sales_account, $inventory_account, $cogs_account, $adjustment_account, $assembly_account, $dimension_id, $dimension2_id, $no_sale) {
			$sql
			 = "INSERT INTO stock_master (stock_id, description, long_description, category_id,
		 		tax_type_id, units, mb_flag, sales_account, inventory_account, cogs_account,
		 		adjustment_account, assembly_account, dimension_id, dimension2_id, no_sale)
		 		VALUES (" . DB::escape($stock_id) . ", " . DB::escape($description) . ", " . DB::escape($long_description) . ",
		 		" . DB::escape($category_id) . ", " . DB::escape($tax_type_id) . ", " . DB::escape($units) . ", " . DB::escape($mb_flag) . ",
		 		" . DB::escape($sales_account) . ", " . DB::escape($inventory_account) . ", " . DB::escape($cogs_account) . "," . DB::escape($adjustment_account) . ", " . DB::escape($assembly_account) . ", " . DB::escape($dimension_id) . ", " . DB::escape($dimension2_id) . "," . DB::escape($no_sale) . ")";
			DB::query($sql, "The item could not be added");
			$sql
			 = "INSERT INTO stock_location (loc_code, stock_id)
		 		SELECT locations.loc_code, " . DB::escape($stock_id) . " FROM locations";
			DB::query($sql, "The item locstock could not be added");
			Item_Code::add($stock_id, $stock_id, $description, $category_id, 1, 0);
		}
		static public function del($stock_id) {
			$sql = "DELETE FROM stock_master WHERE stock_id=" . DB::escape($stock_id);
			DB::query($sql, "could not delete stock item");
			/*and cascade deletes in stock_location */
			$sql = "DELETE FROM stock_location WHERE stock_id=" . DB::escape($stock_id);
			DB::query($sql, "could not delete stock item loc stock");
			/*and cascade deletes in purch_data */
			$sql = "DELETE FROM purch_data WHERE stock_id=" . DB::escape($stock_id);
			DB::query($sql, "could not delete stock item purch data");
			/*and cascade deletes in prices */
			$sql = "DELETE FROM prices WHERE stock_id=" . DB::escape($stock_id);
			DB::query($sql, "could not delete stock item prices");
			/*and cascade delete the bill of material if any */
			$sql = "DELETE FROM bom WHERE parent=" . DB::escape($stock_id);
			DB::query($sql, "could not delete stock item bom");
			Item_Code::delete_kit($stock_id);
		}
		static public function get($stock_id) {
			$sql
			 = "SELECT stock_master.*,item_tax_types.name AS tax_type_name
		 		FROM stock_master,item_tax_types
		 		WHERE item_tax_types.id=stock_master.tax_type_id
		 		AND stock_id=" . DB::escape($stock_id);
			$result = DB::query($sql, "an item could not be retreived");
			return DB::fetch($result);
		}
		static public function	qty_format($number, $stock_id = null, &$dec) {
			$dec = Item::qty_dec($stock_id);
			return Num::format($number, $dec);
		}
		static public function	qty_dec($stock_id = null) {
			if (is_null($stock_id)) {
				$dec = User::qty_dec();
			}
			else {
				$dec = Item_Unit::get_decimal($stock_id);
				if ($dec == -1) {
					$dec = User::qty_dec();
				}
			}
			return $dec;
		}
		static public function select($name, $selected_id = null, $all_option = false, $submit_on_change = false, $opts = array(), $editkey = false, $legacy = false) {
			if (!$legacy) {
				return Item::addSearchBox($name, array_merge(array(
																													'submitonselect' => $submit_on_change, 'selected' => $selected_id,
																													'purchase' => true, 'cells' => true
																										 ), $opts));
			}
			$sql
			 = "SELECT stock_id, s.description, c.description, s.inactive, s.editable, s.long_description
					FROM stock_master s,stock_category c WHERE s.category_id=c.category_id";
			if ($editkey) {
				Display::set_editor('item', $name, $editkey);
			}
			return select_box($name, $selected_id, $sql, 'stock_id', 's.description', array_merge(array(
																																																 'format' => '_format_stock_items',
																																																 'spec_option' => $all_option === true ?
																																																	_("All Items") :
																																																	$all_option,
																																																 'spec_id' => ALL_TEXT,
																																																 'search_box' => false,
																																																 'search' => array(
																																																	 "stock_id", "c.description",
																																																	 "s.description"
																																																 ),
																																																 'search_submit' => DB_Company::get_pref('no_item_list') != 0,
																																																 'size' => 10,
																																																 'select_submit' => $submit_on_change,
																																																 'category' => 2,
																																																 'order' => array(
																																																	 'c.description', 'stock_id'
																																																 ),
																																																 'editable' => 30,
																																																 'max' => 50
																																														), $opts));
		}
		static public function cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $all = false, $editkey = false, $legacy = false) {
			if ($label != null) {
				echo "<td>$label</td>\n";
			}
			echo Item::select($name, $selected_id, $all_option, $submit_on_change, array(
																																									'submitonselect' => $submit_on_change,
																																									'cells' => true,
																																									'purchase' => false,
																																									'show_inactive' => $all,
																																									'editable' => $editkey
																																						 ), $editkey, $legacy);
		}
	}
