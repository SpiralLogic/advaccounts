<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 8/01/11
	 * Time: 2:53 AM
	 * To change this template use File | Settings | File Templates.
	 */
	abstract class DB_abstract
	{
		public $id = 0;
		protected $_table;
		protected $_id_column;
		/***
		 * @var Status
		 */
		protected $_status = null;

		abstract protected function delete();

		abstract protected function _canProcess();

		abstract protected function _defaults();

		abstract protected function _new();

		public function __construct($id = null) {
			if (is_numeric($id)) {
				$this->id = $id;
			}
			if (!$id || empty($id)) {
				$this->_new();
				return $this->_status(true, 'initalise', 'Created new ' . get_class($this) . "!");
			} elseif (is_array($id)) {
				$this->_defaults();
				$this->setFromArray($id);
				if ($this->id) {
					$this->_read($this->id);
				}
				return $this->_status(true, 'initalise', get_class($this) . " read from Database and changes applied!");
			}
			$this->_read($this->id);
			return $this->_status(true, 'initalise', get_class($this) . " read from database!");
		}

		/**
		 * @return array
		 */
		public function getStatus() {
			return $this->_status->get();
		}

		public function save($changes = null) {
			if (is_array($changes)) {
				$this->setFromArray($changes);
			}
			if (!$this->_canProcess()) {
				return false;
			}
			if ($this->id == 0) {
				return $this->_saveNew();
			} else {
				$data = (array)$this;
				DB::update($this->_table)->values($data)->where($this->_id_column . '=', $this->id)->exec();
				if (property_exists($this, 'inactive')) {
					DB::update_record_status($this->id, $this->inactive, $this->_table, $this->_id_column);
				}
				return $this->_status(true, 'write', get_class($this) . " has been updated!");
			}
		}

		protected function _read($id = false) {
			if ($id === false) {
				return $this->_status(false, 'read', 'No ' . get_class($this), ' ID to read');
			}
			$this->_defaults();
			DB::select()->from($this->_table)->where($this->_id_column . '=', $id);
			DB::fetch()->intoClass($this);
			return $this->_status(true, 'read', 'Successfully read ' . get_class($this), $id);
		}

		protected function _saveNew() {
			DB::begin_transaction();
			$result = DB::insert($this->_table)->values((array)$this)->exec();
			if ($result && property_exists($this, 'inactive')) {
				$result = DB::insert_record_status($this->id, $this->inactive, $this->_table, $this->_id_column);
			}
			DB::commit_transaction();
			if ($result === false) {
				return $this->_status(false, 'write', 'Could not add to databse: ' . get_class($this));
			} else {
				$this->id = $result;
				return $this->_status(true, 'write', 'Added to databse: ' . get_class($this));
			}
		}

		protected function setFromArray($changes = NULL) {
			if (!is_array($changes) || count($changes) == 0) {
				return $this->_status(false, 'setFromArray', 'Variable array was either not passed, empty or is not an array');
			}
			$remainder = array();
			foreach ($changes as $key => $value) {
				if (!is_array($value)) {
					$value = (trim($value) == null) ? '' : trim($value);
				}
				(property_exists($this, $key)) ? $this->$key = $value : $remainder[$key] = $value;
			}
			return $remainder;
		}

		protected function _status($status = null, $process = null, $message = '', $var = null) {
			if (!$this->_status) {
				return $this->_status = new Status($status, $process, $message, $var);
			}
			return $this->_status->set($status, $process, $message, $var);
		}
	}

