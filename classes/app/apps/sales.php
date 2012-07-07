<?php

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App\Apps;
  /**

   */
  use Orders;
  use ADV\App\Application;

  class Sales extends Application {
    public $name = 'Sales';
    public $help_context = '&Sales';
    /**

     */
    public function buildMenu() {
      $this->add_module(_('Quotations and Orders'));
      $this->addLeftFunction(0, '', '');
      $this->addLeftFunction(0, _('New Sales &Quotation'), '/sales/sales_order_entry?' . Orders::ADD . '=0&' . Orders::TYPE . '=' . ST_SALESQUOTE, SA_SALESQUOTE);
      $this->addLeftFunction(0, _('Search Quotatio&ns'), '/sales/inquiry/sales_orders_view?' . Orders::TYPE . '=' . ST_SALESQUOTE . '', SA_SALESTRANSVIEW);
      $this->addLeftFunction(0, '', '');
      $this->addRightFunction(0, _('New Sales &Order'), '/sales/sales_order_entry?' . Orders::ADD . '=0&' . Orders::TYPE . '=' . ST_SALESORDER, SA_SALESORDER);
      $this->addRightFunction(0, _('Search  S&ales Orders'), '/sales/inquiry/sales_orders_view?' . Orders::TYPE . '=' . ST_SALESORDER, SA_SALESTRANSVIEW);
      $this->add_module(_('Deliveries and Invoices'));
      $this->addLeftFunction(1, _('New D&irect Delivery'), '/sales/sales_order_entry?' . Orders::ADD . '=0&' . Orders::TYPE . '=' . ST_CUSTDELIVERY, SA_SALESDELIVERY);
      $this->addLeftFunction(1, _('&Delivery Against Sales Orders'), '/sales/inquiry/sales_orders_view?OutstandingOnly=1', SA_SALESDELIVERY);
      $this->addLeftFunction(1, _('&Invoice Against Delivery'), '/sales/inquiry/sales_deliveries_view?OutstandingOnly=1', SA_SALESINVOICE);
      $this->addLeftFunction(1, _('&Template Delivery'), '/sales/inquiry/sales_orders_view?DeliveryTemplates=Yes', SA_SALESDELIVERY);
      $this->addRightFunction(1, '', '');
      $this->addRightFunction(1, _('New Direct &Invoice'), '/sales/sales_order_entry?' . Orders::ADD . '=0&' . Orders::TYPE . '=' . ST_SALESINVOICE, SA_SALESINVOICE);
      $this->addRightFunction(1, _('Search Invoices and D&eliveries'), '/sales/inquiry/customer_inquiry?', SA_SALESTRANSVIEW);
      $this->addRightFunction(1, _('New Invoice from &Template '), '/sales/inquiry/sales_orders_view?InvoiceTemplates=Yes', SA_SALESINVOICE);
      $this->addRightFunction(1, _('Manage Recurrent &Invoices'), '/sales/manage/recurrent_invoices?', SA_SRECURRENT);
      $this->addRightFunction(1, _('&Create and Print Recurrent Invoices'), '/sales/create_recurrent_invoices?', SA_SALESINVOICE);
      $this->addRightFunction(1, '', '');
      $this->add_module(_('Payments and Customers'));
      $this->addLeftFunction(2, '', '');
      $this->addLeftFunction(2, _('New Customer &Payment'), '/sales/customer_payments?', SA_SALESPAYMNT);
      $this->addLeftFunction(2, _('New Customer &Refund'), '/sales/customer_refunds?', SA_SALESREFUND);
      $this->addLeftFunction(2, _('New Customer &Credit'), '/sales/credit_note_entry?NewCredit=Yes', SA_SALESCREDIT);
      $this->addLeftFunction(2, _('&Allocate Customer Payments or Credits'), '/sales/allocations/customer_allocation_main?', SA_SALESALLOC);
      $this->addLeftFunction(2, _('Search Customer A&llocations'), '/sales/inquiry/customer_allocation_inquiry?', SA_SALESALLOC);
      $this->addLeftFunction(2, '', '');
      $this->addRightFunction(2, _('Add and Manage &Customers'), '/contacts/customers', SA_CUSTOMER);
      $this->addRightFunction(2, _('Customer and Sales &Reports'), 'reporting/reports_main?Class=0', SA_SALESTRANSVIEW);
      $this->addRightFunction(2, _('Email Customer Statements'), '/sales/email_statements', SA_SALESTRANSVIEW);
      $this->add_module(_('Sales Setup'));
      $this->addLeftFunction(3, _('Credit &Statuses'), '/sales/manage/credit_status?', SA_CRSTATUS);
      $this->addLeftFunction(3, _('Sales T&ypes'), '/sales/manage/sales_types?', SA_SALESTYPES);
      $this->addRightFunction(3, _('Sales &Persons'), '/sales/manage/sales_people?', SA_SALESMAN);
      $this->addRightFunction(3, _('Sales &Areas'), '/sales/manage/sales_areas?', SA_SALESAREA);
      $this->addRightFunction(3, _('Sales &Groups'), '/sales/manage/sales_groups?', SA_SALESGROUP);
    }
  }

