<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 7/01/11
	 * Time: 4:21 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class status {
		/**
		 * @var array
		 */
		protected $_status = array();
		protected $_errors = array();
		public function __construct($status = null, $process = null, $message = '', $var = null) {

			$this->set($status, $process, $message, $var);
		}

		/**
		 * @param null	 $status
		 * @param null	 $process
		 * @param string $message
		 * @param null	 $var
		 *
		 * @return array
		 */
		public function set($status = null, $process = null, $message = '', $var = null) {
			if ($status === null || $process === null) {

				$newstatus['status'] = false;
				$newstatus['process'] = 'status';
				$newstatus['message'] = 'Not enough parameters passed for status update.';
			} else {
				$newstatus['status'] = $status;
				$newstatus['process'] = $process;
				$newstatus['message'] = $message;
				if (!empty($var) && $var != null) {
					$newstatus['var'] = $var;
				}
			}
			$this->_status[] = $newstatus;
			if (!$status) $this->_errors[] = $newstatus;
			return $status;
		}

		public function append(array $status) {
			$this->_status[] = $status;
			return $status['status'];
		}

		/**
		 * @return array
		 */
		public function get() {
			if (!empty($this->_errors)) return end($this->_errors);
			if (!empty($this->_status)) return end($this->_status);
			return false;
		}
		public function hasError() {
					if (!empty($this->_errors)) return end($this->_errors);
					return false;
				}

		public function getAll() {
			return $this->_status;
		}
	}