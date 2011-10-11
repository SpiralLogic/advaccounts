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
	function add_item_category($description, $tax_type_id, $sales_account,
														 $cogs_account, $inventory_account, $adjustment_account, $assembly_account,
														 $units, $mb_flag, $dim1, $dim2, $no_sale) {
		$sql = "INSERT INTO stock_category (description, dflt_tax_type,
			dflt_units, dflt_mb_flag, dflt_sales_act, dflt_cogs_act, 
			dflt_inventory_act, dflt_adjustment_act, dflt_assembly_act, 
			dflt_dim1, dflt_dim2, dflt_no_sale)
		VALUES ("
		 . DBOld::escape($description) . ","
		 . DBOld::escape($tax_type_id) . ","
		 . DBOld::escape($units) . ","
		 . DBOld::escape($mb_flag) . ","
		 . DBOld::escape($sales_account) . ","
		 . DBOld::escape($cogs_account) . ","
		 . DBOld::escape($inventory_account) . ","
		 . DBOld::escape($adjustment_account) . ","
		 . DBOld::escape($assembly_account) . ","
		 . DBOld::escape($dim1) . ","
		 . DBOld::escape($dim2) . ","
		 . DBOld::escape($no_sale) . ")";

		DBOld::query($sql, "an item category could not be added");
	}

	function update_item_category($id, $description, $tax_type_id,
																$sales_account, $cogs_account, $inventory_account, $adjustment_account,
																$assembly_account, $units, $mb_flag, $dim1, $dim2, $no_sale) {
		$sql = "UPDATE stock_category SET "
		 . "description = " . DBOld::escape($description) . ","
		 . "dflt_tax_type = " . DBOld::escape($tax_type_id) . ","
		 . "dflt_units = " . DBOld::escape($units) . ","
		 . "dflt_mb_flag = " . DBOld::escape($mb_flag) . ","
		 . "dflt_sales_act = " . DBOld::escape($sales_account) . ","
		 . "dflt_cogs_act = " . DBOld::escape($cogs_account) . ","
		 . "dflt_inventory_act = " . DBOld::escape($inventory_account) . ","
		 . "dflt_adjustment_act = " . DBOld::escape($adjustment_account) . ","
		 . "dflt_assembly_act = " . DBOld::escape($assembly_account) . ","
		 . "dflt_dim1 = " . DBOld::escape($dim1) . ","
		 . "dflt_dim2 = " . DBOld::escape($dim2) . ","
		 . "dflt_no_sale = " . DBOld::escape($no_sale)
		 . "WHERE category_id = " . DBOld::escape($id);

		DBOld::query($sql, "an item category could not be updated");
	}

	function delete_item_category($id) {
		$sql = "DELETE FROM stock_category WHERE category_id=" . DBOld::escape($id);

		DBOld::query($sql, "an item category could not be deleted");
	}

	function get_item_category($id) {
		$sql = "SELECT * FROM stock_category WHERE category_id=" . DBOld::escape($id);

		$result = DBOld::query($sql, "an item category could not be retrieved");

		return DBOld::fetch($result);
	}

	function get_category_name($id) {
		$sql = "SELECT description FROM stock_category WHERE category_id=" . DBOld::escape($id);

		$result = DBOld::query($sql, "could not get sales type");

		$row = DBOld::fetch_row($result);
		return $row[0];
	}

?>