<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  class Purch_GLCode {

    public $counter;
    public $gl_code;
    public $gl_act_name;
    public $gl_dim;
    public $gl_dim2;
    public $amount;
    public $memo_;
    /**
     * @param $counter
     * @param $gl_code
     * @param $gl_act_name
     * @param $gl_dim
     * @param $gl_dim2
     * @param $amount
     * @param $memo_
     */
    public function __construct($counter, $gl_code, $gl_act_name, $gl_dim, $gl_dim2, $amount, $memo_) {
      /* Constructor function to add a new gl_codes object with passed params */
      $this->counter = $counter;
      $this->gl_code = $gl_code;
      $this->gl_act_name = $gl_act_name;
      $this->gl_dim = $gl_dim;
      $this->gl_dim2 = $gl_dim2;
      $this->amount = $amount;
      $this->memo_ = $memo_;
    }
  }
