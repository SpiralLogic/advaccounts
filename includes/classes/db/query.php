<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Complex
 * Date: 22/08/11
 * Time: 12:24 AM
 * To change this template use File | Settings | File Templates.
 */

	abstract class Query extends Where {

		protected static $query = null;
		protected $results = null;
		protected $compiled_query = false;

		public $type;

		protected function __construct($type) {
			static::$query = $this;
			$this->type = $type;
		}

		public function getQuery() {
			if (!$this->compiled_query) $this->compiled_query = $this->execute();
			return $this->compiled_query;
		}

		public function exec($db = null) {
			$prepared = DBconnection::instance($db)->prepare($this->getQuery());
			var_dump($prepared);
			$prepared->execute($this->data);
			return DBconnection::instance($db)->lastInsertId();

		}

		public function fetch($db = null) {
			$this->result = new Result($this, $db);
			return $this->result;
		}

		public static function _fetch($db) {
			$result = new Result(clone(static::$query), $db);
			static::$query = null;
			return $result;
		}

	}
