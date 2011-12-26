<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 22/08/11
	 * Time: 12:24 AM
	 * To change this template use File | Settings | File Templates.
	 */
	abstract class DB_Query extends DB_Query_Where
	{
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
		 * @var DB
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
			try {
			$result = $this->conn->exec($this->getQuery($data), $this->type, $this->data);
			return $result;
			}catch (DBException $e) {
return  false;
			}
		}

		/***
		 * @return DB_Query_Result
		 */
		public function fetch() {
			return $this->exec(null);
		}
	}
