<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 8/01/11
	 * Time: 2:53 AM
	 * To change this template use File | Settings | File Templates.
	 */
	abstract class DB_abstract {

		abstract function save($changes = null);

		abstract protected function delete();

		abstract protected function _canProcess();

		abstract protected function _defaults();

		abstract protected function _new();

		abstract protected function _read();

		abstract protected function _saveNew();
/**
 * @var Status
 */
		protected $_status = null;

		public function getStatus() {
			return $this->_status->get();
		}

		protected function __construct($id = 0) {
			if (!$id || empty($id)) {
				$this->_new();
				return $this->_status(true, 'initalise', 'Created new ' . get_class($this) . "!");
			} elseif (is_array($id)) {
				$this->_defaults();
				if (isset($id['id'])) $this->_read($id['id']);
				$this->setFromArray($id);
				return $this->_status(true, 'initalise', get_class($this) . " details contructed!");
			}
			$this->_read($id);
			return $this->_status(true, 'initalise', get_class($this) . " details loaded from DB!");
		}

		protected function escape($value) {

			return DB::escape($value);
		}

		protected function setFromArray($changes = NULL) {
			if (!is_array($changes) || count($changes) == 0) {
				$this->_status(false, 'setFromArray', 'Variable array was either not passed, empty or is not an array');
				return false;
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
				$this->_status = new Status();
			}
			$this->_status->set($status, $process, $message, $var);
		}
	}

