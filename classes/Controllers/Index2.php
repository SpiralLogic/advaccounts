<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @date      22/09/12
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Controllers;

  use ADV\App\Controller\Action;

  /** **/
  class Index2 extends Action
  {
    public $name = "Banking";
    public $help_context = "&Banking";
    /**

     */
    protected function index() {
      echo   $this->embed('Items\\Manage\\Prices');
      $this->embed('Items\\Manage\\Purchasing');
    }
  }
