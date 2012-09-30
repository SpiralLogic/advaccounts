<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.core
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core;

  /**

   */
  class Status {
    /**
     * @var array
     */
    protected $status = [];
    /**
     * @var array
     */
    protected $errors = [];
    const SUCCESS = E_SUCCESS;
    const INFO    = E_USER_NOTICE;
    const WARNING = E_USER_WARNING;
    const ERROR   = E_USER_ERROR;
    /**
     * @param null   $status
     * @param string $message
     * @param null   $var
     *
     * @internal param null $process
     */
    public function __construct($status = null, $message = '', $var = null) {
      if (func_num_args() > 0 && $message) {
        $this->set($status, $message, $var);
      }
    }
    /**
     * Adds new status entry
     *
     * @param int|null      $status  One of Status::SUCCESS | Status::INFO | Status::WARNING | Status::ERROR
     * @param string        $message Friendly message to display
     * @param mixed         $var     The variable if any invovled in causeing the status
     *
     * @internal param string $process What process caused the change in status
     * @return bool
     */
    public function set($status = self::INFO, $message = '', $var = null) {
      if ($status === true) {
        $status = self::INFO;
      }
      if ($status === false) {
        $status = self::ERROR;
      }
      if ($status === null) {
        $newstatus['status']  = self::ERROR;
        $newstatus['message'] = 'Not enough parameters passed for status update.';
      } else {
        $newstatus['status']  = $status;
        $newstatus['message'] = $message;
        if (!empty($var) && $var != null) {
          $newstatus['var'] = $var;
        }
      }
      $this->status[] = $newstatus;
      if ($status === self::ERROR) {
        $this->errors[] = $newstatus;
      }

      return !($status == self::ERROR);
    }
    /**
     * @return array
     */
    public function get() {
      if (!empty($this->errors)) {
        return end($this->errors);
      }
      if (!empty($this->status)) {
        return end($this->status);
      }

      return [];
    }
    /**
     * @return array
     */
    public function getAll() {
      return $this->status;
    }
    /**
     * @param array $status
     * @param bool  $error_only
     *
     * @return mixed
     */
    public function append(array $status, $error_only = true) {
      if ($error_only && $status['status'] != self::ERROR) {
        return true;
      }
      $this->status[] = $status;

      return false;
    }
    /**
     * @return bool|mixed
     */
    public function hasError() {
      if (!empty($this->errors)) {
        return end($this->errors);
      }

      return false;
    }
    /**
     * @return string
     */
    public function __toString() {
      $last = $this->get();
      $str  = ($last['status'] != self::ERROR) ? ' Succeeded: ' : ' Failed: ';
      $str .= $last['message'];

      return $str;
    }
  }
