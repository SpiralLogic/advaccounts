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
	function write_item_unit($selected, $abbr, $description, $decimals) {
		if ($selected != '')
			$sql = "UPDATE item_units SET
	 	abbr = " . DBOld::escape($abbr) . ",
	 	name = " . DBOld::escape($description) . ",
	 	decimals = " . DBOld::escape($decimals) . "
        	WHERE abbr = " . DBOld::escape($selected);
		else
			$sql = "INSERT INTO item_units
			(abbr, name, decimals) VALUES( " . DBOld::escape($abbr) . ",
	  		" . DBOld::escape($description) . ", " . DBOld::escape($decimals) . ")";

		DBOld::query($sql, "an item unit could not be updated");
	}

	function delete_item_unit($unit) {
		$sql = "DELETE FROM item_units WHERE abbr=" . DBOld::escape($unit);

		DBOld::query($sql, "an unit of measure could not be deleted");
	}

	function get_item_unit($unit) {
		$sql = "SELECT * FROM item_units WHERE abbr=" . DBOld::escape($unit);

		$result = DBOld::query($sql, "an unit of measure could not be retrieved");

		return DBOld::fetch($result);
	}

	function get_unit_descr($unit) {
		$sql = "SELECT description FROM item_units WHERE abbr=" . DBOld::escape($unit);

		$result = DBOld::query($sql, "could not unit description");

		$row = DBOld::fetch_row($result);
		return $row[0];
	}

	function item_unit_used($unit) {
		$sql = "SELECT COUNT(*) FROM stock_master WHERE units=" . DBOld::escape($unit);
		$result = DBOld::query($sql, "could not query stock master");
		$myrow = DBOld::fetch_row($result);
		return ($myrow[0] > 0);
	}

	function get_all_item_units($all = false) {
		$sql = "SELECT * FROM item_units";
		if (!$all) $sql .= " WHERE !inactive";
		$sql .= " ORDER BY name";
		return DBOld::query($sql, "could not get stock categories");
	}

	// 2008-06-15. Added Joe Hunt to get a measure of unit by given stock_id
	function get_unit_dec($stock_id) {
		$sql = "SELECT decimals FROM item_units,	stock_master
		WHERE abbr=units AND stock_id=" . DBOld::escape($stock_id) . " LIMIT 1";
		$result = DBOld::query($sql, "could not get unit decimals");

		$row = DBOld::fetch_row($result);
		return $row[0];
	}

?>