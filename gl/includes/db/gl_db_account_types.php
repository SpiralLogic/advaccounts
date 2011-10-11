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
	function add_account_type($id, $name, $class_id, $parent) {
		$sql = "INSERT INTO chart_types (id, name, class_id, parent)
		VALUES ($id, " . DBOld::escape($name) . ", " . DBOld::escape($class_id) . ", " . DBOld::escape($parent) . ")";

		return DBOld::query($sql);
	}

	function update_account_type($id, $name, $class_id, $parent) {
		$sql = "UPDATE chart_types SET name=" . DBOld::escape($name) . ",
		class_id=" . DBOld::escape($class_id) . ", parent=" . DBOld::escape($parent)
		 . " WHERE id = " . DBOld::escape($id);

		return DBOld::query($sql, "could not update account type");
	}

	function get_account_types($all = false, $class_id = false, $parent = false) {
		$sql = "SELECT * FROM chart_types";

		if (!$all)
			$sql .= " WHERE !inactive";
		if ($class_id != false)
			$sql .= " AND class_id=" . DBOld::escape($class_id);
		if ($parent == -1)
			$sql .= " AND parent <= 0";
		elseif ($parent != false)
			$sql .= " AND parent=" . DBOld::escape($parent);
		$sql .= " ORDER BY class_id, id";

		return DBOld::query($sql, "could not get account types");
	}

	function get_account_type($id) {
		$sql = "SELECT * FROM chart_types WHERE id = " . DBOld::escape($id);

		$result = DBOld::query($sql, "could not get account type");

		return DBOld::fetch($result);
	}

	function get_account_type_name($id) {
		$sql = "SELECT name FROM chart_types WHERE id = " . DBOld::escape($id);

		$result = DBOld::query($sql, "could not get account type");

		$row = DBOld::fetch_row($result);
		return $row[0];
	}

	function delete_account_type($id) {
		$sql = "DELETE FROM chart_types WHERE id = " . DBOld::escape($id);

		DBOld::query($sql, "could not delete account type");
	}

	function add_account_class($id, $name, $ctype) {
		$sql = "INSERT INTO chart_class (cid, class_name, ctype)
		VALUES (" . DBOld::escape($id) . ", " . DBOld::escape($name) . ", " . DBOld::escape($ctype) . ")";

		return DBOld::query($sql);
	}

	function update_account_class($id, $name, $ctype) {
		$sql = "UPDATE chart_class SET class_name=" . DBOld::escape($name) . ",
		ctype=" . DBOld::escape($ctype) . " WHERE cid = " . DBOld::escape($id);

		return DBOld::query($sql);
	}

	function get_account_classes($all = false, $balance = -1) {
		$sql = "SELECT * FROM chart_class";
		if (!$all)
			$sql .= " WHERE !inactive";
		if ($balance == 0)
			$sql .= " AND ctype>" . CL_EQUITY . " OR ctype=0";
		elseif ($balance == 1)
			$sql .= " AND ctype>0 AND ctype<" . CL_INCOME;
		$sql .= " ORDER BY cid";

		return DBOld::query($sql, "could not get account classes");
	}

	function get_account_class($id) {
		$sql = "SELECT * FROM chart_class WHERE cid = " . DBOld::escape($id);

		$result = DBOld::query($sql, "could not get account type");

		return DBOld::fetch($result);
	}

	function get_account_class_name($id) {
		$sql = "SELECT class_name FROM chart_class WHERE cid =" . DBOld::escape($id);

		$result = DBOld::query($sql, "could not get account type");

		$row = DBOld::fetch_row($result);
		return $row[0];
	}

	function delete_account_class($id) {
		$sql = "DELETE FROM chart_class WHERE cid = " . DBOld::escape($id);

		DBOld::query($sql, "could not delete account type");
	}

?>