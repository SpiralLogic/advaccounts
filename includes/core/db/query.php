<?php
	/**
	 * PHP version 5.4
	 *
	 * @category  PHP
	 * @package   ADVAccounts
	 * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
	 * @copyright 2010 - 2012
	 * @link      http://www.advancedgroup.com.au
	 *
	 **/
	abstract class DB_Query extends DB_Query_Where
	{
		/**
		 * @var DB_Query
		 */
		static protected $query = null;
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
		 * @return DB_Query_Result|int|bool
		 */
		public function exec($data = null) {
			$result = $this->conn->exec($this->getQuery($data), $this->type, $this->data);
			return $result;
		}
		/***
		 * @return DB_Query_Result
		 */
		public function fetch() {
			return $this->exec(null);
		}
	}
