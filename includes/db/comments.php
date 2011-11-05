<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 12/10/11
	 * Time: 6:03 AM
	 * To change this template use File | Settings | File Templates.
	 */
	class DB_Comments {

		//--------------------------------------------------------------------------------------------------

		public static function get($type, $type_no) {
			$sql = "SELECT * FROM comments WHERE type="
			 . DB::escape($type) . " AND id=" . DB::escape($type_no);
			return DBOld::query($sql, "could not query comments transaction table");
		}

		//--------------------------------------------------------------------------------------------------

		public static function add($type, $type_no, $date_, $memo_) {
			if ($memo_ != null && $memo_ != "") {
				$date = Dates::date2sql($date_);
				$sql = "INSERT INTO comments (type, id, date_, memo_)
	    		VALUES (" . DB::escape($type) . ", " . DB::escape($type_no)
				 . ", '$date', " . DB::escape($memo_) . ")";
				DBOld::query($sql, "could not add comments transaction entry");
			}
		}

		//--------------------------------------------------------------------------------------------------

		public static function update($type, $id, $date_, $memo_) {
			if ($date_ == null) {
				DB_Comments::delete($type, $id);
				DB_Comments::add($type, $id, Dates::Today(), $memo_);
			} else {
				$date = Dates::date2sql($date_);
				$sql = "UPDATE comments SET memo_=" . DB::escape($memo_)
				 . " WHERE type=" . DB::escape($type) . " AND id=" . DB::escape($id)
				 . " AND date_='$date'";
				DBOld::query($sql, "could not update comments");
			}
		}

		//--------------------------------------------------------------------------------------------------

		public static function delete($type, $type_no) {
			$sql = "DELETE FROM comments WHERE type=" . DB::escape($type)
			 . " AND id=" . DB::escape($type_no);

			DBOld::query($sql, "could not delete from comments transaction table");
		}
		//--------------------------------------------------------------------------------------------------
	}
