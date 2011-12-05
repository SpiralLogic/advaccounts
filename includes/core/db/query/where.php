<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 14/08/11
	 * Time: 11:13 PM
	 * To change this template use File | Settings | File Templates.
	 */
	abstract class DB_Query_Where
	{
		/**
		 * @var array
		 */
		public $data = array();
		/**
		 * @var array
		 */
		protected $where = array();
		/**
		 * @var array
		 */
		private $wheredata = array();
		/**
		 * @var int
		 */
		protected $count = 0;

		/***
		 * @param array $conditions
		 * @param string $type
		 * @param null $uservar
		 *
		 * @return DB_Query_Select
		 */
		protected function _where( $conditions, $type = 'AND', $uservar = null)
			{
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

		/**
		 * @param $condition
		 * @param null $uservar
		 *
		 * @return DB_Query_Select
		 */public function where($condition, $uservar = null)
			{
				return $this->_where($condition, 'AND', $uservar);
			}

		/**
		 * @param $condition
		 * @param null $uservar
		 * @return DB_Query_Select
		 */public function or_where($condition, $uservar = null)
			{
				return $this->_where($condition, 'OR', $uservar);
			}

		/**
		 * @param $condition
		 * @param null $uservar
		 * @return DB_Query_Select
		 */public function and_where($condition, $uservar = null)
			{
				return $this->_where($condition, 'AND', $uservar);
			}

		/**
		 * @param $condition
		 * @param null $uservar
		 * @return DB_Query_Select
		 */public function or_open($condition, $uservar = null)
			{
				return $this->_where($condition, 'OR (', $uservar);
			}

		/**
		 * @param $condition
		 * @param null $uservar
		 * @return DB_Query_Select
		 */public function and_open($condition, $uservar = null)
			{
				return $this->_where($condition, 'AND (', $uservar);
			}

		/**
		 * @param $condition
		 * @param null $uservar
		 * @return DB_Query_Select
		 */public function close_and($condition, $uservar = null)
			{
				return $this->_where($condition, ') AND', $uservar);
			}

		/**
		 * @param $condition
		 * @param null $uservar
		 * @return DB_Query_Select
		 */public function close_or($condition, $uservar = null)
			{
				return $this->_where($condition, ') OR', $uservar);
			}

		/**
		 * @param $condition
		 * @param null $uservar
		 * @return DB_Query_Select
		 */public function open($condition, $uservar = null)
			{
				if (empty($this->where)) {
					$condition = '(' . $condition;
				}
				return $this->_where($condition, ' AND ', $uservar);
			}

		/**
		 * @return DB_Query_Where
		 */public function close()
			{
				array_push($this->where, array_pop($this->where) . ') ');
				return $this;
			}

		/**
		 * @return string
		 */protected function _buildWhere()
			{
				$sql = '';
				if (!empty($this->where)) {
					$sql .= ' WHERE ' . implode(' ', $this->where);
				}
				$this->data = $this->data + $this->wheredata;
				return $sql;
			}
	}
