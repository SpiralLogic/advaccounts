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
	class GL_AccountType
	{
		function add($id, $name, $class_id, $parent)
		{
			$sql = "INSERT INTO chart_types (id, name, class_id, parent)
		VALUES ($id, " . DB::escape($name) . ", " . DB::escape($class_id) . ", " . DB::escape($parent) . ")";
			return DB::query($sql);
		}

		function update($id, $name, $class_id, $parent)
		{
			$sql = "UPDATE chart_types SET name=" . DB::escape($name) . ",
		class_id=" . DB::escape($class_id) . ", parent=" . DB::escape($parent) . " WHERE id = " . DB::escape($id);
			return DB::query($sql, "could not update account type");
		}

		function get_all($all = false, $class_id = false, $parent = false)
		{
			$sql = "SELECT * FROM chart_types";
			if (!$all) {
				$sql .= " WHERE !inactive";
			}
			if ($class_id != false) {
				$sql .= " AND class_id=" . DB::escape($class_id);
			}
			if ($parent == -1) {
				$sql .= " AND parent <= 0";
			} elseif ($parent != false) {
				$sql .= " AND parent=" . DB::escape($parent);
			}
			$sql .= " ORDER BY class_id, id";
			return DB::query($sql, "could not get account types");
		}

		function get($id)
		{
			$sql = "SELECT * FROM chart_types WHERE id = " . DB::escape($id);
			$result = DB::query($sql, "could not get account type");
			return DB::fetch($result);
		}

		function get_name($id)
		{
			$sql = "SELECT name FROM chart_types WHERE id = " . DB::escape($id);
			$result = DB::query($sql, "could not get account type");
			$row = DB::fetch_row($result);
			return $row[0];
		}

		function delete($id)
		{
			$sql = "DELETE FROM chart_types WHERE id = " . DB::escape($id);
			DB::query($sql, "could not delete account type");
		}
	}