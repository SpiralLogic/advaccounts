<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Complex
 * Date: 29/12/10
 * Time: 4:41 AM
 * To change this template use File | Settings | File Templates.
 */
class DB
{
	protected static $conn;
	protected static $showsql = false;
	protected static $debug = false;
	protected static $result;
	protected static $dbname;
	protected static $dbpass;
	protected static $dbuser;
	protected static $dbhost;
	protected static $dbport = 3306;

	static function query($sql)
	{
		if (!self::$conn) self::_connect();
		if (self::$showsql) echo "<pre style='size:2, color:000099'>" . $sql . "</pre>\n";
		try {
			$results = self::$conn->query($sql);
		} catch (PDOException $e) {
			return self::_error($e);
		}
		return $results;
	}

	protected static function _connect()
	{
		global $db_connections;
		self::$dbhost = $db_connections[0]['host'];
		self::$dbname = $db_connections[0]['dbname'];
		self::$dbuser = $db_connections[0]['dbuser'];
		self::$dbpass = $db_connections[0]['dbpassword'];
		try {
			self::$conn = new PDO('mysql:host=' . self::$dbhost . ':' . self::$dbport . ';dbname=' . self::$dbname, self::$dbuser, self::$dbpass,
				array(PDO::ATTR_PERSISTENT => true));
		} catch (PDOException $e) {
			self::_error($e, true);
		}
	}

	protected static function _error(PDOException $e, $exit = false, $rollback = false)
	{
		if (function_exists('xdebug_call_file')) {
			$error = '<p>DATABASE ERROR: <br>At file ' . xdebug_call_file() . ':' . xdebug_call_line() . ':<br>' . $e->getMessage() . '</p>';
		} else {
			$error = '<p>DATABASE ERROR: <pre>' . $e->getTraceAsString() . '</pre></p>';
		}
		if ($rollback) self::$conn->rollBack();
		trigger_error($error, E_USER_ERROR);
		if ($exit) exit;
		return false;
	}
}
