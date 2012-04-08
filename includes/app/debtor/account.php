<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  class Debtor_Account extends Debtor_Branch {

    public $accounts_id = 0;
    public $br_name = 'Accounts Department';
    public $branch_ref = 'accounts';
    /**
     * @param int|null $id
     */
    public function __construct($id = NULL) {
      $this->accounts_id = &$this->branch_id;
      $this->id = &$this->accounts_id;
      parent::__construct($id);
    }
    protected function _defaults() {
      parent::_defaults();
      $this->branch_ref = 'accounts';
      $this->br_name = 'Accounts Department';
    }
  }
