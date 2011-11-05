<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 29/12/10
	 * Time: 4:41 AM
	 * To change this template use File | Settings | File Templates.
	 */
	class DB {
		const SELECT = 0;
		const INSERT = 1;
		const UPDATE = 2;
		const DELETE = 4;
		protected static $_prepared = false;

		final function __construct() {
		}

		public static function query($sql, $db = null, $fetchas = PDO::FETCH_OBJ) {
			return DB_Connection::instance($db)->query($sql, $fetchas);
		}

		public static function quote($value, $db = null) {
			return DB_Connection::instance($db)->quote($value);
		}

		public static function escape($value, $null = false) {
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

		public static function prepare($sql, $db = null) {
			static::$_prepared = DB_Connection::instance($db)->prepare($sql);
		}

		public static function execute($data) {
			if (static::$_prepared) {
				if (Config::get('debug_sql')) {
					$sql = static::$_prepared->queryString;
					foreach (
						$data as $k => $v
					) {
						$sql = preg_replace('/\?/i', " '$v' ", $sql, 1); // outputs '123def abcdef abcdef' str_replace(,,$sql);
					}
					FB::info($sql);
				}
				static::$_prepared->execute($data);
				return static::$_prepared->fetchAll(PDO::FETCH_ASSOC);
			}
		}

		public static function select() {
			$columns = func_get_args();
			return new DB_Select($columns);
		}

		public static function update($into) {
			return new DB_Update($into);
		}

		public static function insert($into) {
			return new DB_Insert($into);
		}

		public static function delete($into) {
			return new DB_Delete($into);
		}

		public static function fetch($db = null) {
			return DB_Query::_fetch($db);
		}
	}
