<?php

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  /**

   */
  class Apps_Sales extends Application
  {
    /**

     */
    public function __construct()
    {
      global $installed_extensions;
      parent::__construct('Sales', _($this->help_context = '&Sales'));
      $this->add_module(_('Quotations and Orders'));
      $this->add_lapp_function(0, '', '');
      $this->add_lapp_function(0, _('New Sales &Quotation'), '/sales/sales_order_entry?' . Orders::ADD . '=0&' . Orders::TYPE . '=' . ST_SALESQUOTE, SA_SALESQUOTE);
      $this->add_lapp_function(0, _('Search Quotatio&ns'), '/sales/inquiry/sales_orders_view?' . Orders::TYPE . '=32', SA_SALESTRANSVIEW);
      $this->add_lapp_function(0, '', '');
      $this->add_rapp_function(0, _('New Sales &Order'), '/sales/sales_order_entry?' . Orders::ADD . '=0&' . Orders::TYPE . '=' . ST_SALESORDER, SA_SALESORDER);
      $this->add_rapp_function(0, _('Search  S&ales Orders'), '/sales/inquiry/sales_orders_view?' . Orders::TYPE . '=' . ST_SALESORDER, SA_SALESTRANSVIEW);
      $this->add_module(_('Deliveries and Invoices'));
      $this->add_lapp_function(1, _('New D&irect Delivery'), '/sales/sales_order_entry?' . Orders::ADD . '=0&' . Orders::TYPE . '=' . ST_CUSTDELIVERY, SA_SALESDELIVERY);
      $this->add_lapp_function(1, _('&Delivery Against Sales Orders'), '/sales/inquiry/sales_orders_view?OutstandingOnly=1', SA_SALESDELIVERY);
      $this->add_lapp_function(1, _('&Invoice Against Delivery'), '/sales/inquiry/sales_deliveries_view?OutstandingOnly=1', SA_SALESINVOICE);
      $this->add_lapp_function(1, _('&Template Delivery'), '/sales/inquiry/sales_orders_view?DeliveryTemplates=Yes', SA_SALESDELIVERY);
      $this->add_rapp_function(1, '', '');
      $this->add_rapp_function(1, _('New Direct &Invoice'), '/sales/sales_order_entry?' . Orders::ADD . '=0&' . Orders::TYPE . '=' . ST_SALESINVOICE, SA_SALESINVOICE);
      $this->add_rapp_function(1, _('Search Invoices and D&eliveries'), '/sales/inquiry/customer_inquiry?', SA_SALESTRANSVIEW);
      $this->add_rapp_function(1, _('New Invoice from &Template '), '/sales/inquiry/sales_orders_view?InvoiceTemplates=Yes', SA_SALESINVOICE);
      $this->add_rapp_function(1, _('Manage Recurrent &Invoices'), '/sales/manage/recurrent_invoices?', SA_SRECURRENT);
      $this->add_rapp_function(1, _('&Create and Print Recurrent Invoices'), '/sales/create_recurrent_invoices?', SA_SALESINVOICE);
      $this->add_rapp_function(1, '', '');
      $this->add_module(_('Payments and Customers'));
      $this->add_lapp_function(2, '', '');
      $this->add_lapp_function(2, _('New Customer &Payment'), '/sales/customer_payments?', SA_SALESPAYMNT);
      $this->add_lapp_function(2, _('New Customer &Refund'), '/sales/customer_refunds?', SA_SALESREFUND);
      $this->add_lapp_function(2, _('New Customer &Credit'), '/sales/credit_note_entry?NewCredit=Yes', SA_SALESCREDIT);
      $this->add_lapp_function(2, _('&Allocate Customer Payments or Credits'), '/sales/allocations/customer_allocation_main?', SA_SALESALLOC);
      $this->add_lapp_function(2, _('Search Customer A&llocations'), '/sales/inquiry/customer_allocation_inquiry?', SA_SALESALLOC);
      $this->add_lapp_function(2, '', '');
      $this->add_rapp_function(2, _('Add and Manage &Customers'), '/contacts/customers', SA_CUSTOMER);
      $this->add_rapp_function(2, _('Customer and Sales &Reports'), 'reporting/reports_main?Class=0', SA_SALESTRANSVIEW);
      $this->add_rapp_function(2, _('Email Customer Statements'), '/sales/email_statements', SA_SALESTRANSVIEW);
      $this->add_module(_('Sales Setup'));
      $this->add_lapp_function(3, _('Credit &Statuses'), '/sales/manage/credit_status?', SA_CRSTATUS);
      $this->add_lapp_function(3, _('Sales T&ypes'), '/sales/manage/sales_types?', SA_SALESTYPES);
      $this->add_rapp_function(3, _('Sales &Persons'), '/sales/manage/sales_people?', SA_SALESMAN);
      $this->add_rapp_function(3, _('Sales &Areas'), '/sales/manage/sales_areas?', SA_SALESAREA);
      $this->add_rapp_function(3, _('Sales &Groups'), '/sales/manage/sales_groups?', SA_SALESGROUP);
      if (count($installed_extensions) > 0) {
        foreach ($installed_extensions as $mod) {
          if (@$mod['active'] && $mod['type'] == 'plugin' && $mod['tab'] == 'sales') {
            $this->add_rapp_function(2, $mod['title'], 'modules/' . $mod['path'] . '/' . $mod['filename'] . '?', isset($mod['access']) ?
                                        $mod['access'] : SA_OPEN);
          }
        }
      }
    }
  }

