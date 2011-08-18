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

	 public function select($column = null, $orderby = false, $groupby = false) {
		if (is_array($column)) foreach ($column as $c) call_user_func_array(array($this, 'select'), (array)$c);
		else {
		  $order = explode('as', $column);
		  if ($column) $this->select[] = $column;
		  if ($orderby) $this->orderby($order[0], ($orderby != 'desc') ? false : true);
		  if ($groupby) $this->groupby($order[0]);
		}
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

	 public function limit($start, $quantity = null) {
		$this->limit = ($quantity == null) ? $start : "$start, $quantity";
		return $this;
	 }

	 public function union() {
		$this->union[] = '(' . $this->_buildQuery() . ')';
		$this->select = $this->from = $this->orderby = $this->groupby = array();
		$this->limit = '';
		return $this;
	 }

	 public function exec($data) {
		if ($this->union) return implode(' UNION ', $this->union);
		return $this->_buildQuery();
	 }

	 protected function _buildQuery() {

		$sql = "SELECT ";
		$sql .= (empty($this->select)) ? '*' : implode(', ', $this->select);
		$sql .= " FROM " . implode(', ', $this->from);
		$sql .= parent::_buildQuery();

		if (!empty($this->orderby)) $sql .= ' ORDER BY ' . implode(', ', $this->orderby);
		if (!empty($this->groupby)) $sql .= ' GROUP BY ' . implode(', ', $this->groupby);
		if (!empty($this->limit)) $sql .= ' LIMIT ' . $this->limit;
		return $sql;
	 }

  }

