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
function get_demand_qty($stock_id, $location)
{
	$sql = "SELECT SUM(sales_order_details.quantity - "
		   .  "sales_order_details.qty_sent) AS QtyDemand
			FROM sales_order_details,
					sales_orders
				WHERE sales_order_details.order_no="
		   .  "sales_orders.order_no AND sales_orders.trans_type=" . ST_SALESORDER . " AND
				sales_orders.trans_type=sales_order_details.trans_type AND ";
	if ($location != "")
		$sql .=  "sales_orders.from_stk_loc =" . db_escape($location) . " AND ";
	$sql .=  "sales_order_details.stk_code = " . db_escape($stock_id);

	$result = db_query($sql, "No transactions were returned");
	$row = db_fetch($result);
	if ($row === false)
		return 0;
	return $row['QtyDemand'];
}

$bom_list = array();
$qoh_stock = NULL;

function load_stock_levels($location)
{
	global $qoh_stock;
	$date = date2sql(Today());

	$sql = "SELECT stock_id, SUM(qty) FROM stock_moves WHERE tran_date <= '$date'";
	if ($location != '') $sql .= " AND loc_code = " . db_escape($location);
	$sql .= " GROUP BY stock_id";
	$result = db_query($sql, "QOH calulcation failed");
	while ($row = db_fetch($result)) {
		$qoh_stock[$row[0]] = $row[1];
	}
}

// recursion fixed by Tom Moulton. Max 10 recursion levels.
function stock_demand_manufacture($stock_id, $qty, $demand_id, $location, $level = 0)
{
	global $bom_list, $qoh_stock;
	$demand = 0.0;
	if ($level > 10) {
		display_warning("BOM Too many Manufacturing levels deep $level");
		return $demand;
	}
	// Load all stock levels (stock moves) into $qoh_stock
	if ($qoh_stock == NULL) {
		$qoh_stock = array();
		load_stock_levels($location);
	}
	if (empty($qoh_stock[$stock_id])) $stock_qty = 0;
	else $stock_qty = $qoh_stock[$stock_id];
	if ($qty <= $stock_qty) return $demand;
	$bom = @$bom_list[$stock_id];
	if ($bom == NULL) {
		$sql = "SELECT parent, component, quantity FROM "
			   .  "bom WHERE parent = " . db_escape($stock_id);
		if ($location != "") $sql .= " AND loc_code = " . db_escape($location);
		$result = db_query($sql, "Could not search bom");
		$bom = array();
		// Even if we get no results, remember that fact 
		$bom[] = array($stock_id, '', 0);
		while ($row = db_fetch_row($result)) {
			$bom[] = array($row[0], $row[1], $row[2]);
		}
		db_free_result($result);
		$bom_list[$stock_id] = $bom;
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
		$demand += stock_demand_manufacture($row[1], $q, $demand_id, $location, $level + 1);
	}
	return $demand;
}

// recursion fixed by Tom Moulton
function get_demand_asm_qty($stock_id, $location)
{
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
		$sql .=  "sales_orders.from_stk_loc =" . db_escape($location) . " AND ";
	$sql .=  "sales_order_details.quantity-sales_order_details.qty_sent > 0 AND
				   stock_master.stock_id=sales_order_details.stk_code AND
				   (stock_master.mb_flag='M' OR stock_master.mb_flag='A')
				   GROUP BY sales_order_details.stk_code";
	$result = db_query($sql, "No transactions were returned");
	while ($row = db_fetch_row($result)) {
		$demand_qty += stock_demand_manufacture($row[0], $row[1], $stock_id, $location);
	}
	return $demand_qty;
}

function get_on_porder_qty($stock_id, $location)
{
	$sql = "SELECT SUM(purch_order_details.quantity_ordered - "
		   .  "purch_order_details.quantity_received) AS qoo
		FROM purch_order_details INNER JOIN "
		   .  "purch_orders ON purch_order_details.order_no=purch_orders.order_no
		WHERE purch_order_details.item_code=" . db_escape($stock_id) . " ";
	if ($location != "")
		$sql .= "AND purch_orders.into_stock_location=" . db_escape($location) . " ";
	$sql .= "AND purch_order_details.item_code=" . db_escape($stock_id);
	$qoo_result = db_query($sql, "could not receive quantity on order for item");

	if (db_num_rows($qoo_result) == 1) {
		$qoo_row = db_fetch_row($qoo_result);
		$qoo = $qoo_row[0];
	}
	else
	{
		$qoo = 0;
	}
	return $qoo;
}

function get_on_worder_qty($stock_id, $location)
{
	$sql = "SELECT SUM((workorders.units_reqd-workorders.units_issued) *
		(wo_requirements.units_req-wo_requirements.units_issued)) AS qoo
		FROM wo_requirements INNER JOIN workorders
			ON wo_requirements.workorder_id=workorders.id
		WHERE wo_requirements.stock_id=" . db_escape($stock_id) . " ";
	if ($location != "")
		$sql .= "AND wo_requirements.loc_code=" . db_escape($location) . " ";
	$sql .= "AND workorders.released=1";
	$qoo_result = db_query($sql, "could not receive quantity on order for item");
	if (db_num_rows($qoo_result) == 1) {
		$qoo_row = db_fetch_row($qoo_result);
		$qoo = $qoo_row[0];
	}
	else
		$qoo = 0.0;
	$flag = get_mb_flag($stock_id);
	if ($flag == 'A' || $flag == 'M') {
		$sql = "SELECT SUM((workorders.units_reqd-workorders.units_issued)) AS qoo
			FROM workorders
			WHERE workorders.stock_id=" . db_escape($stock_id) . " ";
		if ($location != "")
			$sql .= "AND workorders.loc_code=" . db_escape($location) . " ";
		$sql .= "AND workorders.released=1";
		$qoo_result = db_query($sql, "could not receive quantity on order for item");
		if (db_num_rows($qoo_result) == 1) {
			$qoo_row = db_fetch_row($qoo_result);
			$qoo += $qoo_row[0];
		}
	}
	return $qoo;
}

function get_mb_flag($stock_id)
{
	$sql = "SELECT mb_flag FROM stock_master WHERE stock_id = "
		   . db_escape($stock_id);
	$result = db_query($sql, "retreive mb_flag from item");

	if (db_num_rows($result) == 0)
		return -1;

	$myrow = db_fetch_row($result);
	return $myrow[0];
}

//--------------------------------------------------------------------------------------

function get_bom($item)
{
	$sql = "SELECT bom.*, locations.location_name, workcentres.name AS WorkCentreDescription,
    	stock_master.description, stock_master.mb_flag AS ResourceType,
    	stock_master.material_cost+ stock_master.labour_cost+stock_master.overhead_cost AS standard_cost, units,
    	bom.quantity * (stock_master.material_cost+ stock_master.labour_cost+ stock_master.overhead_cost) AS ComponentCost
    	FROM (workcentres, locations, bom) INNER JOIN stock_master ON bom.component = stock_master.stock_id
    	WHERE bom.parent = " . db_escape($item) . "
		AND workcentres.id=bom.workcentre_added
		AND bom.loc_code = locations.loc_code ORDER BY bom.id";

	return db_query($sql, "The bill of material could not be retrieved");
}

//--------------------------------------------------------------------------------------

function has_bom($item)
{
	$result = get_bom($item);

	return (db_num_rows($result) != 0);
}

//--------------------------------------------------------------------------------------

?>
