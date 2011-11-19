<?php
class GL_AccountClass {
	function add($id, $name, $ctype) {
		$sql = "INSERT INTO chart_class (cid, class_name, ctype)
		VALUES (" . DB::escape($id) . ", " . DB::escape($name) . ", " . DB::escape($ctype) . ")";

		return DB::query($sql);
	}

	function update($id, $name, $ctype) {
		$sql = "UPDATE chart_class SET class_name=" . DB::escape($name) . ",
		ctype=" . DB::escape($ctype) . " WHERE cid = " . DB::escape($id);

		return DB::query($sql);
	}

	function get_all($all = false, $balance = -1) {
		$sql = "SELECT * FROM chart_class";
		if (!$all)
			$sql .= " WHERE !inactive";
		if ($balance == 0)
			$sql .= " AND ctype>" . CL_EQUITY . " OR ctype=0";
		elseif ($balance == 1)
			$sql .= " AND ctype>0 AND ctype<" . CL_INCOME;
		$sql .= " ORDER BY cid";

		return DB::query($sql, "could not get account classes");
	}

	function get($id) {
		$sql = "SELECT * FROM chart_class WHERE cid = " . DB::escape($id);

		$result = DB::query($sql, "could not get account type");

		return DB::fetch($result);
	}

	function get_name($id) {
		$sql = "SELECT class_name FROM chart_class WHERE cid =" . DB::escape($id);

		$result = DB::query($sql, "could not get account type");

		$row = DB::fetch_row($result);
		return $row[0];
	}

	function delete($id) {
		$sql = "DELETE FROM chart_class WHERE cid = " . DB::escape($id);

		DB::query($sql, "could not delete account type");
	}
	}