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
   *
   */
  class Apps_Sales extends Application {
    /**
     *
     */
    function __construct() {
      global $installed_extensions;
      parent::__construct("Sales", _($this->help_context = "&Sales"));

      $this->add_module(_("Quotations and Orders"));
      $this->add_lapp_function(0, '', '');
      $this->add_lapp_function(0, _("Sales &Quotation Entry"),
        "sales/sales_order_entry.php?add=0&type=32", SA_SALESQUOTE);
      $this->add_lapp_function(0, _("Sales Quotation I&nquiry"),
        "sales/inquiry/sales_orders_view.php?type=32", SA_SALESTRANSVIEW);
      $this->add_lapp_function(0, '', '');
      $this->add_rapp_function(0, _("Sales &Order Entry"),
        "sales/sales_order_entry.php?add=0&type=30", SA_SALESORDER);

      $this->add_rapp_function(0, _("Sales Order &Inquiry"),
        "sales/inquiry/sales_orders_view.php?type=30", SA_SALESTRANSVIEW);

      $this->add_module(_("Deliveries and Invoices"));

      $this->add_lapp_function(1, _("Prepare &Invoice"),
        "sales/sales_order_entry.php?add=0&type=" . ST_CUSTDELIVERY, SA_SALESDELIVERY);
      $this->add_lapp_function(1, _("&Delivery Against Sales Orders"),
        "sales/inquiry/sales_orders_view.php?OutstandingOnly=1", SA_SALESDELIVERY);
      $this->add_lapp_function(1, _("&Invoice Against Sales Delivery"),
        "sales/inquiry/sales_deliveries_view.php?OutstandingOnly=1", SA_SALESINVOICE);
      $this->add_lapp_function(1, _("&Template Delivery"),
        "sales/inquiry/sales_orders_view.php?DeliveryTemplates=Yes", SA_SALESDELIVERY);
      $this->add_rapp_function(1, '', '');

      $this->add_rapp_function(1, _("Direct &Invoice"),
        "sales/sales_order_entry.php?add=0&type=" . ST_SALESINVOICE, SA_SALESINVOICE);

      $this->add_rapp_function(1, _("Invoice/D&elivery Inquiry"),
        "sales/inquiry/customer_inquiry.php?", SA_SALESTRANSVIEW);

      $this->add_rapp_function(1, _("&Template Invoice"),
        "sales/inquiry/sales_orders_view.php?InvoiceTemplates=Yes", SA_SALESINVOICE);
      $this->add_rapp_function(1, _("Recurrent &Invoices"),
        "sales/manage/recurrent_invoices.php?", SA_SRECURRENT);
      $this->add_rapp_function(1, _("&Create and Print Recurrent Invoices"),
        "sales/create_recurrent_invoices.php?", SA_SALESINVOICE);
      $this->add_rapp_function(1, '', '');
      $this->add_module(_("Payments and Customers"));
      $this->add_lapp_function(2, '', '');
      $this->add_lapp_function(2, _("Customer &Payments"),
        "sales/customer_payments.php?", SA_SALESPAYMNT);
      $this->add_lapp_function(2, _("Customer &Refunds"), "sales/customer_refunds.php?", SA_SALESREFUND);
      $this->add_lapp_function(2, _("Customer &Credit Notes"),
        "sales/credit_note_entry.php?NewCredit=Yes", SA_SALESCREDIT);
      $this->add_lapp_function(2, _("&Allocate Customer Payments or Credit Notes"),
        "sales/allocations/customer_allocation_main.php?", SA_SALESALLOC);
      $this->add_lapp_function(2, _("Customer Allocation &Inquiry"),
        "sales/inquiry/customer_allocation_inquiry.php?", SA_SALESALLOC);
      $this->add_lapp_function(2, '', '');
      /*	$this->add_rapp_function(2, _("Add and Manage &Customers"),
                       "sales/manage/customers.php?", SA_CUSTOMER);*/
      $this->add_rapp_function(2, _("Customer and Sales &Reports"), "reporting/reports_main.php?Class=0", SA_SALESTRANSVIEW);
      $this->add_rapp_function(2, _("Email Customer Statements"), "sales/email_statements.php", SA_SALESTRANSVIEW);
      /*		$this->add_rapp_function(2, _("Customer &Branches"),
                       "sales/manage/customer_branches.php?", SA_CUSTOMER);*/
      $this->add_module(_("Sales Setup"));

      $this->add_lapp_function(3, _("Credit &Status Setup"),
        "sales/manage/credit_status.php?", SA_CRSTATUS);

      $this->add_lapp_function(3, _("Sales T&ypes"),
        "sales/manage/sales_types.php?", SA_SALESTYPES);
      $this->add_rapp_function(3, _("Sales &Persons"),
        "sales/manage/sales_people.php?", SA_SALESMAN);

      $this->add_rapp_function(3, _("Sales &Areas"),
        "sales/manage/sales_areas.php?", SA_SALESAREA);
      $this->add_rapp_function(3, _("Sales &Groups"),
        "sales/manage/sales_groups.php?", SA_SALESGROUP);

      if (count($installed_extensions) > 0) {
        foreach ($installed_extensions as $mod) {
          if (@$mod['active'] && $mod['type'] == 'plugin' && $mod["tab"] == "sales") {
            $this->add_rapp_function(2, $mod["title"],
              "modules/" . $mod["path"] . "/" . $mod["filename"] . "?",
              isset($mod["access"]) ? $mod["access"] : SA_OPEN);
          }
        }
      }
    }
  }


