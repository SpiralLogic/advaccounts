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
	//----------------------------------------------------------------------------------------
	class Manufacturing {


		public static $bom_list = array();
		public static $qoh_stock = NULL;

		public static function load_stock_levels($location) {

			$date = Dates::date2sql(Dates::Today());

			$sql = "SELECT stock_id, SUM(qty) FROM stock_moves WHERE tran_date <= '$date'";
			if ($location != '') $sql .= " AND loc_code = " . DB::escape($location);
			$sql .= " GROUP BY stock_id";
			$result = DB::query($sql, "QOH calulcation failed");
			while ($row = DB::fetch($result)) {
				static::$qoh_stock[$row[0]] = $row[1];
			}
		}

		public static function get_demand_qty($stock_id, $location) {

			$sql = "SELECT SUM(sales_order_details.quantity - "
			 . "sales_order_details.qty_sent) AS QtyDemand
					FROM sales_order_details,
							sales_orders
						WHERE sales_order_details.order_no="
			 . "sales_orders.order_no AND sales_orders.trans_type=" . ST_SALESORDER . " AND
						sales_orders.trans_type=sales_order_details.trans_type AND ";
			if ($location != "")
				$sql .= "sales_orders.from_stk_loc =" . DB::escape($location) . " AND ";
			$sql .= "sales_order_details.stk_code = " . DB::escape($stock_id);

			$result = DB::query($sql, "No transactions were returned");
			$row = DB::fetch($result);
			if ($row === false)
				return 0;
			return $row['QtyDemand'];
		}

		// recursion fixed by Tom Moulton. Max 10 recursion levels.
		public static function stock_demand_manufacture($stock_id, $qty, $demand_id, $location, $level = 0) {

			$demand = 0.0;
			if ($level > 10) {
				Errors::warning("BOM Too many Manufacturing levels deep $level");
				return $demand;
			}
			// Load all stock levels (stock moves) into static::$qoh_stock
			if (static::$qoh_stock == NULL) {
				static::$qoh_stock = array();
				Manufacturing::load_stock_levels($location);
			}
			if (empty(static::$qoh_stock[$stock_id])) $stock_qty = 0;
			else $stock_qty = static::$qoh_stock[$stock_id];
			if ($qty <= $stock_qty) return $demand;
			$bom = @static::$bom_list[$stock_id];
			if ($bom == NULL) {
				$sql = "SELECT parent, component, quantity FROM "
				 . "bom WHERE parent = " . DB::escape($stock_id);
				if ($location != "") $sql .= " AND loc_code = " . DB::escape($location);
				$result = DB::query($sql, "Could not search bom");
				$bom = array();
				// Even if we get no results, remember that fact
				$bom[] = array($stock_id, '', 0);
				while ($row = DB::fetch_row($result)) {
					$bom[] = array($row[0], $row[1], $row[2]);
				}
				DB::free_result($result);
				static::$bom_list[$stock_id] = $bom;
			}
			$len = count($bom);
			$i = 0;
			while ($i < $len) {
				$row = $bom[$i];
				$i++;
				// Ignore the dummy entry
				if ($row[1] == '') continue;
				$q = $qty * $row[2];
				if ($row[1] == $demand_id) $demand += $q;
				$demand += Manufacturing::stock_demand_manufacture($row[1], $q, $demand_id, $location, $level + 1);
			}
			return $demand;
		}

		// recursion fixed by Tom Moulton
		public static function get_demand_asm_qty($stock_id, $location) {
			$demand_qty = 0.0;
			$sql = "SELECT sales_order_details.stk_code, SUM(sales_order_details.quantity-sales_order_details.qty_sent)
				   AS Demmand
				   FROM sales_order_details,
						sales_orders,
						stock_master
				   WHERE sales_orders.order_no = sales_order_details.order_no AND
				   	sales_orders.trans_type=" . ST_SALESORDER . " AND
					sales_orders.trans_type=sales_order_details.trans_type AND ";
			if ($location != "")
				$sql .= "sales_orders.from_stk_loc =" . DB::escape($location) . " AND ";
			$sql .= "sales_order_details.quantity-sales_order_details.qty_sent > 0 AND
				   stock_master.stock_id=sales_order_details.stk_code AND
				   (stock_master.mb_flag='" . STOCK_MANUFACTURE . "' OR stock_master.mb_flag='A')
				   GROUP BY sales_order_details.stk_code";
			$result = DB::query($sql, "No transactions were returned");
			while ($row = DB::fetch_row($result)) {
				$demand_qty += Manufacturing::stock_demand_manufacture($row[0], $row[1], $stock_id, $location);
			}
			return $demand_qty;
		}

		public static function get_on_porder_qty($stock_id, $location) {
			$sql = "SELECT SUM(purch_order_details.quantity_ordered - "
			 . "purch_order_details.quantity_received) AS qoo
		FROM purch_order_details INNER JOIN "
			 . "purch_orders ON purch_order_details.order_no=purch_orders.order_no
		WHERE purch_order_details.item_code=" . DB::escape($stock_id) . " ";
			if ($location != "")
				$sql .= "AND purch_orders.into_stock_location=" . DB::escape($location) . " ";
			$sql .= "AND purch_order_details.item_code=" . DB::escape($stock_id);
			$qoo_result = DB::query($sql, "could not receive quantity on order for item");

			if (DB::num_rows($qoo_result) == 1) {
				$qoo_row = DB::fetch_row($qoo_result);
				$qoo = $qoo_row[0];
} else {
				$qoo = 0;
			}
			return $qoo;
		}

		public static function get_on_worder_qty($stock_id, $location) {
			$sql = "SELECT SUM((workorders.units_reqd-workorders.units_issued) *
		(wo_requirements.units_req-wo_requirements.units_issued)) AS qoo
		FROM wo_requirements INNER JOIN workorders
			ON wo_requirements.workorder_id=workorders.id
		WHERE wo_requirements.stock_id=" . DB::escape($stock_id) . " ";
			if ($location != "")
				$sql .= "AND wo_requirements.loc_code=" . DB::escape($location) . " ";
			$sql .= "AND workorders.released=1";
			$qoo_result = DB::query($sql, "could not receive quantity on order for item");
			if (DB::num_rows($qoo_result) == 1) {
				$qoo_row = DB::fetch_row($qoo_result);
				$qoo = $qoo_row[0];
			}
			else
				$qoo = 0.0;
			$flag = Manufacturing::get_mb_flag($stock_id);
			if ($flag == 'A' || $flag == STOCK_MANUFACTURE) {
				$sql = "SELECT SUM((workorders.units_reqd-workorders.units_issued)) AS qoo
			FROM workorders
			WHERE workorders.stock_id=" . DB::escape($stock_id) . " ";
				if ($location != "")
					$sql .= "AND workorders.loc_code=" . DB::escape($location) . " ";
				$sql .= "AND workorders.released=1";
				$qoo_result = DB::query($sql, "could not receive quantity on order for item");
				if (DB::num_rows($qoo_result) == 1) {
					$qoo_row = DB::fetch_row($qoo_result);
					$qoo += $qoo_row[0];
				}
			}
			return $qoo;
		}

		public static function get_mb_flag($stock_id) {
			$sql = "SELECT mb_flag FROM stock_master WHERE stock_id = "
			 . DB::escape($stock_id);
			$result = DB::query($sql, "retreive mb_flag from item");

			if (DB::num_rows($result) == 0)
				return -1;

			$myrow = DB::fetch_row($result);
			return $myrow[0];
		}

		//--------------------------------------------------------------------------------------

		function get_bom($item) {
			$sql = "SELECT bom.*, locations.location_name, workcentres.name AS WorkCentreDescription,
    	stock_master.description, stock_master.mb_flag AS ResourceType,
    	stock_master.material_cost+ stock_master.labour_cost+stock_master.overhead_cost AS standard_cost, units,
    	bom.quantity * (stock_master.material_cost+ stock_master.labour_cost+ stock_master.overhead_cost) AS ComponentCost
    	FROM (workcentres, locations, bom) INNER JOIN stock_master ON bom.component = stock_master.stock_id
    	WHERE bom.parent = " . DB::escape($item) . "
		AND workcentres.id=bom.workcentre_added
		AND bom.loc_code = locations.loc_code ORDER BY bom.id";

			return DB::query($sql, "The bill of material could not be retrieved");
		}

		//--------------------------------------------------------------------------------------

		function has_bom($item) {
			$result = Manufacturing::get_bom($item);

			return (DB::num_rows($result) != 0);
		}

		function has_stock_holding($mb_flag) {
			return $mb_flag == STOCK_PURCHASED || $mb_flag == STOCK_MANUFACTURE;
		}
		//--------------------------------------------------------------------------------------

	}