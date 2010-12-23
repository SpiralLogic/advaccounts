<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 15/11/10
 * Time: 4:07 PM
 * To change this template use File | Settings | File Templates.
 */

abstract class Contact {


	// Current status of class, status as current state, process status error occurred, message for current state and if related variable if it applies
	protected $_status = Array('status' => true, 'process' => 'construct', 'message' => 'Customer Initialised', 'var');

	abstract protected function _canProcess();

	abstract function save($changes = null);

	abstract protected function _saveNew();

	abstract protected function _new();

	abstract protected function delete();

	abstract protected function _countTransactions();

	protected function _status($status = null, $process = null, $message = '', $var = null) {
		if ($status === null || $process === null) {
			$this->_status['status'] = false;
			$this->_status['process'] = 'status';
			$this->_status['message'] = 'Not enough parameters passed for status update.';
		} else {
			$this->_status['status'] = $status;
			$this->_status['process'] = $process;
			$this->_status['message'] = $message;
			if (!empty($var) && $var != null) {
				$this->_status['var'] = $var;
			}
		}
		return $this->_status;
	}

	public function getStatus() {
		return $this->_status;
	}

	public function setFromArray($changes = NULL) {
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
