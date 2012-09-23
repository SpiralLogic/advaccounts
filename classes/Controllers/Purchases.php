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

  /**

   */
  class Purchases extends Menu {
    public $name = "Purchases";
    public $help_context = "&Purchases";
    /**

     */
    public function buildMenu() {

      $module = $this->add_module(_("Purchases"));
      $module->addLeftFunction(_("Purchase &Order Entry"), "/purchases/order?NewOrder=Yes", SA_PURCHASEORDER);
      $module->addLeftFunction(_("Purchase Orders &Inquiry"), "/purchases/search/completed?", SA_SUPPTRANSVIEW);
      $module->addLeftFunction(_("&Outstanding Purchase Orders"), "/purchases/search/orders?", SA_SUPPTRANSVIEW);
      $module->addRightFunction(_("Supplier &Invoices"), "/purchases/invoice?New=1", SA_SUPPLIERINVOICE);
      $module->addRightFunction(_("Supplier &Credit Notes"), "/purchases/credit?New=1", SA_SUPPLIERCREDIT);
      $module = $this->add_module(_("Inquiries and Reports"));
      $module->addLeftFunction(_("&Payments to Suppliers"), "/purchases/payment?", SA_SUPPLIERPAYMNT);
      $module->addLeftFunction(_("&Allocate Supplier Payments or Credit Notes"), "/purchases/allocations/supplier_allocation_main?", SA_SUPPLIERALLOC);
      $module->addRightFunction(_("Supplier Transaction &Inquiry"), "/purchases/search/transactions?", SA_SUPPTRANSVIEW);
      $module->addRightFunction(_("Supplier Allocation &Inquiry"), "/purchases/search/allocations?", SA_SUPPLIERALLOC);
      $module->addRightFunction(_("Supplier and Purchasing &Reports"), "reporting/reports_main?Class=1", SA_SUPPTRANSVIEW);
      $module = $this->add_module(_("Maintenance"));
      $module->addLeftFunction(_("&Suppliers"), "/purchases/manage/suppliers?", SA_SUPPLIER);
    }
  }
