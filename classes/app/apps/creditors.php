<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Apps_Creditors extends Application
  {
    /**

     */
    public function __construct()
    {
      $this->direct = '/contacts/suppliers';
      parent::__construct("Creditors", _($this->help_context = "&Suppliers"));
    }
  }
