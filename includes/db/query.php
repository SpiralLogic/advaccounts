<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 22/08/11
	 * Time: 12:24 AM
	 * To change this template use File | Settings | File Templates.
	 */

	abstract class DB_Query extends DB_Where {

		protected static $query = null;
		protected $compiled_query = false;
		protected $type;

		protected function __construct() {
			static::$query = $this;
		}

		protected function getQuery($data) {
			if (!$this->compiled_query) $this->compiled_query = $this->execute($data);
			return $this->compiled_query;
		}

		public function exec($data = null, $db = null) {

			return DB_Connection::instance($db)->exec($this->getQuery($data), $this->type, $this->data);
		}

		public function fetch($db = null) {
			return $this->exec(null, $db);
		}

		public static function _fetch($db = null) {
			$result = static::$query->fetch($db);
			static::$query = null;
			return $result;
		}
	}
