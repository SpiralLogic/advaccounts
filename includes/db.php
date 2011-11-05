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
		const SELECT = 0;
		const INSERT = 1;
		const UPDATE = 2;
		const DELETE = 4;
		protected static $_prepared = array();
		protected static $conn = array();
		protected static $current = false;

		final function __construct()
		{
		}

		protected static function _get($db = null, $config = array())
		{
			if ($db === null && static::$current) {
				return static::$current;
			}
			elseif ($db === null) {
				$config = $config ? : Config::get('db_default');
				$db = $config['name'];
			}
			if (!isset($conn[$db])) {
				static::$conn[$db] = static::$current = DB_Connection::instance($db, $config);
			}
			return static::$current;
		}

		public static function db($db)
		{
			if (!isset(static::$conn[$db])) {
				throw new DB_Exception('There is no connection: ' . $db);
			}
			static::$current = static::$conn[$db];
			return static::$current;
		}

		public static function query($sql, $fetchas = PDO::FETCH_OBJ)
		{
			return static::_get()->query($sql, $fetchas);
		}

		public static function quote($value)
		{
			return static::_get()->quote($value);
		}

		public static function escape($value, $null = false)
		{
			$value = trim($value);
			//reset default if second parameter is skipped
			$null = ($null === null) ? (false) : ($null);
			//check for null/unset/empty strings
			if ((!isset($value)) || (is_null($value)) || ($value === "")) {
				$value = ($null) ? ("NULL") : ("''");
			} else {
				if (is_string($value)) {
					//value is a string and should be quoted; determine best method based on available extensions
					$value = static::quote($value);
				} elseif (!is_numeric($value)) {
					//value is not a string nor numeric
					throw new DB_Exception("ERROR: incorrect data type send to sql query");
				}
			}
			return $value;
		}

		public static function prepare($sql)
		{
			static::$_prepared[static::$current->name] = static::_get()->prepare($sql);
		}

		public static function execute($data)
		{
			if (static::$_prepared) {
				if (Config::get('debug_sql')) {
					$sql = static::$_prepared[static::$current->name]->queryString;
					foreach (
						$data as $k => $v
					) {
						$sql = preg_replace('/\?/i', " '$v' ", $sql, 1); // outputs '123def abcdef abcdef' str_replace(,,$sql);
					}
					FB::info($sql);
				}
				static::$_prepared[static::$current->name]->execute($data);
				return static::$_prepared[static::$current->name]->fetchAll(PDO::FETCH_ASSOC);
			}
		}

		public static function select()
		{
			$columns = func_get_args();
			return new DB_Select($columns, static::_get());
		}

		public static function update($into)
		{
			return new DB_Update($into, static::_get());
		}

		public static function insert($into)
		{
			return new DB_Insert($into, static::_get());
		}

		public static function delete($into)
		{
			return new DB_Delete($into, static::_get());
		}

		public static function fetch()
		{
			return DB_Query::_fetch(static::_get());
		}
	}
