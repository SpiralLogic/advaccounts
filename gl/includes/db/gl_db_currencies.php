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
	//---------------------------------------------------------------------------------------------

	function update_currency($curr_abrev, $symbol, $currency, $country,
													 $hundreds_name, $auto_update) {
		$sql = "UPDATE currencies SET currency=" . DBOld::escape($currency)
		 . ", curr_symbol=" . DBOld::escape($symbol) . ",	country=" . DBOld::escape($country)
		 . ", hundreds_name=" . DBOld::escape($hundreds_name)
		 . ",auto_update = " . DBOld::escape($auto_update)
		 . " WHERE curr_abrev = " . DBOld::escape($curr_abrev);

		DBOld::query($sql, "could not update currency for $curr_abrev");
	}

	//---------------------------------------------------------------------------------------------

	function add_currency($curr_abrev, $symbol, $currency, $country,
												$hundreds_name, $auto_update) {
		$sql = "INSERT INTO currencies (curr_abrev, curr_symbol, currency,
			country, hundreds_name, auto_update)
		VALUES (" . DBOld::escape($curr_abrev) . ", " . DBOld::escape($symbol) . ", "
		 . DBOld::escape($currency) . ", " . DBOld::escape($country) . ", "
		 . DBOld::escape($hundreds_name) . "," . DBOld::escape($auto_update) . ")";

		DBOld::query($sql, "could not add currency for $curr_abrev");
	}

	//---------------------------------------------------------------------------------------------

	function delete_currency($curr_code) {
		$sql = "DELETE FROM currencies WHERE curr_abrev=" . DBOld::escape($curr_code);
		DBOld::query($sql, "could not delete currency	$curr_code");

		$sql = "DELETE FROM exchange_rates WHERE curr_code='$curr_code'";
		DBOld::query($sql, "could not delete exchange rates for currency $curr_code");
	}

	//---------------------------------------------------------------------------------------------

	function get_currency($curr_code) {
		$sql = "SELECT * FROM currencies WHERE curr_abrev=" . DBOld::escape($curr_code);
		$result = DBOld::query($sql, "could not get currency $curr_code");

		$row = DBOld::fetch($result);
		return $row;
	}

	//---------------------------------------------------------------------------------------------

	function get_currencies($all = false) {
		$sql = "SELECT * FROM currencies";
		if (!$all) $sql .= " WHERE !inactive";
		return DBOld::query($sql, "could not get currencies");
	}

	//---------------------------------------------------------------------------------------------

?>