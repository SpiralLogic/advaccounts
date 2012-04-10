<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   adv.accounts.app
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  class Apps_Debtors extends Application {
    /**
     *
     */
    function __construct() {
      $this->direct = 'contacts/customers.php';
      parent::__construct("Debtors", _($this->help_context = "&Customers"));
    }
  }
