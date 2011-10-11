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
			return DBConnection::instance($db)->query($sql, $fetchas);
		}

		public static function quote($value, $db = null) {
			return DBConnection::instance($db)->quote($value);
		}

		public static function prepare($sql, $db = null) {
			static::$_prepared = DBconnection::instance($db)->prepare($sql);
		}

		public static function execute($data) {
			if (static::$_prepared) {
				if (Config::get('debug.sql')) {
					$sql = static::$_prepared->queryString;
					foreach ($data as $k => $v) {
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
			return new Select($columns);
		}

		public static function update($into) {
			return new Update($into);
		}

		public static function insert($into) {
			return new Insert($into);
		}

		public static function delete($into) {
			return new Delete($into);
		}

		public static function fetch($db = null) {
			return Query::_fetch($db);
		}
	}
