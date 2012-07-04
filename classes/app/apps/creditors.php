<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/namespace ADV\App\Apps;

  class Creditors extends \Application
  {

    public $name = 'Creditors';

    public $help_context = "&Suppliers";
    function buildMenu() {
      $this->direct = '/contacts/suppliers';
    }
  }
