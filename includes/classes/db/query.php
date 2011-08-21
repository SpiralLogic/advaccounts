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
		public $type;

		protected function __construct($type) {
			static::$query = $this;
			$this->type = $type;
		}

		public static function fetch($db) {
			if (static::$results === null) {
				static::$results = new Result(static::$query, $db);

			}
			return static::$results;
		}

	}
