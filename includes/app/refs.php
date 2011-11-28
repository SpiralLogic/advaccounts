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
	 ***********************************************************************/
	class Refs
	{
		public static function get_reference($type, $id)
			{
				$sql = "SELECT * FROM refs WHERE type=" . DB::escape($type) . " AND id=" . DB::escape($id);
				$result = DB::query($sql, "could not query reference table");
				$row = DB::fetch($result);
				return $row['reference'];
			}

		//--------------------------------------------------------------------------------------------------
		public static function add_reference($type, $id, $reference)
			{
				$sql = "INSERT INTO refs (type, id, reference)
			VALUES (" . DB::escape($type) . ", " . DB::escape($id) . ", "
				 . DB::escape(trim($reference)) . ")";
				DB::query($sql, "could not add reference entry");
			}

		//--------------------------------------------------------------------------------------------------
		public static function update_reference($type, $id, $reference)
			{
				$sql = "UPDATE refs SET reference=" . DB::escape($reference)
				 . " WHERE type=" . DB::escape($type) . " AND id=" . DB::escape($id);
				DB::query($sql, "could not update reference entry");
			}

		//--------------------------------------------------------------------------------------------------
		public static function delete_reference($type, $id)
			{
				$sql = "DELETE FROM refs WHERE type=$type AND id=" . DB::escape($id);
				return DB::query($sql, "could not delete from reference table");
			}

		//--------------------------------------------------------------------------------------------------
		public static function find_reference($type, $reference)
			{
				$sql = "SELECT id FROM refs WHERE type=" . DB::escape($type)
				 . " AND reference=" . DB::escape($reference);
				$result = DB::query($sql, "could not query reference table");
				return (DB::num_rows($result) > 0);
			}

		//--------------------------------------------------------------------------------------------------
		public static function save_next_reference($type, $reference)
			{
				$sql = "UPDATE sys_types SET next_reference=" . DB::escape(trim($reference))
				 . " WHERE type_id = " . DB::escape($type);
				DB::query($sql, "The next transaction ref for $type could not be updated");
			}

		//--------------------------------------------------------------------------------------------------
		public static function get_next_reference($type)
			{
				$sql = "SELECT next_reference FROM sys_types WHERE type_id = " . DB::escape($type);
				$result = DB::query($sql, "The last transaction ref for $type could not be retreived");
				$row = DB::fetch_row($result);
				return $row[0];
			}

		public static function save($type, $id, $reference)
			{
				static::add_reference($type, $id, $reference);
				if ($reference != 'auto') {
					static::save_last($reference, $type);
				}
			}

		public static function get($type, $id)
			{
				return static::get_reference($type, $id);
			}

		public static function delete($type, $id)
			{
				static::delete_reference($type, $id);
			}

		public static function update($type, $id, $reference)
			{
				static::update_reference($type, $id, $reference);
				if ($reference != 'auto') {
					static::save_last($reference, $type);
				}
			}

		public static function exists($type, $reference)
			{
				return (static::find_reference($type, $reference) != null);
			}

		public static function save_last($reference, $type)
			{
				$next = static::increment(Refs::get_next_reference($type));
				static::save_next_reference($type, $next);
			}

		public static function get_next($type)
			{
				return static::get_next_reference($type);
			}

		//------------------------------------
		public static function is_valid($reference)
			{
				return strlen(trim($reference)) > 0;
			}

		public static function increment($reference)
			{
				// New method done by Pete. So f.i. WA036 will increment to WA037 and so on.
				// If $reference contains at least one group of digits,
				// extract first didgits group and add 1, then put all together.
				// NB. preg_match returns 1 if the regex matches completely
				// also $result[0] holds entire string, 1 the first captured, 2 the 2nd etc.
				//
				if (preg_match('/^(\D*?)(\d+)(.*)/', $reference, $result) == 1) {
					list($all, $prefix, $number, $postfix) = $result;
					$dig_count = strlen($number); // How many digits? eg. 0003 = 4
					$fmt = '%0' . $dig_count . 'd'; // Make a format string - leading zeroes
					$nextval = sprintf($fmt, intval($number + 1)); // Add one on, and put prefix back on
					return $prefix . $nextval . $postfix;
				} else {
					return $reference;
				}
			}
		//------------------------------------
	}

	//----------------------------------------------------------------------------
	function is_new_reference(&$ref, $type)
		{
			$db_info = SysTypes::get_systype_db_info($type);
			$db_name = $db_info[0];
			$db_type = $db_info[1];
			$db_ref = $db_info[3];
			if ($db_ref != null) {
				do {
					$sql = "SELECT $db_ref FROM $db_name WHERE $db_ref='$ref'";
					if ($db_type != null) {
						$sql .= " AND $db_type=$type";
					}
					$result = DB::query($sql, "could not test for unique reference");
				} while (DB::num_rows($result) != 0 && ($ref = Refs::increment($ref)));
				return (DB::num_rows($result) == 0);
			}
			// it's a type that doesn't use references - shouldn't be calling here, but say yes anyways
			return true;
		}

?>