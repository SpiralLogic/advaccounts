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

  use \ADV\App\Controller\Menu;
  use ADV\App\Orders;

  /**

   */
  class Sales extends Menu {
    public $name = "Sales";
    public $help_context = "&Sales";
    /**

     */
    public function buildMenu() {
      $module = $this->add_module('Quotations and Orders');
      $module->addLeftFunction('', '');
      $module->addLeftFunction(_('New Sales &Quotation'), '/sales/order?' . Orders::ADD . '=0&' . Orders::TYPE . '=' . ST_SALESQUOTE, SA_SALESQUOTE);
      $module->addLeftFunction(_('Search Quotatio&ns'), '/sales/search/orders?' . Orders::TYPE . '=' . ST_SALESQUOTE . '', SA_SALESTRANSVIEW);
      $module->addLeftFunction('', '');
      $module->addRightFunction(_('New Sales &Order'), '/sales/order?' . Orders::ADD . '=0&' . Orders::TYPE . '=' . ST_SALESORDER, SA_SALESORDER);
      $module->addRightFunction(_('Search  S&ales Orders'), '/sales/search/orders?' . Orders::TYPE . '=' . ST_SALESORDER, SA_SALESTRANSVIEW);
      $module = $this->add_module(_('Deliveries and Invoices'));
      $module->addLeftFunction(_('New D&irect Delivery'), '/sales/order?' . Orders::ADD . '=0&' . Orders::TYPE . '=' . ST_CUSTDELIVERY, SA_SALESDELIVERY);
      $module->addLeftFunction(_('&Delivery Against Sales Orders'), '/sales/search/orders?OutstandingOnly=1', SA_SALESDELIVERY);
      $module->addLeftFunction(_('&Invoice Against Delivery'), '/sales/search/deliveries?OutstandingOnly=1', SA_SALESINVOICE);
      $module->addLeftFunction(_('&Template Delivery'), '/sales/search/orders?DeliveryTemplates=Yes', SA_SALESDELIVERY);
      $module->addRightFunction('', '');
      $module->addRightFunction(_('New Direct &Invoice'), '/sales/order?' . Orders::ADD . '=0&' . Orders::TYPE . '=' . ST_SALESINVOICE, SA_SALESINVOICE);
      $module->addRightFunction(_('Search Invoices and D&eliveries'), '/sales/search/transactions?', SA_SALESTRANSVIEW);
      $module->addRightFunction(_('New Invoice from &Template '), '/sales/search/orders?InvoiceTemplates=Yes', SA_SALESINVOICE);
      $module->addRightFunction(_('Manage Recurrent &Invoices'), '/sales/manage/recurrent_invoices?', SA_SRECURRENT);
      $module->addRightFunction(_('&Create and Print Recurrent Invoices'), '/sales/create_recurrent_invoices?', SA_SALESINVOICE);
      $module->addRightFunction('', '');
      $module = $this->add_module(_('Payments and Customers'));
      $module->addLeftFunction('', '');
      $module->addLeftFunction(_('New Customer &Payment'), '/sales/payment?', SA_SALESPAYMNT);
      //  $module->addLeftFunction(_('New Customer &Refund'), '/sales/customer_refunds?', SA_SALESREFUND);
      $module->addLeftFunction(_('New Customer &Credit'), '/sales/credit?NewCredit=Yes', SA_SALESCREDIT);
      $module->addLeftFunction(_('&Allocate Customer Payments or Credits'), '/sales/allocations/customer_allocation_main?', SA_SALESALLOC);
      $module->addLeftFunction(_('Search Customer A&llocations'), '/sales/inquiry/customer_allocation_inquiry?', SA_SALESALLOC);
      $module->addLeftFunction('', '');
      $module->addRightFunction(_('Add and Manage &Customers'), '/contacts/manage/customers', SA_CUSTOMER);
      $module->addRightFunction(_('Customer and Sales &Reports'), 'reporting/reports_main?Class=0', SA_SALESTRANSVIEW);
      $module->addRightFunction(_('Email Customer Statements'), '/sales/email_statements', SA_SALESTRANSVIEW);
      $module = $this->add_module(_('Sales Setup'));
      $module->addLeftFunction(_('Credit &Statuses'), '/sales/manage/creditstatuses?', SA_CRSTATUS);
      $module->addLeftFunction(_('Sales T&ypes'), '/sales/manage/types?', SA_SALESTYPES);
      $module->addRightFunction(_('Sales &Persons'), '/sales/manage/people?', SA_SALESMAN);
      $module->addRightFunction(_('Sales &Areas'), '/sales/manage/areas?', SA_SALESAREA);
      $module->addRightFunction(_('Sales &Groups'), '/sales/manage/groups?', SA_SALESGROUP);
    }
  }