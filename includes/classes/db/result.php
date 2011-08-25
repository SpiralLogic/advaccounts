<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Complex
 * Date: 21/08/11
 * Time: 11:24 PM
 * To change this template use File | Settings | File Templates.
 */

	class Result implements \Countable, \Iterator {

		public $prepared;
		protected $current;
		protected $count;
		protected $cursor = -1;
		protected $query;
		protected $valid;
		protected $as_object = false;

		public function __construct($query, $db) {
			$this->query = $query;
			$this->prepared = DBconnection::instance($db)->prepare($query->getQuery());

			$this->prepared->setFetchMode(PDO::FETCH_ASSOC);
			$this->rewind();
		}

		protected function execute() {
			$this->cursor = 0;
			$this->valid = $this->prepared->execute($this->query->data);
			$this->count = $this->prepared->rowCount();
		}

		public function all() {
			$this->execute();
			return $this->prepared->fetchAll();
		}

		public function asClassLate($class) {
			$this->prepared->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE,  $class);
			return $this;
		}

		public function asClass($class) {
			$this->prepared->setFetchMode(PDO::FETCH_CLASS, $class);
			return $this;
		}

		public function intoClass($object) {
			$this->prepared->setFetchMode(PDO::FETCH_INTO, $object);
			$this->execute();
			 $this->prepared->fetch();
		$this->prepared=null;
			$this->query=null;
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
	}
