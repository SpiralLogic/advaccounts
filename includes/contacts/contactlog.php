<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 23/12/10
	 * Time: 2:39 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class Contacts_ContactLog {

		private static $dbTable = 'contact_log';
		const CUSTOMER = 'C';
		const SUPPLIER = 'S';

		static function add($contact_id, $contact_name, $type, $message) {
			if (!isset($contact_id)) return false;
			if (!isset($contact_name)) return false;
			if (!isset($type)) return false;
			if (!isset($message)) return false;
			$sql = "INSERT INTO " . self::$dbTable . " (contact_id, contact_name, type,
        message) VALUES (" . DB::escape($contact_id) . "," . DB::escape($contact_name) . "," . DB::escape($type) . ",
        " . DB::escape($message) . ")";
			DBOld::query($sql, "Couldn't insert contact log");
			return DBOld::insert_id();
		}

		static function read($contact_id, $type) {
			if (!isset($contact_id) || $contact_id == 0) return false;
			if (!isset($type)) return false;
			$sql = "SELECT * FROM " . self::$dbTable . " WHERE contact_id=" . $contact_id . " AND type=" . DB::escape($type) . " ORDER BY date DESC";
			$result = DBOld::query($sql, "Couldn't get contact log entries");
			$results = array();
			while ($row = DBOld::fetch_assoc($result)) {
				$results[] = $row;
			}
			return $results;
		}
	}
