<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Complex
 * Date: 21/08/11
 * Time: 11:24 PM
 * To change this template use File | Settings | File Templates.
 */

	class Result implements \Countable, \Iterator {

		protected $_prepared;
		protected $_current_row;
		protected $_row_count;

		public function __construct($query, $db) {
			$this->_prepared = DBconnection::instance($db)->prepare($query->execute());
			$this->_prepared->execute($query->data);
			$this->rowCount();
			$this->fetch();
			switch ($query->type) {
				case DB::SELECT:
					break;
				case DB::INSERT:
					return DBconnection::instance($db)->lastInsertId();
					break;
				case DB::UPDATE:
					return DBconnection::instance($db)->lastInsertId();
			}

		}

		protected function fetch() {
			$this->_current_row = $this->_prepared->fetch();
			return $this->_current_row;
		}

		protected function rowCount() {
			$this->_row_count = $this->_prepared->rowCount();
		}

		public function execute($data = null) {
			$this->_prepared->execute((array)$data);
		}

		public function assoc() {
			$this->_prepared->setFetchMode(PDO::FETCH_ASSOC);
			return $this->_prepared->fetch();
		}

		public function all() {
			return $this->_prepared->fetchAll();
		}

		public function asClass($type) {
			$this->_prepared->setFetchMode(PDO::FETCH_CLASS, $type);
			return $this->_prepared->fetchAll();
		}

		public function intoClass($type) {
			$this->_prepared->setFetchMode(PDO::FETCH_INTO, $type);
			return $this->_prepared->fetchAll();
		}

		/**
		 * (PHP 5 &gt;= 5.1.0)<br/>
		 * Return the current element
		 * @link http://php.net/manual/en/iterator.current.php
		 * @return mixed Can return any type.
		 */
		public function current() {
			return $this->_current_row;
		}

		/**
		 * (PHP 5 &gt;= 5.1.0)<br/>
		 * Move forward to next element
		 * @link http://php.net/manual/en/iterator.next.php
		 * @return void Any returned value is ignored.
		 */
		public function next() {
			return $this->fetch();
		}

		/**
		 * (PHP 5 &gt;= 5.1.0)<br/>
		 * Return the key of the current element
		 * @link http://php.net/manual/en/iterator.key.php
		 * @return scalar scalar on success, integer
		 * 0 on failure.
		 */
		public function key() {
			return $this->_current_row;
		}

		/**
		 * (PHP 5 &gt;= 5.1.0)<br/>
		 * Checks if current position is valid
		 * @link http://php.net/manual/en/iterator.valid.php
		 * @return boolean The return value will be casted to boolean and then evaluated.
		 * Returns true on success or false on failure.
		 */
		public function valid() {
			// TODO: Implement valid() method.
		}

		/**
		 * (PHP 5 &gt;= 5.1.0)<br/>
		 * Rewind the Iterator to the first element
		 * @link http://php.net/manual/en/iterator.rewind.php
		 * @return void Any returned value is ignored.
		 */
		public function rewind() {
			// TODO: Implement rewind() method.
		}

		/**
		 * (PHP 5 &gt;= 5.1.0)<br/>
		 * Count elements of an object
		 * @link http://php.net/manual/en/countable.count.php
		 * @return int The custom count as an integer.
		 * </p>
		 * <p>
		 * The return value is cast to an integer.
		 */
		public function count() {
			return $this->_row_count;
		}
	}
