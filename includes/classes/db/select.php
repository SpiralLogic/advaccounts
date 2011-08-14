<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Complex
 * Date: 15/08/11
 * Time: 5:35 AM
 * To change this template use File | Settings | File Templates.
 */

	class Select extends Query {

		protected $select = array();
		protected $from = array();
		protected $limit = array();
		protected $orderby = array();
		protected $groupby = array();

		public function select($column = null, $orderby = false, $groupby = false) {
			if ($column) $this->select[] = $column;
			if ($orderby) $this->orderby($column, ($orderby != 'desc') ? false : true);
			if ($groupby) $this->groupby($column);
			return $this;
		}

		public function from($table) {
			$this->from[] = $table;
			return $this;
		}

		function orderby($by, $asc = true) {
			if (!$asc) $by = $by . ' DESC';
			$this->orderby[] = $by;
			return $this;
		}

		function groupby($by) {
			$this->groupby[] = $by;
			return $this;
		}

		public function limit($start, $quantity) {
			$this->limit = array($start, $quantity);
			return $this;
		}

		public function exec($data) {
			return $this->_buildQuery();
		}

		protected function _buildQuery() {
			$sql = "SELECT ";
			$sql .= (empty($this->select)) ? '*' : implode(', ', $this->select);
			$sql .= " FROM " . implode(', ', $this->from);
			$sql .= parent::_buildQuery();
			return $sql;
		}

	}

