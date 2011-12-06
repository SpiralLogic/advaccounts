<?php
	class GL_Class {
		public static function add($id, $name, $ctype) {
			$sql = "INSERT INTO chart_class (cid, class_name, ctype)
		VALUES (" . DB::escape($id) . ", " . DB::escape($name) . ", " . DB::escape($ctype) . ")";
			return DB::query($sql);
		}

		public static function update($id, $name, $ctype) {
			$sql = "UPDATE chart_class SET class_name=" . DB::escape($name) . ",
		ctype=" . DB::escape($ctype) . " WHERE cid = " . DB::escape($id);
			return DB::query($sql);
		}

		public static function get_all($all = false, $balance = -1) {
			$sql = "SELECT * FROM chart_class";
			if (!$all) {
				$sql .= " WHERE !inactive";
			}
			if ($balance == 0) {
				$sql .= " AND ctype>" . CL_EQUITY . " OR ctype=0";
			}
			elseif ($balance == 1)
			{
				$sql .= " AND ctype>0 AND ctype<" . CL_INCOME;
			}
			$sql .= " ORDER BY cid";
			return DB::query($sql, "could not get account classes");
		}

		public static function get($id) {
			$sql = "SELECT * FROM chart_class WHERE cid = " . DB::escape($id);
			$result = DB::query($sql, "could not get account type");
			return DB::fetch($result);
		}

		public static function get_name($id) {
			$sql = "SELECT class_name FROM chart_class WHERE cid =" . DB::escape($id);
			$result = DB::query($sql, "could not get account type");
			$row = DB::fetch_row($result);
			return $row[0];
		}

		public static function delete($id) {
			$sql = "DELETE FROM chart_class WHERE cid = " . DB::escape($id);
			DB::query($sql, "could not delete account type");
		}

		public static function	select($name, $selected_id = null, $submit_on_change = false) {
			$sql = "SELECT cid, class_name FROM chart_class";
			return select_box($name, $selected_id, $sql, 'cid', 'class_name', array(
				'select_submit' => $submit_on_change, 'async' => false));
		}

		public static function	cells($label, $name, $selected_id = null, $submit_on_change = false) {
			if ($label != null) {
				echo "<td>$label</td>\n";
			}
			echo "<td>";
			echo GL_Class::select($name, $selected_id, $submit_on_change);
			echo "</td>\n";
		}

		public static function	row($label, $name, $selected_id = null, $submit_on_change = false) {
			echo "<tr><td class='label'>$label</td>";
			GL_Class::cells(null, $name, $selected_id, $submit_on_change);
			echo "</tr>\n";
		}

		public static function	types_row($label, $name, $selected_id = null, $submit_on_change = false) {
			global $class_types;
			echo "<tr><td class='label'>$label</td><td>";
			echo array_selector($name, $selected_id, $class_types, array('select_submit' => $submit_on_change));
			echo "</td></tr>\n";
		}
	}