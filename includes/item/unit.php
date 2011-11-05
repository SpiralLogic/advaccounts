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
		Copyright (C) FrontAccounting, LLC.
		Released under the terms of the GNU General Public License, GPL,
		as published by the Free Software Foundation, either version 3
		of the License, or (at your option) any later version.
		This program is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
		See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
		 ***********************************************************************/
		function write($selected, $abbr, $description, $decimals)
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
			DBOld::query($sql, "an item unit could not be updated");
		}

		function delete($unit)
		{
			$sql = "DELETE FROM item_units WHERE abbr=" . DB::escape($unit);
			DBOld::query($sql, "an unit of measure could not be deleted");
		}

		function get($unit)
		{
			$sql = "SELECT * FROM item_units WHERE abbr=" . DB::escape($unit);
			$result = DBOld::query($sql, "an unit of measure could not be retrieved");
			return DBOld::fetch($result);
		}

		function desc($unit)
		{
			$sql = "SELECT description FROM item_units WHERE abbr=" . DB::escape($unit);
			$result = DBOld::query($sql, "could not unit description");
			$row = DBOld::fetch_row($result);
			return $row[0];
		}

		function used($unit)
		{
			$sql = "SELECT COUNT(*) FROM stock_master WHERE units=" . DB::escape($unit);
			$result = DBOld::query($sql, "could not query stock master");
			$myrow = DBOld::fetch_row($result);
			return ($myrow[0] > 0);
		}

		function get_all($all = false)
		{
			$sql = "SELECT * FROM item_units";
			if (!$all) {
				$sql .= " WHERE !inactive";
			}
			$sql .= " ORDER BY name";
			return DBOld::query($sql, "could not get stock categories");
		}

		// 2008-06-15. Added Joe Hunt to get a measure of unit by given stock_id
		function get_decimal($stock_id)
		{
			$sql
			 = "SELECT decimals FROM item_units,	stock_master
		WHERE abbr=units AND stock_id=" . DB::escape($stock_id) . " LIMIT 1";
			$result = DBOld::query($sql, "could not get unit decimals");
			$row = DBOld::fetch_row($result);
			return $row[0];
		}
	}
