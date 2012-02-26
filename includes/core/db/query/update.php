<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 15/08/11
	 * Time: 5:46 AM
	 *
	 */
	Class DB_Query_Update extends DB_Query_Insert {
		/**
		 * @param bool $table
		 * @param			$db
		 */
		public function __construct($table = false, $db) {
			parent::__construct($table, $db);
			$this->type = DB::UPDATE;
		}


		/**
		 * @return string
		 */
		protected function _buildQuery() {
			$sql = "UPDATE " . $this->table . " SET ";
			foreach ($this->fields as &$feild) {
				$feild = " $feild = :$feild";
			}
			$sql .= implode(', ', $this->fields);
			$sql .= $this->_buildWhere();
			return $sql;
		}
	}