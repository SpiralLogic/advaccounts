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
	function update_item(
		$stock_id, $description, $long_description, $category_id,
		$tax_type_id, $units = '', $mb_flag = '', $sales_account, $inventory_account,
		$cogs_account, $adjustment_account, $assembly_account, $dimension_id,
		$dimension2_id, $no_sale
	)
	{
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

	function add_item(
		$stock_id, $description, $long_description, $category_id,
		$tax_type_id, $units, $mb_flag, $sales_account, $inventory_account,
		$cogs_account, $adjustment_account, $assembly_account, $dimension_id,
		$dimension2_id, $no_sale
	)
	{
		$sql
		 = "INSERT INTO stock_master (stock_id, description, long_description, category_id,
		tax_type_id, units, mb_flag, sales_account, inventory_account, cogs_account,
		adjustment_account, assembly_account, dimension_id, dimension2_id, no_sale)
		VALUES (" . DB::escape($stock_id) . ", " . DB::escape($description) . ", " . DB::escape($long_description) . ",
		" . DB::escape($category_id) . ", " . DB::escape($tax_type_id) . ", "
		 . DB::escape($units) . ", " . DB::escape($mb_flag) . ",
		" . DB::escape($sales_account) . ", " . DB::escape($inventory_account)
		 . ", " . DB::escape($cogs_account) . "," . DB::escape($adjustment_account)
		 . ", " . DB::escape($assembly_account) . ", "
		 . DB::escape($dimension_id) . ", " . DB::escape($dimension2_id) . ","
		 . DB::escape($no_sale) . ")";
		DB::query($sql, "The item could not be added");
		$sql
		 = "INSERT INTO loc_stock (loc_code, stock_id)
		SELECT locations.loc_code, " . DB::escape($stock_id)
		 . " FROM locations";
		DB::query($sql, "The item locstock could not be added");
		Item_Code::add($stock_id, $stock_id, $description, $category_id, 1, 0);
	}

	function delete_item($stock_id)
	{
		$sql = "DELETE FROM stock_master WHERE stock_id=" . DB::escape($stock_id);
		DB::query($sql, "could not delete stock item");
		/*and cascade deletes in loc_stock */
		$sql = "DELETE FROM loc_stock WHERE stock_id=" . DB::escape($stock_id);
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

	function get_item($stock_id)
	{
		$sql
		 = "SELECT stock_master.*,item_tax_types.name AS tax_type_name
		FROM stock_master,item_tax_types
		WHERE item_tax_types.id=stock_master.tax_type_id
		AND stock_id=" . DB::escape($stock_id);
		$result = DB::query($sql, "an item could not be retreived");
		return DB::fetch($result);
	}

	function get_items()
	{
		$sql = "SELECT * FROM stock_master";
		return DB::query($sql, "items could not be retreived");
	}

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
	function get_qoh_on_date($stock_id, $location = null, $date_ = null, $exclude = 0)
	{
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
			WHERE stock_id=" . DB::escape($stock_id)
			 . " AND type=" . DB::escape($exclude)
			 . " AND tran_date = '$date'";
			$result = DB::query($sql, "QOH calulcation failed");
			$myrow2 = DB::fetch_row($result);
			if ($myrow2 !== false) {
				$myrow[0] -= $myrow2[0];
			}
		}
		return $myrow[0];
	}

	//--------------------------------------------------------------------------------------
	function get_item_edit_info($stock_id)
	{
		$sql
		 = "SELECT material_cost + labour_cost + overhead_cost AS standard_cost, units, decimals
		FROM stock_master,item_units
		WHERE stock_id=" . DB::escape($stock_id)
		 . " AND stock_master.units=item_units.abbr";
		$query = DB::query($sql, "The standard cost cannot be retrieved");
		$result = array(
			'standard_cost' => 0,
			'units' => 'ea',
			'decimals' => User::price_dec()
		);
		if (DB::num_rows($query) == 0) {
			$result = DB::fetch($query);
		}
		return $result;
	}

	//--------------------------------------------------------------------------------------
	function is_inventory_item($stock_id)
	{
		$sql
		 = "SELECT stock_id FROM stock_master
		WHERE stock_id=" . DB::escape($stock_id) . " AND mb_flag <> 'D'";
		$result = DB::query($sql, "Cannot query is inventory item or not");
		return DB::num_rows($result) > 0;
	}

	//-------------------------------------------------------------------
	function last_negative_stock_begin_date($stock_id, $to)
	{
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

	//-------------------------------------------------------------------
	function get_deliveries_between($stock_id, $from, $to)
	{
		$from = Dates::date2sql($from);
		$to = Dates::date2sql($to);
		$sql
		 = "SELECT SUM(-qty), SUM(-qty*standard_cost) FROM stock_moves
		WHERE type=" . ST_CUSTDELIVERY . " AND stock_id=" . DB::escape($stock_id) . " AND
			tran_date>='$from' AND tran_date<='$to' GROUP BY stock_id";
		$result = DB::query($sql, "The deliveries could not be updated");
		return DB::fetch_row($result);
	}

	//-------------------------------------------------------------------
	function adjust_deliveries($stock_id, $material_cost, $to)
	{
		if (!is_inventory_item($stock_id)) {
			return;
		}
		$from = last_negative_stock_begin_date($stock_id, $to);
		if ($from == false || $from == "") {
			return;
		}
		$from = Dates::sql2date($from);
		$row = get_deliveries_between($stock_id, $from, $to);
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
			$stock_gl_code = get_stock_gl_code($stock_id);
			$memo_ = _("Cost was ") . $old_cost . _(" changed to ") . $new_cost . _(" for item ") . "'$stock_id'";
			add_gl_trans_std_cost(
				ST_COSTUPDATE, $update_no, $to, $stock_gl_code["cogs_account"],
				$stock_gl_code["dimension_id"], $stock_gl_code["dimension2_id"], $memo_, $diff
			);
			add_gl_trans_std_cost(
				ST_COSTUPDATE, $update_no, $to, $stock_gl_code["inventory_account"], 0, 0, $memo_,
				-$diff
			);
			DB_AuditTrail::add(ST_COSTUPDATE, $update_no, $to);
		}
	}

	function get_stock_gl_code($stock_id)
	{
		/*Gets the GL Codes relevant to the item account  */
		$sql
		 = "SELECT inventory_account, cogs_account,
		adjustment_account, sales_account, assembly_account, dimension_id, dimension2_id FROM
		stock_master WHERE stock_id = " . DB::escape($stock_id);
		$get = DB::query($sql, "retreive stock gl code");
		return DB::fetch($get);
	}

	//--------------------------------------------------------------------------------------
	// $date_ - display / non-sql date
	// $std_cost - in HOME currency
	// $show_or_hide - wil this move be visible in reports, etc
	// $price - in $person_id's currency
	function item_img_name($stock_id)
	{
		return strtr($stock_id, "><\\/:|*?", '________');
	}

	function get_stockid($stock_id)
	{
		$result = current(DB::select('id')->from('stock_master')->where('stock_id LIKE ', $stock_id)->fetch()->all());
		return $result['id'];
	}

?>