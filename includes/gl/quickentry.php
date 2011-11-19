<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 13/11/11
	 * Time: 10:03 AM
	 * To change this template use File | Settings | File Templates.
	 */
	class GL_QuickEntry
	{
		//---------------------------------------------------------------------------------------------

		public static function add($description, $type, $base_amount, $base_desc) {
			$sql = "INSERT INTO quick_entries (description, type, base_amount, base_desc)
		VALUES (" . DB::escape($description) . ", " . DB::escape($type) . ", "
			 . DB::escape($base_amount) . ", " . DB::escape($base_desc) . ")";

			DB::query($sql, "could not insert quick entry for $description");
		}

		//---------------------------------------------------------------------------------------------

		public static function update($selected_id, $description, $type, $base_amount, $base_desc) {
			$sql = "UPDATE quick_entries	SET description = " . DB::escape($description) . ",
			type=" . DB::escape($type) . ", base_amount=" . DB::escape($base_amount)
			 . ", base_desc=" . DB::escape($base_desc) . "
			WHERE id = " . DB::escape($selected_id);

			DB::query($sql, "could not update quick entry for $selected_id");
		}

		//---------------------------------------------------------------------------------------------

		public static function delete($selected_id) {
			$sql = "DELETE FROM quick_entries WHERE id=" . DB::escape($selected_id);

			DB::query($sql, "could not delete quick entry $selected_id");
		}

		//---------------------------------------------------------------------------------------------

		public static function add_line($qid, $action, $dest_id, $amount, $dim, $dim2) {
			$sql = "INSERT INTO quick_entry_lines
			(qid, action, dest_id, amount, dimension_id, dimension2_id)
		VALUES
			($qid, " . DB::escape($action) . "," . DB::escape($dest_id) . ",
				" . DB::escape($amount) . ", " . DB::escape($dim) . ", " . DB::escape($dim2) . ")";

			DB::query($sql, "could not insert quick entry line for $qid");
		}

		//---------------------------------------------------------------------------------------------

		public static function update_line($selected_id, $qid, $action, $dest_id, $amount, $dim, $dim2) {
			$sql = "UPDATE quick_entry_lines SET qid = " . DB::escape($qid)
			 . ", action=" . DB::escape($action) . ",
			dest_id=" . DB::escape($dest_id) . ", amount=" . DB::escape($amount)
			 . ", dimension_id=" . DB::escape($dim) . ", dimension2_id=" . DB::escape($dim2) . "
			WHERE id = " . DB::escape($selected_id);

			DB::query($sql, "could not update quick entry line for $selected_id");
		}

		//---------------------------------------------------------------------------------------------

		public static function delete_line($selected_id) {
			$sql = "DELETE FROM quick_entry_lines WHERE id=" . DB::escape($selected_id);

			DB::query($sql, "could not delete quick entry line $selected_id");
		}

		//---------------------------------------------------------------------------------------------

		public static function has($type = null) {
			$sql = "SELECT id FROM quick_entries";
			if ($type != null)
				$sql .= " WHERE type=" . DB::escape($type);

			$result = DB::query($sql, "could not retreive quick entries");
			return DB::num_rows($result) > 0;
		}

		public static function get_all($type = null) {
			$sql = "SELECT * FROM quick_entries";
			if ($type != null)
				$sql .= " WHERE type=" . DB::escape($type);
			$sql .= " ORDER BY description";

			return DB::query($sql, "could not retreive quick entries");
		}

		public static function get($selected_id) {
			$sql = "SELECT * FROM quick_entries WHERE id=" . DB::escape($selected_id);

			$result = DB::query($sql, "could not retreive quick entry $selected_id");

			return DB::fetch($result);
		}

		public static function get_lines($qid) {
			$sql = "SELECT quick_entry_lines.*, chart_master.account_name,
				tax_types.name as tax_name
			FROM quick_entry_lines
			LEFT JOIN chart_master ON
				quick_entry_lines.dest_id = chart_master.account_code
			LEFT JOIN tax_types ON
				quick_entry_lines.dest_id = tax_types.id
			WHERE
				qid=" . DB::escape($qid) . " ORDER by id";

			return DB::query($sql, "could not retreive quick entries");
		}

		public static function has_lines($qid) {
			$sql = "SELECT id FROM quick_entry_lines WHERE qid=" . DB::escape($qid);

			$result = DB::query($sql, "could not retreive quick entries");
			return DB::num_rows($result) > 0;
		}

		//---------------------------------------------------------------------------------------------

		public static function has_line($selected_id) {
			$sql = "SELECT * FROM quick_entry_lines WHERE id=" . DB::escape($selected_id);

			$result = DB::query($sql, "could not retreive quick entry for $selected_id");

			return DB::fetch($result);
		}

	}
