<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 22/10/11
	 * Time: 5:48 AM
	 * To change this template use File | Settings | File Templates.
	 */
	class Tags {
		public static function add($type, $name, $description) {
			$sql = "INSERT INTO tags (type, name, description)
 		VALUES (" . DB::escape($type) . ", " . DB::escape($name) . ", " . DB::escape($description) . ")";

			return DB::query($sql);
		}

		//--------------------------------------------------------------------------------------

		public static function update($id, $name, $description, $type = null) {
			$sql = "UPDATE tags SET name=" . DB::escape($name) . ",
                                        description=" . DB::escape($description);
			if ($type != null)
				$sql .= ", type=" . DB::escape($type);

			$sql .= " WHERE id = " . DB::escape($id);

			return DB::query($sql);
		}

		//--------------------------------------------------------------------------------------

		public static function get_all($type, $all = false) {
			$sql = "SELECT * FROM tags WHERE type=" . DB::escape($type);

			if (!$all) $sql .= " AND !inactive";

			$sql .= " ORDER BY name";

			return DB::query($sql, "could not get tags");
		}

		//--------------------------------------------------------------------------------------

		public static function get($id) {
			$sql = "SELECT * FROM tags WHERE id = " . DB::escape($id);

			$result = DB::query($sql, "could not get tag");

			return DB::fetch($result);
		}

		//--------------------------------------------------------------------------------------

		public static function get_type($id) {
			$sql = "SELECT type FROM tags WHERE id = " . DB::escape($id);

			$result = DB::query($sql, "could not get tag type");

			$row = DB::fetch_row($result);
			return $row[0];
		}

		//--------------------------------------------------------------------------------------

		public static function get_name($id) {
			$sql = "SELECT name FROM tags WHERE id = " . DB::escape($id);

			$result = DB::query($sql, "could not get tag name");

			$row = DB::fetch_row($result);
			return $row[0];
		}

		//--------------------------------------------------------------------------------------

		public static function get_description($id) {
			$sql = "SELECT description FROM tags WHERE id = " . DB::escape($id);

			$result = DB::query($sql, "could not get tag description");

			$row = DB::fetch_row($result);
			return $row[0];
		}

		//--------------------------------------------------------------------------------------

		public static function delete($id) {
			$sql = "DELETE FROM tags WHERE id = " . DB::escape($id);

			DB::query($sql, "could not delete tag");
		}

		//--------------------------------------------------------------------------------------

		public static function add_associations($recordid, $tagids) {
			foreach ($tagids as $tagid) {
				if (!$tagid) continue;
				$sql = "INSERT INTO tag_associations (record_id, tag_id)
 			VALUES (" . DB::escape($recordid) . ", " . DB::escape($tagid) . ")";

				DB::query($sql, "could not add tag association");
			}
		}

		//--------------------------------------------------------------------------------------

		public static function update_associations($type, $recordid, $tagids) {
			// Delete the old associations
			Tags::delete_associations($type, $recordid, false);
			// Add the new associations
			Tags::add_associations($recordid, $tagids);
		}

		//--------------------------------------------------------------------------------------
		// To delete tag associations, we need to specify the tag type.
		// Otherwise we may inadvertantly delete records for another type of tag
		//
		public static function delete_associations($type, $recordid, $all = false) {
			/* multiply table DELETE syntax available since MySQL 4.0.0:
							$sql = "DELETE ta FROM ".''."tag_associations ta
										INNER JOIN ".''."tags tags ON tags.id = ta.tag_id
										WHERE tags.type = ".DB::escape($type)." AND ta.record_id = ".DB::escape($recordid);
						*/
			// To support MySQL 3.xx we have to use multiply queries
			$sql = "SELECT * FROM tag_associations ta
 			INNER JOIN tags tags ON tags.id = ta.tag_id
 			WHERE tags.type = " . DB::escape($type) . " AND ta.record_id = " . DB::escape($recordid);
			if (!$all)
				$sql .= " AND tags.inactive = 0";
			$result = DB::query($sql, "could not select tag associations");

			while ($ta = DB::fetch($result)) {
				$sql2 = "DELETE FROM tag_associations WHERE
 			record_id = '" . $ta['record_id'] . "' AND tag_id=" . $ta['tag_id'];
				DB::query($sql2, "could not delete tag associations");
			}
		}

		//--------------------------------------------------------------------------------------

		public static function get_associated_records($id) {
			// Which table we query is based on the tag type
			$type = Tags::get_type($id);

			$table = $key = '';
			switch ($type) {
				case TAG_ACCOUNT:
					$table = "chart_master";
					$key   = "account_code";
					break;
				case TAG_DIMENSION:
					$table = "dimensions";
					$key   = "id";
					break;
			}

			$sql = "SELECT $table.* FROM $table
 		INNER JOIN tag_associations AS ta ON ta.record_id = $table.$key
 		INNER JOIN tags AS tags ON ta.tag_id = tags.id
 	        WHERE tags.id = " . DB::escape($id);

			return DB::query($sql, "could not get tag associations for tag");
		}

		//--------------------------------------------------------------------------------------

		public static function get_all_associated_with_record($type, $recordid) {
			$sql = "SELECT tags.* FROM tag_associations AS ta
 				INNER JOIN tags AS tags ON tags.id = ta.tag_id
 				WHERE tags.type = $type	AND ta.record_id = " . DB::escape($recordid);

			return DB::query($sql, "could not get tags associations for record");
		}
		//--------------------------------------------------------------------------------------

	}

?>