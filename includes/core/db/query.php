<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 22/08/11
	 * Time: 12:24 AM
	 * To change this template use File | Settings | File Templates.
	 */
	abstract class DB_Query extends DB_Query_Where {
		/**
		 * @var DB_Query
		 */
		protected static $query = null;
		/**
		 * @var bool
		 */
		protected $compiled_query = false;
		/**
		 * @var
		 */
		protected $type;
		/**
		 * @var DB_Connection
		 */
		protected $conn;

		/**
		 * @abstract
		 *
		 */
		protected abstract function execute();

		/**
		 * @param $conn
		 */
		protected function __construct($conn) {
			$this->conn = $conn;
			static::$query = $this;
		}

		/**
		 * @param $data
		 *
		 * @return bool
		 */
		protected function getQuery($data) {
			if (!$this->compiled_query) {
				$this->compiled_query = $this->execute($data);
			}
			return $this->compiled_query;
		}

		/***
		 * @param null $data
		 *
		 * @return DB_Query_Result
		 */
		public function exec($data = null) {
			$result = $this->conn->exec($this->getQuery($data), $this->type, $this->data);
			if (!$result) {
				$sql = $this->compiled_query;
				foreach ($this->data as $k => $v) {
					$sql = str_replace(':' . $k, DB::quote($v), $sql);
				}
				Errors::show_db_error($sql);
			}
			return $result;
		}

		/***
		 * @return DB_Query_Result
		 */
		public function fetch() {
			return $this->exec(null);
		}

		/***
		 * @static
		 *
		 * @param $db
		 *
		 * @return DB_Query_Result
		 */
		public static function _fetch($db) {
			static::$query->conn = $db;
			$result = static::$query->fetch();
			static::$query = null;
			return $result;
		}
	}
