<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 23/12/10
 * Time: 2:39 PM
 * To change this template use File | Settings | File Templates.
 */
class contact_log
{

	private static $dbTable = 'contact_log';

	static function add($contact_id, $contact_name, $type, $message)
	{
		if (!isset($contact_id)) return false;
		if (!isset($contact_name)) return false;
		if (!isset($type)) return false;
		if (!isset($message)) return false;
		$sql = "INSERT INTO " . TB_PREF . self::$dbTable . " (contact_id, contact_name, type,
        message) VALUES (" . db_escape($contact_id) . "," . db_escape($contact_name) . "," . db_escape($type) . ",
        " . db_escape($message) . ")";
		db_query($sql, "Couldn't insert contact log");
		return db_insert_id();
	}

	static function read($contact_id, $type)
	{
		if (!isset($contact_id)) return false;
		if (!isset($type)) return false;
		$sql = "SELECT * FROM " . TB_PREF . self::$dbTable . " WHERE contact_id=" . $contact_id . " AND type=" .
			   db_escape($type) . " ORDER BY date DESC";
		$result = db_query($sql, "Couldn't get contact log entries");
		$results = array();
		while ($row = db_fetch_assoc($result)) {
			$results[] = $row;
		}
		return $results;
	}
}
