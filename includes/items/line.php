<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Complex
 * Date: 1/11/11
 * Time: 7:19 AM
 * To change this template use File | Settings | File Templates.
 */ 

	class Items_Line {
			var $stock_id;
			var $description;
			var $units;
			var $mb_flag;

			var $quantity;
			var $price;
			var $standard_cost;

			function __construct($stock_id, $qty, $standard_cost = null, $description = null) {
				$item_row = get_item($stock_id);

				if ($item_row == null)
					Errors::show_db_error("invalid item added to order : $stock_id", "");

				$this->mb_flag = $item_row["mb_flag"];
				$this->units = $item_row["units"];

				if ($description == null)
					$this->description = $item_row["description"];
				else
					$this->description = $description;

				if ($standard_cost == null)
					$this->standard_cost = $item_row["actual_cost"];
				else
					$this->standard_cost = $standard_cost;

				$this->stock_id = $stock_id;
				$this->quantity = $qty;
				//$this->price = $price;
				$this->price = 0;
			}

			function check_qoh($location, $date_, $reverse) {

				if (!SysPrefs::allow_negative_stock()) {
					if (Manufacturing::has_stock_holding($this->mb_flag)) {
						$quantity = $this->quantity;
						if ($reverse)
							$quantity = -$this->quantity;

						if ($quantity >= 0)
							return null;

						$qoh = get_qoh_on_date($this->stock_id, $location, $date_);
						if ($quantity + $qoh < 0) {
							return $this;
						}
					}
				}

				return null;
			}
		}

		//---------------------------------------------------------------------------------------

