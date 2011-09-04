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
		protected $union = array();

		public function __construct($columns) {
			parent::__construct();
			$this->type = DB::SELECT;
			call_user_func_array(array($this, 'select'), $columns);
		}

		public function select($columns = null) {
			$columns = func_get_args();
			$this->select = array_merge($this->select, $columns);

			return $this;
		}

		public function from($tables = null) {
			$tables = func_get_args();
			$this->from = array_merge($this->from, $tables);
			return $this;
		}

		function orderby($by = null) {
			$by = func_get_args();
			$this->orderby = array_merge($this->orderby, $by);
			return $this;
		}

		function groupby($by = null) {
			$by = func_get_args();
			$this->groupby = array_merge($this->groupby, $by);
			return $this;
		}

		public function limit($start, $quantity = null) {
			$this->limit = ($quantity == null) ? $start : "$start, $quantity";
			return $this;
		}

		public function union() {
			$this->union[] = '(' . $this->_buildQuery(true) . ')';
			$this->select = $this->from = $this->orderby = $this->groupby = array();
			$this->limit = '';
			return $this;
		}

		public function execute() {

			return $this->_buildQuery();
		}

		protected function _buildQuery($isunion = false) {
			if (!$isunion && $this->union) {
				$this->select[] = 's.*';
				$this->from[] = '(' . implode(' UNION ', $this->union) . ') s ';
			}
			$sql = "SELECT ";
			$sql .= (empty($this->select)) ? '*' : implode(', ', $this->select);

			$sql .= " FROM " . implode(', ', $this->from);

			$sql .= parent::_buildWhere();
			if (!empty($this->groupby)) $sql .= ' GROUP BY ' . implode(', ', $this->groupby);
			if (!empty($this->orderby)) $sql .= ' ORDER BY ' . implode(', ', $this->orderby);
			if (!empty($this->limit)) $sql .= ' LIMIT ' . $this->limit;
			return $sql;
		}

	}

