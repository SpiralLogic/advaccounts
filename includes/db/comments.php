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
			 . DBOld::escape($type) . " AND id=" . DBOld::escape($type_no);
			return DBOld::query($sql, "could not query comments transaction table");
		}

		//--------------------------------------------------------------------------------------------------

		public static function add($type, $type_no, $date_, $memo_) {
			if ($memo_ != null && $memo_ != "") {
				$date = Dates::date2sql($date_);
				$sql = "INSERT INTO comments (type, id, date_, memo_)
	    		VALUES (" . DBOld::escape($type) . ", " . DBOld::escape($type_no)
				 . ", '$date', " . DBOld::escape($memo_) . ")";
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
				$sql = "UPDATE comments SET memo_=" . DBOld::escape($memo_)
				 . " WHERE type=" . DBOld::escape($type) . " AND id=" . DBOld::escape($id)
				 . " AND date_='$date'";
				DBOld::query($sql, "could not update comments");
			}
		}

		//--------------------------------------------------------------------------------------------------

		public static function delete($type, $type_no) {
			$sql = "DELETE FROM comments WHERE type=" . DBOld::escape($type)
			 . " AND id=" . DBOld::escape($type_no);

			DBOld::query($sql, "could not delete from comments transaction table");
		}
		//--------------------------------------------------------------------------------------------------
	}
