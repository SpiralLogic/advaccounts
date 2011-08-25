<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Complex
 * Date: 15/08/11
 * Time: 5:46 AM
 *
 */

	Class Insert extends Query {

		protected $table;
		protected $values = array();
		protected $feilds = array();
		public $data = array();

		public function __construct($into = false) {
			if ($into) $this->into($into);
			parent::__construct(DB::INSERT);

			return $this;
		}

		public function into($table) {
			$this->table = $table;
			return $this;
		}

		public function values($values) {
			$this->data = (array)$values + $this->data;
			return $this;
		}

		public function value($feild, $value) {
			if (is_array($feild) && is_array($value)) {
				if (count($feild) != count($value)) {
					throw new Adv_Exception('Feild count and Value count unequal');
				} else {
					$this->values(array_combine($feild, $value));
				}
			} elseif (is_array($feild) && !is_array($value)) {
				$values = array_fill(0, count($feild), $value);
				$this->values(array_combine($feild, $values));
			} else {
				$this->values(array($feild => $value));
			}
			return $this;
		}

		public function execute() {
			$this->feilds = array_keys($this->data);
			return $this->_buildQuery();
		}

		protected
		function _buildQuery() {
			$sql = "INSERT INTO " . $this->table . " (";
			$sql .= implode(', ', $this->feilds) . ") VALUES (";
			$sql .= ':' . implode(', :', str_replace('-', '_', $this->feilds));
			$sql .= ') ';
			return $sql;
		}
	}