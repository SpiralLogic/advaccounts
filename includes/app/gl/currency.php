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

class GL_Currency {


	public static function update($curr_abrev, $symbol, $currency, $country,
													 $hundreds_name, $auto_update) {
		$sql = "UPDATE currencies SET currency=" . DB::escape($currency)
		 . ", curr_symbol=" . DB::escape($symbol) . ",	country=" . DB::escape($country)
		 . ", hundreds_name=" . DB::escape($hundreds_name)
		 . ",auto_update = " . DB::escape($auto_update)
		 . " WHERE curr_abrev = " . DB::escape($curr_abrev);

		DB::query($sql, "could not update currency for $curr_abrev");
	}



	public static function add($curr_abrev, $symbol, $currency, $country,
												$hundreds_name, $auto_update) {
		$sql = "INSERT INTO currencies (curr_abrev, curr_symbol, currency,
			country, hundreds_name, auto_update)
		VALUES (" . DB::escape($curr_abrev) . ", " . DB::escape($symbol) . ", "
		 . DB::escape($currency) . ", " . DB::escape($country) . ", "
		 . DB::escape($hundreds_name) . "," . DB::escape($auto_update) . ")";

		DB::query($sql, "could not add currency for $curr_abrev");
	}



	public static function delete($curr_code) {
		$sql = "DELETE FROM currencies WHERE curr_abrev=" . DB::escape($curr_code);
		DB::query($sql, "could not delete currency	$curr_code");

		$sql = "DELETE FROM exchange_rates WHERE curr_code='$curr_code'";
		DB::query($sql, "could not delete exchange rates for currency $curr_code");
	}



	public static function get($curr_code) {
		$sql = "SELECT * FROM currencies WHERE curr_abrev=" . DB::escape($curr_code);
		$result = DB::query($sql, "could not get currency $curr_code");

		$row = DB::fetch($result);
		return $row;
	}



	public static function get_all($all = false) {
		$sql = "SELECT * FROM currencies";
		if (!$all) $sql .= " WHERE !inactive";
		return DB::query($sql, "could not get currencies");
	}


		//  CURRENCIES
	public static function select($name, $selected_id = null, $submit_on_change = false) {
			$sql = "SELECT curr_abrev, currency, inactive FROM currencies";
			// default to the company currency
			return combo_input($name, $selected_id, $sql, 'curr_abrev', 'currency', array(
																																									 'select_submit' => $submit_on_change, 'default' => Banking::get_company_currency(), 'async' => false));
		}

	public static function cells($label, $name, $selected_id = null, $submit_on_change = false) {
			if ($label != null) {
				echo "<td>$label</td>\n";
			}
			echo "<td>";
			echo GL_Currency::select($name, $selected_id, $submit_on_change);
			echo "</td>\n";
		}

	public static function row($label, $name, $selected_id = null, $submit_on_change = false) {
			echo "<tr><td class='label'>$label</td>";
			GL_Currency::cells(null, $name, $selected_id, $submit_on_change);
			echo "</tr>\n";
		}


}
