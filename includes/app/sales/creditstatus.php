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
	class Sales_CreditStatus
	{
		static public function add($description, $disallow_invoicing) {
			$sql
			 = "INSERT INTO credit_status (reason_description, dissallow_invoices)
		VALUES (" . DB::escape($description) . "," . DB::escape($disallow_invoicing) . ")";
			DB::query($sql, "could not add credit status");
		}

		static public function update($status_id, $description, $disallow_invoicing) {
			$sql = "UPDATE credit_status SET reason_description=" . DB::escape($description) . ",
		dissallow_invoices=" . DB::escape($disallow_invoicing) . " WHERE id=" . DB::escape($status_id);
			DB::query($sql, "could not update credit status");
		}

		static public function get_all($all = false) {
			$sql = "SELECT * FROM credit_status";
			if (!$all) {
				$sql .= " WHERE !inactive";
			}
			return DB::query($sql, "could not get all credit status");
		}

		static public function get($status_id) {
			$sql = "SELECT * FROM credit_status WHERE id=" . DB::escape($status_id);
			$result = DB::query($sql, "could not get credit status");
			return DB::fetch($result);
		}

		static public function delete($status_id) {
			$sql = "DELETE FROM credit_status WHERE id=" . DB::escape($status_id);
			DB::query($sql, "could not delete credit status");
		}

		static public function select($name, $selected_id = null, $disabled = null) {
			if ($disabled === null) {
				$disabled = (!User::i()->can_access(SA_CUSTOMER_CREDIT));
			}
			$sql = "SELECT id, reason_description, inactive FROM credit_status";
			return select_box($name, $selected_id, $sql, 'id', 'reason_description', array('disabled' => $disabled));
		}

		static public function cells($label, $name, $selected_id = null, $disabled = null) {
			if ($label != null) {
				echo "<td>$label</td>\n";
			}
			echo "<td>";
			echo Sales_CreditStatus::select($name, $selected_id, $disabled);
			echo "</td>\n";
		}

		static public function row($label, $name, $selected_id = null, $disabled = null) {
			echo "<tr><td class='label'>$label</td>";
			Sales_CreditStatus::cells(null, $name, $selected_id, $disabled);
			echo "</tr>\n";
		}
	}
