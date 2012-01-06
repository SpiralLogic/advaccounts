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
	class GL_Type
	{
		static public function add($id, $name, $class_id, $parent) {
			$sql = "INSERT INTO chart_types (id, name, class_id, parent)
		VALUES ($id, " . DB::escape($name) . ", " . DB::escape($class_id) . ", " . DB::escape($parent) . ")";
			return DB::query($sql);
		}

		static public function update($id, $name, $class_id, $parent) {
			$sql = "UPDATE chart_types SET name=" . DB::escape($name) . ",
		class_id=" . DB::escape($class_id) . ", parent=" . DB::escape($parent) . " WHERE id = " . DB::escape($id);
			return DB::query($sql, "could not update account type");
		}

		static public function get_all($all = false, $class_id = false, $parent = false) {
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

		static public function get($id) {
			$sql = "SELECT * FROM chart_types WHERE id = " . DB::escape($id);
			$result = DB::query($sql, "could not get account type");
			return DB::fetch($result);
		}

		static public function get_name($id) {
			$sql = "SELECT name FROM chart_types WHERE id = " . DB::escape($id);
			$result = DB::query($sql, "could not get account type");
			$row = DB::fetch_row($result);
			return $row[0];
		}

		static public function delete($id) {
			$sql = "DELETE FROM chart_types WHERE id = " . DB::escape($id);
			DB::query($sql, "could not delete account type");
		}

		static public function	select($name, $selected_id = null, $all_option = false, $all_option_numeric = true) {
			$sql = "SELECT id, name FROM chart_types";
			return select_box($name, $selected_id, $sql, 'id', 'name', array(
																																			'order' => 'id', 'spec_option' => $all_option, 'spec_id' => $all_option_numeric ?
				 0 : ALL_TEXT));
		}

		static public function	cells($label, $name, $selected_id = null, $all_option = false, $all_option_numeric = false) {
			if ($label != null) {
				echo "<td>$label</td>\n";
			}
			echo "<td>";
			echo GL_Type::select($name, $selected_id, $all_option, $all_option_numeric);
			echo "</td>\n";
		}

		static public function	row($label, $name, $selected_id = null, $all_option = false, $all_option_numeric = false) {
			echo "<tr><td class='label'>$label</td>";
			GL_Type::cells(null, $name, $selected_id, $all_option, $all_option_numeric);
			echo "</tr>\n";
		}
	}