<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Complex
 * Date: 14/08/11
 * Time: 11:13 PM
 * To change this template use File | Settings | File Templates.
 */

	abstract class Where {

		public $data = array();
		protected $where = array();
		private $wheredata =array();
		protected $count = 0;

		protected function _where($conditions, $type = 'AND', $uservar=null) {
			if (is_array($conditions)) {
				foreach ($conditions as $condition) {
					if (is_array($condition)) {
						$this->_where($condition[0], $type, $condition[1]);
					} else {
						$this->_where($condition);
					}
				}
				return $this;
			}
			if ($uservar !== null) {
				$name = ':dbcondition' . $this->count;
				$this->count++;
				$this->wheredata[$name] = $uservar;
				$conditions = $conditions . ' ' . $name;
			}
			$this->where[] = (empty($this->where)) ? $conditions : $type . ' ' . $conditions;
			return $this;
		}

		public function where($condition, $uservar = null) {
			return $this->_where($condition, 'AND', $uservar);
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

		protected function _buildWhere() {
			$sql = '';
			if (!empty($this->where)) $sql .= ' WHERE ' . implode(' ', $this->where);
			$this->data = $this->data + $this->wheredata;
			return $sql;
		}
	}
