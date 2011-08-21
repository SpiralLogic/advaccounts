<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Complex
 * Date: 22/08/11
 * Time: 12:24 AM
 * To change this template use File | Settings | File Templates.
 */

	abstract class Query extends Where {

		protected static $query;
		protected static $results = null;
		protected static $rows = 0;
		public $type;

		protected function __construct($type) {
			static::$query = $this;
			$this->type = $type;
		}

		protected static function _getResults($db) {
			if (static::$results === null) {
				static::$results = new Result(static::$query, $db);
				static::$rows = static::$results->rowCount();
			}
			return static::$results;
		}

		public static function fetch($db) {

			$results = static::_getResults($db);
			if (static::$rows > 1) return $results->fetch();

			return static::$results;
		}

		public static function rowCount($db) {
			static::_getResults($db);
			return static::$rows;
		}
	}
