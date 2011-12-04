<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Complex
 * Date: 6/11/11
 * Time: 2:42 AM
 * To change this template use File | Settings | File Templates.
 */
	class Item_Unit
	{
		/**********************************************************************
		Copyright (C) Advanced Group PTY LTD
		Released under the terms of the GNU General Public License, GPL,
		as published by the Free Software Foundation, either version 3
		of the License, or (at your option) any later version.
		This program is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
		See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
		 ***********************************************************************/
public static		function write($selected, $abbr, $description, $decimals)
		{
			if ($selected != '') {
				$sql
				 = "UPDATE item_units SET
	 	abbr = " . DB::escape($abbr) . ",
	 	name = " . DB::escape($description) . ",
	 	decimals = " . DB::escape($decimals) . "
        	WHERE abbr = " . DB::escape($selected);
			}
			else
			{
				$sql
				 = "INSERT INTO item_units
			(abbr, name, decimals) VALUES( " . DB::escape($abbr) . ",
	  		" . DB::escape($description) . ", " . DB::escape($decimals) . ")";
			}
			DB::query($sql, "an item unit could not be updated");
		}

		public static	function delete($unit)
		{
			$sql = "DELETE FROM item_units WHERE abbr=" . DB::escape($unit);
			DB::query($sql, "an unit of measure could not be deleted");
		}

		public static		function get($unit)
		{
			$sql = "SELECT * FROM item_units WHERE abbr=" . DB::escape($unit);
			$result = DB::query($sql, "an unit of measure could not be retrieved");
			return DB::fetch($result);
		}

		public static	function desc($unit)
		{
			$sql = "SELECT description FROM item_units WHERE abbr=" . DB::escape($unit);
			$result = DB::query($sql, "could not unit description");
			$row = DB::fetch_row($result);
			return $row[0];
		}

		public static		function used($unit)
		{
			$sql = "SELECT COUNT(*) FROM stock_master WHERE units=" . DB::escape($unit);
			$result = DB::query($sql, "could not query stock master");
			$myrow = DB::fetch_row($result);
			return ($myrow[0] > 0);
		}

		public static		function get_all($all = false)
		{
			$sql = "SELECT * FROM item_units";
			if (!$all) {
				$sql .= " WHERE !inactive";
			}
			$sql .= " ORDER BY name";
			return DB::query($sql, "could not get stock categories");
		}

		// 2008-06-15. Added Joe Hunt to get a measure of unit by given stock_id
		public static		function get_decimal($stock_id)
		{
			$sql
			 = "SELECT decimals FROM item_units,	stock_master
		WHERE abbr=units AND stock_id=" . DB::escape($stock_id) . " LIMIT 1";
			$result = DB::query($sql, "could not get unit decimals");
			$row = DB::fetch_row($result);
			return $row[0];
		}

			// STOCK UNITS
		public static		function row($label, $name, $value = null, $enabled = true) {
				$result = Item_Unit::get_all();
				echo "<tr>";
				if ($label != null) {
					echo "<td class='label'>$label</td>\n";
				}
				echo "<td>";
				while ($unit = DB::fetch($result)) {
					$units[$unit['abbr']] = $unit['name'];
				}
				echo array_selector($name, $value, $units, array('disabled' => !$enabled));
				echo "</td></tr>\n";
			}

	}
