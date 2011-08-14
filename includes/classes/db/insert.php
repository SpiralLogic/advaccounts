<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Complex
 * Date: 15/08/11
 * Time: 5:46 AM
 *
 */

	Class Insert {

		protected $columns = array();
		protected $table;
		public $data=array();

		public function insert($into) {
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
			if (count($this->columns==0) && is_object($data)) {
				$data = get_object_vars($data);
				$this->columns=array_keys($data);
			}
			if ($data!==null) $this->data = array_merge((array)$data,$this->data);
			return $this->_buildQuery();
		}

		protected function _buildQuery() {
			$sql = "INSERT INTO " . $this->table . " (";
			$sql .= implode(', ', $this->columns) . ") VALUES (";
			$sql .= ':' . implode(', :', str_replace('-', '_', $this->columns));
			$sql .= ') ';
			var_dump($sql);
		}
	}