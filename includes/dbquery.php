<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Complex
 * Date: 14/08/11
 * Time: 11:13 PM
 * To change this template use File | Settings | File Templates.
 */

	class DBQuery {

		protected $select = array();
		protected $insert = array();
		protected $from = array();
		protected $where = array();
		protected $limit = array();
		protected $orderby = array();
		protected $groupby = array();
		public $data = array();

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

		protected function _where($condition, $type = 'AND', $uservar) {
			if ($uservar !== null) {
				$this->data[] = $uservar;
				$condition = $condition . '?';
			}
			$this->where[] = (empty($this->where)) ? $condition : $type . ' ' . $condition;
			return $this;
		}

		public function where($condition, $uservar = null) {
			return $this->_where($condition, '', $uservar);
		}

		public function or_where($condition, $uservar = null) {
			return $this->_where($condition, 'OR', $uservar);
		}

		public function and_where($condition, $uservar = null) {
			return $this->_where($condition, 'AND', $uservar);
		}

		public function or_open($condition, $uservar = null) {
			return $this->_where($condition, 'OR (', $uservar);
		}

		public function and_open($condition, $uservar = null) {
			return $this->_where($condition, 'AND (', $uservar);
		}

		public function close_and($condition, $uservar = null) {
			return $this->_where($condition, ') AND', $uservar);
		}

		public function close_or($condition, $uservar = null) {
			return $this->_where($condition, ') OR', $uservar);
		}

		function orderby($by, $asc = true) {
			if (!$asc) $by = $by . ' DESC';
			$this->orderby[] = $by;

		}

		function groupby($by) {
			$this->groupby[] = $by;

		}

		public function limit($start, $quantity) {
			$this->limit = array($start, $quantity);
			return $this;
		}

		public function exec() {
			return $this->_buildQuery();

		}

		protected function _buildQuery() {
			$sql = "SELECT ";
			$sql .= (empty($this->select)) ? '*' : implode(', ', $this->select);
			$sql .= " FROM " . implode(', ', $this->from);
			if (!empty($this->where)) $sql .= ' WHERE ' . implode(' ', $this->where);
			if (!empty($this->orderby)) $sql .= ' ORDER BY ' . implode(', ', $this->orderby);
			if (!empty($this->groupby)) $sql .= ' GROUP BY ' . implode(', ', $this->groupby);
			return $sql;
		}

	}
