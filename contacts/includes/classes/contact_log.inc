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


	private $dbTable;
	private static $_instance;

	private function __construct()
	{
	}
	public static function __callStatic($func, $args)
	{
		(static::$_instance !== null) ? : static::$_instance = new static;
		static::$_instance->dbTable = TB_PREF . 'contact_log';
		static::$_instance->$func($args);
	}
	protected function add($args = array())
	{
		$contact_id = (isset($args['contact_id'])) ? $args['contact_id'] : false;
		$contact_type = (isset($args['contact_type'])) ? $args['contact_type'] : false;
		$message = (isset($args['message'])) ? $args['message'] : false;
		$date = date('Y-m-d H:i:s');
		$sql = "INSERT INTO " . $this->dbTable . " (contact_id, date, type, messsage) VALUES (" . db_escape($contact_id) . "," . strtotime($date) . "," . db_escape($contact_type) . ","
		       . db_escape($message) . ")";
		db_query($sql, "Couldn't insert contact log");
	    return db_insert_id();
	}
	protected function read($args = array())
	{
		$contact_id = (isset($args['contact_id'])) ? $args['contact_id'] : false;
		$contact_type = (isset($args['contact_type'])) ? $args['contact_type'] : false;
		$sql = "SELECT * FROM " . $this->dbTable . " WHERE contact_id=" . db_escape($contact_id) . " AND contact_type=" . db_escape($contact_type) . " ORDER BY date";
		$result = db_query($sql, "Couldn't get contact log entries");
		return db_fetch_assoc($result);
	}
}
