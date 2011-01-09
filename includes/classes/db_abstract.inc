<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Complex
 * Date: 8/01/11
 * Time: 2:53 AM
 * To change this template use File | Settings | File Templates.
 * @property mixed _status
 */
abstract class DB_abstract {


	abstract protected function _defaults();

	abstract protected function _new();

	abstract protected function _read();

	abstract protected function _canProcess();

	abstract protected function _saveNew();

	abstract public function save($changes = null);

	abstract public function delete();

	abstract protected function _countTransactions();
	/**
	 * @param  $status
	 * @param  $process
	 * @param string $message
	 * @param  $var
	 * @return void
	 */
	protected function _status($status, $process, $message = '', $var = null) {
		if (!$this->_status) {
			$this->_status = new Status();
		}
		$this->_status->set($status, $process, $message, $var);
	}

	protected function __construct($id = false, $changes = array()) {
		if (is_array($id) && count($changes) == 0) {
			$changes = $id;
			$id = false;
		}
		if (!$id) {
			$this->_new();
			$this->_status(true, 'initalise', 'Created new ' . get_class($this) . "!");
		} else {
			$this->_read($id);
			$this->_status(true, 'initalise', get_class($this) . " details loaded from DB!");
		}
		if (count($changes) > 0) {
			$this->setFromArray($changes);
			$this->_status(true, 'initalise', get_class($this) . " details contructed!");
		}

	}

	protected function setFromArray($changes = NULL) {
		if ($changes == null || empty($changes) || !is_array($changes)) {
			$this->_status(false, 'setFromArray', 'Variable array was either not passed, empty or is not an array');
			return false;
		}
		$remainder = array();
		foreach ($changes as $key => $value) {
			(property_exists($this, $key)) ? $this->$key = $value : $remainder[$key] = $value;
		}
		return $remainder;
	}
}
