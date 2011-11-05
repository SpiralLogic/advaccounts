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
	function add_dimension($reference, $name, $type_, $date_, $due_date, $memo_) {

		DBOld::begin_transaction();

		$date = Dates::date2sql($date_);
		$duedate = Dates::date2sql($due_date);

		$sql = "INSERT INTO dimensions (reference, name, type_, date_, due_date)
		VALUES (" . DB::escape($reference) . ", " . DB::escape($name) . ", " . DB::escape($type_)
		 . ", '$date', '$duedate')";
		DBOld::query($sql, "could not add dimension");
		$id = DBOld::insert_id();

		DB_Comments::add(ST_DIMENSION, $id, $date_, $memo_);

		Refs::save(ST_DIMENSION, $id, $reference);

		DBOld::commit_transaction();

		return $id;
	}

	function update_dimension($id, $name, $type_, $date_, $due_date, $memo_) {
		DBOld::begin_transaction();

		$date = Dates::date2sql($date_);
		$duedate = Dates::date2sql($due_date);

		$sql = "UPDATE dimensions SET name=" . DB::escape($name) . ",
		type_ = " . DB::escape($type_) . ",
		date_='$date',
		due_date='$duedate'
		WHERE id = " . DB::escape($id);

		DBOld::query($sql, "could not update dimension");

		DB_Comments::update(ST_DIMENSION, $id, null, $memo_);

		DBOld::commit_transaction();

		return $id;
	}

	function delete_dimension($id) {
		DBOld::begin_transaction();

		// delete the actual dimension
		$sql = "DELETE FROM dimensions WHERE id=" . DB::escape($id);
		DBOld::query($sql, "The dimension could not be deleted");

		DB_Comments::delete(ST_DIMENSION, $id);

		DBOld::commit_transaction();
	}

	//--------------------------------------------------------------------------------------

	function get_dimension($id, $allow_null = false) {
		$sql = "SELECT * FROM dimensions	WHERE id=" . DB::escape($id);

		$result = DBOld::query($sql, "The dimension could not be retrieved");

		if (!$allow_null && DBOld::num_rows($result) == 0)
			Errors::show_db_error("Could not find dimension $id", $sql);

		return DBOld::fetch($result);
	}

	//--------------------------------------------------------------------------------------

	function get_dimension_string($id, $html = false, $space = ' ') {
		if ($id <= 0) {
			if ($html)
				$dim = "&nbsp;";
			else
				$dim = "";
		} else {
			$row = get_dimension($id, true);
			$dim = $row['reference'] . $space . $row['name'];
		}

		return $dim;
	}

	//--------------------------------------------------------------------------------------

	function get_dimensions() {
		$sql = "SELECT * FROM dimensions ORDER BY date_";

		return DBOld::query($sql, "The dimensions could not be retrieved");
	}

	//--------------------------------------------------------------------------------------

	function dimension_has_deposits($id) {
		return dimension_has_payments($id);
	}

	//--------------------------------------------------------------------------------------

	function dimension_has_payments($id) {
		$sql = "SELECT SUM(amount) FROM gl_trans WHERE dimension_id = " . DB::escape($id);
		$res = DBOld::query($sql, "Transactions could not be calculated");
		$row = DBOld::fetch_row($res);
		return ($row[0] != 0.0);
	}

	function dimension_is_closed($id) {
		$result = get_dimension($id);
		return ($result['closed'] == '1');
	}

	//--------------------------------------------------------------------------------------

	function close_dimension($id) {
		$sql = "UPDATE dimensions SET closed='1' WHERE id = " . DB::escape($id);
		DBOld::query($sql, "could not close dimension");
	}

	//--------------------------------------------------------------------------------------

	function reopen_dimension($id) {
		$sql = "UPDATE dimensions SET closed='0' WHERE id = $id";
		DBOld::query($sql, "could not reopen dimension");
	}

?>