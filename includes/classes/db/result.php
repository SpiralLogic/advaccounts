<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Complex
 * Date: 21/08/11
 * Time: 11:24 PM
 * To change this template use File | Settings | File Templates.
 */

	class Result {

		protected $_prepared;

		public function __construct($query, $name) {
			$this->_prepared = DBconnection::instance($name)->prepare($query->execute());
			$this->_prepared->execute($query->data);
			switch ($query->type) {
				case DB::SELECT:
					return $this;
					break;
				case DB::INSERT:
					return DBconnection::instance($name)->lastInsertId();
					break;
				case DB::UPDATE:
					return DBconnection::instance($name)->lastInsertId();
			}

		}

		public function fetch() {
			return $this->_prepared->fetch();
		}

		public function rowCount() {
			return $this->_prepared->rowCount();
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

	}
