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
  use ADV\App\Application;

  /**

   */
  class Purchases extends Application
  {
    public $name = 'Purchases';
    public $help_context = "&Purchases";
    public function buildMenu() {
      $this->add_module(_("Purchases"));
      $this->addLeftFunction(0, _("Purchase &Order Entry"), "/purchases/po_entry_items?NewOrder=Yes", SA_PURCHASEORDER);
      $this->addLeftFunction(0, _("Purchase Orders &Inquiry"), "/purchases/inquiry/po_search_completed?", SA_SUPPTRANSVIEW);
      $this->addLeftFunction(0, _("&Outstanding Purchase Orders"), "/purchases/inquiry/po_search?", SA_SUPPTRANSVIEW);
      $this->addRightFunction(0, _("Supplier &Invoices"), "/purchases/supplier_invoice?New=1", SA_SUPPLIERINVOICE);
      $this->addRightFunction(0, _("Supplier &Credit Notes"), "/purchases/supplier_credit?New=1", SA_SUPPLIERCREDIT);
      $this->add_module(_("Inquiries and Reports"));
      $this->addLeftFunction(1, _("&Payments to Suppliers"), "/purchases/supplier_payment?", SA_SUPPLIERPAYMNT);
      $this->addLeftFunction(1, _("&Allocate Supplier Payments or Credit Notes"), "/purchases/allocations/supplier_allocation_main?", SA_SUPPLIERALLOC);
      $this->addRightFunction(1, _("Supplier Transaction &Inquiry"), "/purchases/inquiry/supplier_inquiry?", SA_SUPPTRANSVIEW);
      $this->addRightFunction(1, _("Supplier Allocation &Inquiry"), "/purchases/inquiry/supplier_allocation_inquiry?", SA_SUPPLIERALLOC);
      $this->addRightFunction(1, _("Supplier and Purchasing &Reports"), "reporting/reports_main?Class=1", SA_SUPPTRANSVIEW);
      $this->add_module(_("Maintenance"));
      $this->addLeftFunction(2, _("&Suppliers"), "/purchases/manage/suppliers?", SA_SUPPLIER);
    }
  }

