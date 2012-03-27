<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/


  class Status {

    /**
     * @var array
     */
    protected $_status = array();
    /**
     * @var array
     */
    protected $_errors = array();
    const SUCCESS = E_SUCCESS;
    const INFO = E_USER_NOTICE;
    const WARNING = E_USER_WARNING;
    const ERROR = E_USER_ERROR;
    /**
     * @param null   $status
     * @param null   $process
     * @param string $message
     * @param null   $var
     */
    public function __construct($status = NULL, $process = NULL, $message = '', $var = NULL) {
      $this->set($status, $process, $message, $var);
    }
    /**
     * @param null   $status
     * @param null   $process
     * @param string $message
     * @param null   $var
     *
     * @return array
     */
    public function set($status = NULL, $process = NULL, $message = '', $var = NULL) {
      if ($status === TRUE) {
        $status = self::INFO;
      }
      if ($status === FALSE) {
        $status = self::ERROR;
      }
      if ($status === NULL || $process === NULL) {
        $newstatus['status'] = self::ERROR;
        $newstatus['process'] = 'status';
        $newstatus['message'] = 'Not enough parameters passed for status update.';
      }
      else {
        $newstatus['status'] = $status;
        $newstatus['process'] = $process;
        $newstatus['message'] = $message;
        if (!empty($var) && $var != NULL) {
          $newstatus['var'] = $var;
        }
      }
      $this->_status[] = $newstatus;
      if ($status == self::ERROR) {
        $this->_errors[] = $newstatus;
      }
      return !($status == self::ERROR);
    }
    /**
     * @param array $status
     *
     * @return mixed
     */
    public function append(array $status, $error_only = TRUE) {
      if ($error_only && $status['status'] != self::ERROR) {
        return TRUE;
      }
      $this->_status[] = $status;
      return FALSE;
    }
    /**
     * @return array
     */
    public function get() {
      if (!empty($this->_errors)) {
        return end($this->_errors);
      }
      if (!empty($this->_status)) {
        return end($this->_status);
      }
      return FALSE;
    }
    /**
     * @return bool|mixed
     */
    public function hasError() {
      if (!empty($this->_errors)) {
        return end($this->_errors);
      }
      return FALSE;
    }
    /**
     * @return array
     */
    public function getAll() {
      return $this->_status;
    }
    /**
     * @return string
     */
    public function __toString() {
      $last = $this->get();
      $str = ucwords($last['process']);
      $str .= ($last['status'] != self::ERROR) ? ' Succeeded: ' : ' Failed: ';
      $str .= $last['message'];
      return $str;
    }
  }
