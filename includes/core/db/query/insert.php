<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 15/08/11
	 * Time: 5:46 AM
	 *
	 */
	Class DB_Query_Insert extends DB_Query {
		/**
		 * @var
		 */
		protected $table;
		/**
		 * @var array
		 */
		protected $values = array();
		/**
		 * @var array
		 */
		protected $feilds = array();
		/**
		 * @var array
		 */
		protected $hasFeilds = array();
		/**
		 * @var array
		 */
		public $data = array();

		/**
		 * @param bool $table
		 * @param $db
		 */
		public function __construct($table = false, $db) {
			parent::__construct($db);
			if ($table) {
				$this->into($table);
			}
			$this->type = DB::INSERT;
			$query = DB::query('SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = ' . DB::quote($table), false);
			while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
				$this->hasFeilds[] = $row['COLUMN_NAME'];
			}
			return $this;
		}

		/**
		 * @param $table
		 *
		 * @return DB_Query_Insert
		 */
		public function into($table) {
			$this->table = $table;
			return $this;
		}

		/**
		 * @param $values array key pair
		 *
		 * @return DB_Query_Insert|DB_Query_Update
		 */
		public function values($values) {
			$this->data = (array)$values + $this->data;

			return $this;
		}

		/**
		 * @param $feild
		 * @param $value
		 * @return DB_Query_Insert
		 * @throws Adv_Exception
		 */
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

		/**
		 * @param null $data
		 * @return string
		 */
		protected function execute($data = null) {
			if ($this->data !== null) {
				$this->values((array)$data);
			}
			$this->data = array_intersect_key($this->data, array_flip($this->hasFeilds));

			$this->feilds = array_keys($this->data);

			return $this->_buildQuery();
		}

		/**
		 * @return string
		 */
		protected function _buildQuery() {
			$sql = "INSERT INTO " . $this->table . " (";
			$sql .= implode(', ', $this->feilds) . ") VALUES (";
			$sql .= ':' . implode(', :', str_replace('-', '_', $this->feilds));
			$sql .= ') ';
			return $sql;
		}
	}
