<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Complex
 * Date: 15/08/11
 * Time: 5:46 AM
 *
 */

	Class Update extends Query {

		protected $columns = array();
		protected $table;
		protected $values = array();

		public function update($into) {
			$this->table = $into;
			return $this;
		}

		public function values($values) {
			$this->data = array_merge($this->data, (array)$values);
			return $this;
		}

		public function columns($columns) {
			$this->columns = array_merge($this->columns, (array)$columns);
			return $this;
		}

		public function data($data) {
			if (!is_array($data)) return;
			$this->columns = array_merge($this->columns, array_keys($data));
			$this->data = array_merge($this->data, array_values($data));
			return $this;
		}

		public function exec($data) {
			if (count($this->columns == 0) && is_object($data)) {
				$data = get_object_vars($data);
				$this->columns = array_keys($data);
			}
			if ($data !== null) $this->data = array_merge((array)$data, $this->data);
						return $this->_buildQuery();
		}

		protected function _buildQuery() {
			$sql = "UPDATE " . $this->table . " SET ";
			foreach ($this->columns as &$column) {
				$column = " $column = :$column ";
			}
			$sql .= implode(', ', $this->columns);
			$sql .= parent::_buildQuery();
			return $sql;
		}
	}