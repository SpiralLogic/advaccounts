<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  return array(
    'active'  => array(
      'Sales'        => ['enabled'=> true], //
      'Debtors'      => ['name'=> 'Customers', 'enabled'=> true, 'direct'=> '/contacts/manage/customers'], //
      'Purchases'    => ['enabled'=> true], //
      'Creditors'    => ['name'=> 'Suppliers', 'enabled'=> true, 'direct'=> '/contacts/manage/suppliers'], //
      'Inventory'    => ['enabled'=> true], //
      'Manufacturing'=> ['enabled'=> true], //
      'GL'           => ['name'=> 'Banking', 'enabled'=> true], //
      'Advanced'     => ['enabled'=> true], //
      'System'       => ['enabled'=> true]
    ), //
    'default' => 'Sales'
  );
