<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 21/08/11
	 * Time: 11:24 PM
	 * To change this template use File | Settings | File Templates.
	 */

	class DB_Result implements \Countable, \Iterator {

		public $prepared;
		protected $current;
		protected $count;
		protected $cursor = -1;
		protected $data;
		protected $valid;

		public function __construct($prepared, $data=null) {
			$this->data = $data;
			$this->prepared = $prepared;
			$this->prepared->setFetchMode(PDO::FETCH_ASSOC);
			$this->execute();
		}

		protected function execute() {
			try {
				$this->cursor = 0;
				$this->valid = $this->prepared->execute($this->data);
				$this->count = $this->prepared->rowCount();
			}
			catch (PDOException $e) {
			}
		}

		public function all() {
			$result = $this->prepared->fetchAll();
			$this->prepared = null;
			return $result;
		}

		public function one($column = null) {
			$result = $this->prepared->fetch();
			return ($column !== null && isset($result[$column])) ? $result[$column] : $result;
		}

		public function asClassLate($class, $contruct = array()) {
			$this->prepared->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $class, $contruct);
			return $this;
		}

		public function asClass($class, $contruct = array()) {
			$this->prepared->setFetchMode(PDO::FETCH_CLASS, $class, $contruct);
			return $this;
		}

		public function intoClass($object) {
			return $this->intoObject($object);
		}

		public function intoObject($object) {
			$this->prepared->setFetchMode(PDO::FETCH_INTO, $object);
			$this->prepared->fetch();
			$this->prepared = null;
		}

		public function asObject() {
			$this->prepared->setFetchMode(PDO::FETCH_OBJ);
			return $this;
		}

		/**
		 * (PHP 5 &gt;= 5.1.0)<br/>
		 * Return the current element
		 * @link http://php.net/manual/en/iterator.current.php
		 * @return mixed Can return any type.
		 */
		public function current() {
			return $this->current;
		}

		/**
		 * (PHP 5 &gt;= 5.1.0)<br/>
		 * Move forward to next element
		 * @link http://php.net/manual/en/iterator.next.php
		 * @return void Any returned value is ignored.
		 */
		public function next() {
			$this->current = $this->prepared->fetch();
			++$this->cursor;
		}

		/**
		 * (PHP 5 &gt;= 5.1.0)<br/>
		 * Return the key of the current element
		 * @link http://php.net/manual/en/iterator.key.php
		 * @return scalar scalar on success, integer
		 * 0 on failure.
		 */
		public function key() {
			return $this->cursor;
		}

		public function valid() {
			if (!$this->current) $this->valid = false;
			return $this->valid;
		}

		/**
		 * (PHP 5 &gt;= 5.1.0)<br/>
		 * Rewind the Iterator to the first element
		 * @link http://php.net/manual/en/iterator.rewind.php
		 * @return void Any returned value is ignored.
		 */
		public function rewind() {
			if ($this->cursor > -1) {
				$this->prepared->closeCursor();
			}
			$this->execute();
			$this->next();
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
			return $this->count;
		}

		public function __toString() {
			if ($this->cursor === 0) $this->next();
			return var_export($this->current(), true);
		}
	}
