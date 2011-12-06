<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 12/10/11
	 * Time: 6:03 AM
	 * To change this template use File | Settings | File Templates.
	 */
	class DB_Comments
	{
		public static function add($type, $type_no, $date_, $memo_) {
			if ($memo_ != null && $memo_ != "") {
				$date = Dates::date2sql($date_);
				$sql = "INSERT INTO comments (type, id, date_, memo_)
		 		VALUES (" . DB::escape($type) . ", " . DB::escape($type_no)
				 . ", '$date', " . DB::escape($memo_) . ")";
				DB::query($sql, "could not add comments transaction entry");
			}
		}

		public static function delete($type, $type_no) {
			$sql = "DELETE FROM comments WHERE type=" . DB::escape($type)
			 . " AND id=" . DB::escape($type_no);
			DB::query($sql, "could not delete from comments transaction table");
		}

		static function display_row($type, $id) {
			$comments = DB_Comments::get($type, $id);
			if ($comments and DB::num_rows($comments)) {
				echo "<tr><td class='label'>Comments</td><td colspan=15>";
				while ($comment = DB::fetch($comments)) {
					echo $comment["memo_"] . "<br>";
				}
				echo "</td></tr>";
			}
		}

		public static function get($type, $type_no) {
			$sql = "SELECT * FROM comments WHERE type="
			 . DB::escape($type) . " AND id=" . DB::escape($type_no);
			return DB::query($sql, "could not query comments transaction table");
		}

		static function get_string($type, $type_no) {
			$str_return = "";
			$result = DB_Comments::get($type, $type_no);
			while ($comment = DB::fetch($result)) {
				if (strlen($str_return)) {
					$str_return = $str_return . " \n";
				}
				$str_return = $str_return . $comment["memo_"];
			}
			return $str_return;
		}

		public static function update($type, $id, $date_, $memo_) {
			if ($date_ == null) {
				DB_Comments::delete($type, $id);
				DB_Comments::add($type, $id, Dates::Today(), $memo_);
			} else {
				$date = Dates::date2sql($date_);
				$sql = "UPDATE comments SET memo_=" . DB::escape($memo_)
				 . " WHERE type=" . DB::escape($type) . " AND id=" . DB::escape($id)
				 . " AND date_='$date'";
				DB::query($sql, "could not update comments");
			}
		}
	}
