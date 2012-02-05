<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 14/09/11
	 * Time: 2:32 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class Item_Price
	{
		const PURCHASE = 1;
		const SALE = 2;
		const SORT_UPDATE = 'last_update';
		const SORT_PRICE = 'price';
		const SORT_CODE = 'stock_id';
		public $stockid;
		protected $_type;

		static function getPrices($stockid, $type = self::SALE, $sort = self::SORT_PRICE) {
			switch ($type) {
				case self::PURCHASE:
					$result = DB::select()->from('purch_data')->where('stockid=', $stockid)->orderby($sort)->fetch()
					 ->asClassLate('Item_Price', array(self::PURCHASE))->all();
					break;
				case self::SALE:
					$result = DB::select()->from('prices')->where('stockid=', $stockid)->orderby($sort)->fetch()
					 ->asClassLate('Item_Price', array(self::SALE))->all();
					break;
				default:
					throw Exception();
			}
			if ($sort != self::SORT_CODE) {
				$result = array_reverse($result);
			}
			return $result;
		}

		static function getPriceBySupplier($stockid, $supplierid) {
			$result = DB::select()->from('purch_data')->where('stockid=', $stockid)->and_where('supplier_id=', $supplierid)->fetch()
			 ->asClassLate('Item_Price', array(self::PURCHASE))->one();
			return $result;
		}

		public function __construct($type) {
			$this->_type = $type;
		}

		public function save() {
			DB::update('prices')->where('stockid=', $this->stockid)->and_where('id=', $this->id)->exec($this);
		}

		static public function add($stock_id, $sales_type_id, $curr_abrev, $price, $item_code_id = null) {
			if ($item_code_id == null) {
				$item_code_id = Item_Code::get_id($stock_id);

			}
			$sql = "INSERT INTO prices (item_code_id, stock_id, sales_type_id, curr_abrev, price)
			VALUES (" . DB::escape($item_code_id) . ", " . DB::escape($stock_id) . ", " . DB::escape($sales_type_id) . ", " . DB::escape($curr_abrev) . ", " . DB::escape($price) . ")";
			DB::query($sql, "an item price could not be added");
		}

		static public function update($price_id, $sales_type_id, $curr_abrev, $price) {
			$sql = "UPDATE prices SET sales_type_id=" . DB::escape($sales_type_id) . ",
			curr_abrev=" . DB::escape($curr_abrev) . ",
			price=" . DB::escape($price) . " WHERE id=" . DB::escape($price_id);
			DB::query($sql, "an item price could not be updated");
		}

		static public function delete($price_id) {
			$sql = "DELETE FROM prices WHERE id= " . DB::escape($price_id);
			DB::query($sql, "an item price could not be deleted");
		}

		static public function get_all($stock_id) {
			$sql = "SELECT sales_types.sales_type, prices.*
			FROM prices, sales_types
			WHERE prices.sales_type_id = sales_types.id
			AND stock_id=" . DB::escape($stock_id) . " ORDER BY curr_abrev, sales_type_id";
			return DB::query($sql, "item prices could not be retreived");
		}

		static public function get($price_id) {
			$sql = "SELECT * FROM prices WHERE id=" . DB::escape($price_id);
			$result = DB::query($sql, "price could not be retreived");
			return DB::fetch($result);
		}

		static public function get_standard_cost($stock_id) {
			$sql = "SELECT IF(s.mb_flag='" . STOCK_SERVICE . "', 0, material_cost + labour_cost + overhead_cost) AS std_cost
				FROM stock_master s WHERE stock_id=" . DB::escape($stock_id);
			$result = DB::query($sql, "The standard cost cannot be retrieved");
			$myrow = DB::fetch_row($result);
			return $myrow[0];
		}

		static public function get_percent($stock_id, $add_pct) {
			$avg = static::get_standard_cost($stock_id);
			if ($avg == 0) {
				return 0;
			}
			return Num::round($avg * (1 + $add_pct / 100), User::price_dec());
		}

		static public function get_calculated_price($stock_id, $currency, $sales_type_id, $factor = null, $date = null) {
			if ($date == null) {
				$date = Dates::new_doc_date();
			}
			if ($factor === null) {
				$myrow = Sales_Type::get($sales_type_id);
				$factor = $myrow['factor'];
			}
			$add_pct = DB_Company::get_pref('add_pct');
			$base_id = DB_Company::get_base_sales_type();
			$home_curr = Bank_Currency::for_company();
			//	AND (sales_type_id = $sales_type_id	OR sales_type_id = $base_id)
			$sql = "SELECT price, curr_abrev, sales_type_id
			FROM prices
			WHERE stock_id = " . DB::escape($stock_id) . "
				AND (curr_abrev = " . DB::escape($currency) . " OR curr_abrev = " . DB::escape($home_curr) . ")";
			$result = DB::query($sql, "There was a problem retrieving the pricing information for the part $stock_id for customer");
			$num_rows = DB::num_rows($result);
			$rate = Num::round(Bank_Currency::exchange_rate_from_home($currency, $date), User::exrate_dec());
			$round_to = DB_Company::get_pref('round_to');
			$prices = array();
			while ($myrow = DB::fetch($result)) {
				$prices[$myrow['sales_type_id']][$myrow['curr_abrev']] = $myrow['price'];
			}
			$price = false;
			if (isset($prices[$sales_type_id][$currency])) {
				$price = $prices[$sales_type_id][$currency];
			} elseif (isset($prices[$base_id][$currency])) {
				$price = $prices[$base_id][$currency] * $factor;
			} elseif (isset($prices[$sales_type_id][$home_curr])) {
				$price = $prices[$sales_type_id][$home_curr] / $rate;
			} elseif (isset($prices[$base_id][$home_curr])) {
				$price = $prices[$base_id][$home_curr] * $factor / $rate;
			} /*
								 if (isset($prices[$sales_type_id][$home_curr]))
								 {
									 $price = $prices[$sales_type_id][$home_curr] / $rate;
								 }
								 elseif (isset($prices[$base_id][$currency]))
								 {
									 $price = $prices[$base_id][$currency] * $factor;
								 }
								 elseif (isset($prices[$base_id][$home_curr]))
								 {
									 $price = $prices[$base_id][$home_curr] * $factor / $rate;
								 }
							 */ elseif ($num_rows == 0 && $add_pct != -1) {
				$price = static::get_percent($stock_id, $add_pct);
				if ($currency != $home_curr) {
					$price /= $rate;
				}
				if ($factor != 0) {
					$price *= $factor;
				}
			}
			if ($price === false) {
				return 0;
			} elseif ($round_to != 1) {
				return Num::round_to_nearest($price, $round_to);
			} else {
				return Num::round($price, User::price_dec());
			}
		}

		/***
		 *
		 *	Get price for given item or kit.
		 * When $std==true price is calculated as a sum of all included stock items,
		 *	otherwise all prices set for kits and items are accepted.
		 *
		 * @param			$item_code
		 * @param			$currency
		 * @param			$sales_type_id
		 * @param null $factor
		 * @param null $date
		 * @param bool $std
		 *
		 * @return float|int
		 */
		static public function get_kit($item_code, $currency, $sales_type_id, $factor = null, $date = null, $std = false) {
			$kit_price = 0.00;
			if (!$std) {
				$kit_price = static::get_calculated_price($item_code, $currency, $sales_type_id, $factor, $date);
				if ($kit_price !== false) {
					return $kit_price;
				}
			}
			// no price for kit found, get total value of all items
			$kit = Item_Code::get_kit($item_code);
			while ($item = DB::fetch($kit)) {
				if ($item['item_code'] != $item['stock_id']) {
					// foreign/kit code
					$kit_price += $item['quantity'] * static::get_kit($item['stock_id'], $currency, $sales_type_id, $factor, $date, $std);
				} else {
					// stock item
					$kit_price += $item['quantity'] * static::get_calculated_price($item['stock_id'], $currency, $sales_type_id, $factor,
						$date);
				}
			}
			return $kit_price;
		}

		static public function get_purchase($supplier_id, $stock_id) {
			$sql = "SELECT price, conversion_factor FROM purch_data
				WHERE supplier_id = " . DB::escape($supplier_id) . "
				AND stock_id = " . DB::escape($stock_id);
			$result = DB::query($sql, "The supplier pricing details for " . $stock_id . " could not be retrieved");
			if (DB::num_rows($result) == 1) {
				$myrow = DB::fetch($result);
				return $myrow["price"] / $myrow['conversion_factor'];
			} else {
				return 0;
			}
		}

		static public function update_cost($stock_id, $material_cost, $labour_cost, $overhead_cost, $last_cost) {
			$mb_flag = WO::get_mb_flag($stock_id);
			if (Input::post('mb_flag') == STOCK_SERVICE) {
				Errors::db_error("Cannot do cost update for Service item : $stock_id", "");
			}
			$update_no = -1;
			DB::begin();
			$sql = "UPDATE stock_master SET material_cost=" . DB::escape($material_cost) . ",
						labour_cost=" . DB::escape($labour_cost) . ",
						overhead_cost=" . DB::escape($overhead_cost) . ",
						last_cost=" . DB::escape($last_cost) . "
						WHERE stock_id=" . DB::escape($stock_id);
			DB::query($sql, "The cost details for the inventory item could not be updated");
			$qoh = Item::get_qoh_on_date($_POST['stock_id']);
			$date_ = Dates::Today();
			if ($qoh > 0) {
				$update_no = SysTypes::get_next_trans_no(ST_COSTUPDATE);
				if (!Dates::is_date_in_fiscalyear($date_)) {
					$date_ = Dates::end_fiscalyear();
				}
				$stock_gl_code = Item::get_gl_code($stock_id);
				$new_cost = $material_cost + $labour_cost + $overhead_cost;
				$value_of_change = $qoh * ($new_cost - $last_cost);
				$memo_ = "Cost was " . $last_cost . " changed to " . $new_cost . " x quantity on hand of $qoh";
				GL_Trans::add_std_cost(ST_COSTUPDATE, $update_no, $date_, $stock_gl_code["adjustment_account"],
					$stock_gl_code["dimension_id"], $stock_gl_code["dimension2_id"], $memo_, (-$value_of_change));
				GL_Trans::add_std_cost(ST_COSTUPDATE, $update_no, $date_, $stock_gl_code["inventory_account"], 0, 0, $memo_,
					$value_of_change);
			}
			DB_AuditTrail::add(ST_COSTUPDATE, $update_no, $date_);
			DB::commit();
			return $update_no;
		}

		static public function to_words($amount, $document = 0) {
			global $Hooks;
			// use local Item_Price::to_words() if the hook is defined
			if (method_exists($Hooks, 'price_in_words')) {
				return $Hooks->price_in_words($amount, $document);
			}
			// Only usefor Remittance and Receipts as default
			if (!($document == ST_SUPPAYMENT || $document == ST_CUSTPAYMENT || $document == ST_CUSTREFUND || $document == ST_CHEQUE)) {
				return "";
			}
			if ($amount < 0 || $amount > 999999999999) {
				return "";
			}
			$dec = User::price_dec();
			if ($dec > 0) {
				$divisor = pow(10, $dec);
				$frac = Num::round($amount - floor($amount), $dec) * $divisor;
				$frac = sprintf("%0{$dec}d", $frac);
				$and = _("and");
				$frac = " $and $frac/$divisor";
			}
			else {
				$frac = "";
			}
			return Num::to_words(intval($amount)) . $frac;
		}
	}
