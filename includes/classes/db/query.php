<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Complex
 * Date: 14/08/11
 * Time: 11:13 PM
 * To change this template use File | Settings | File Templates.
 */

	abstract class Query {

		protected $insert = array();
		protected $where = array();
		public $data = array();
		protected $count = 0;

		protected function _where($condition, $type = 'AND', $uservar) {
			if (is_array($condition)) {
				foreach ($condition as $condition => $uservar) $this->_where($condition, $type, $uservar);
				return $this;
			}
			if ($uservar !== null) {
				$name = ':dbcondition' . $this->count;
				$this->count++;
				$this->data[$name] = $uservar;
				$condition = $condition . ' ' . $name;
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

		abstract public function exec($data);

		protected function _buildQuery() {
			$sql = '';
			if (!empty($this->where)) $sql .= ' WHERE ' . implode(' ', $this->where);
			$this->where=array();
			return $sql;
		}

	}
