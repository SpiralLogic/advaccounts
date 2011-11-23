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
		protected static $query = null;
		protected $compiled_query = false;
		protected $type;
		protected $conn;

		protected function __construct($db)
		{
			$this->conn = $db;
			static::$query = $this;
		}

		protected function getQuery($data)
		{
			if (!$this->compiled_query) {
				$this->compiled_query = $this->execute($data);
			}
			return $this->compiled_query;
		}
/***
 * @param null $data
 * @return DB_Query_Result
 */
		public function exec($data = null)
		{
			return $this->conn->exec($this->getQuery($data), $this->type, $this->data);
		}
/***
 * @return DB_Query_Result
 */
		public function fetch()
		{
			return $this->exec(null);
		}
/***
 * @static
 * @param $db
 * @return DB_Query_Result
 */
		public static function _fetch($db)
		{
			static::$query->conn = $db;
			$result = static::$query->fetch();
			static::$query = null;
			return $result;
		}
	}
