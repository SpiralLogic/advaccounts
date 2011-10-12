<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 15/08/11
	 * Time: 5:46 AM
	 *
	 */

	Class DB_Update extends DB_Insert {

		public function __construct($table = false) {
			parent::__construct($table);
			$this->type = DB::UPDATE;
		}

		protected function _buildQuery() {
			$sql = "UPDATE " . $this->table . " SET ";
			foreach ($this->feilds as &$feild) {
				$feild = " $feild = :$feild";
			}
			$sql .= implode(', ', $this->feilds);
			$sql .= $this->_buildWhere();
			return $sql;
		}
	}