<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 28/08/11
	 * Time: 4:23 PM
	 * To change this template use File | Settings | File Templates.
	 */

		class DB_Query_Delete extends DB_Query {

		protected $table;

		public function __construct($table = false,$db) {
			$this->table = $table;
			$this->type = DB::DELETE;
			parent::__construct($db);
		}

		public function execute() {
			return $this->_buildQuery();
		}

		protected function _buildQuery() {
			$sql = "DELETE FROM " . $this->table;
			$sql .= $this->_buildWhere();
			return $sql;
		}
	}
