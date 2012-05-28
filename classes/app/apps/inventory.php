<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Apps_Inventory extends Application
  {
    public $name = "Inventory";
    public $help_context = "&Inventory";
    /**

     */
    public function buildMenu()
    {
      $this->add_module(_("Transactions"));
      $this->add_lapp_function(0, _("Inventory Location &Transfers"), "/inventory/transfers?NewTransfer=1", SA_LOCATIONTRANSFER);
      $this->add_lapp_function(0, _("Inventory &Adjustments"), "/inventory/adjustments?NewAdjustment=1", SA_INVENTORYADJUSTMENT);
      $this->add_module(_("Inquiries and Reports"));
      $this->add_lapp_function(1, _("Inventory Item &Movements"), "/inventory/inquiry/stock_movements?", SA_ITEMSTRANSVIEW);
      $this->add_lapp_function(1, _("Inventory Item &Status"), "/inventory/inquiry/stock_status?", SA_ITEMSSTATVIEW);
      $this->add_rapp_function(1, _("Inventory &Reports"), "reporting/reports_main?Class=2", SA_ITEMSTRANSVIEW);
      $this->add_module(_("Maintenance"));
      $this->add_lapp_function(2, _("&Items"), "/inventory/manage/items?", SA_ITEM);
      $this->add_lapp_function(2, _("&Items2"), "items/quickitems?", SA_ITEM);
      $this->add_lapp_function(2, _("&Foreign Item Codes"), "/inventory/manage/item_codes?", SA_FORITEMCODE);
      $this->add_lapp_function(2, _("Sales &Kits"), "/inventory/manage/sales_kits?", SA_SALESKIT);
      $this->add_lapp_function(2, _("Item &Categories"), "/inventory/manage/item_categories?", SA_ITEMCATEGORY);
      $this->add_lapp_function(2, _("Inventory &Locations"), "/inventory/manage/locations?", SA_INVENTORYLOCATION);
      $this->add_rapp_function(2, _("Inventory &Movement Types"), "/inventory/manage/movement_types?", SA_INVENTORYMOVETYPE);
      $this->add_rapp_function(2, _("&Units of Measure"), "/inventory/manage/item_units?", SA_UOM);
      $this->add_rapp_function(2, _("&Reorder Levels"), "/inventory/reorder_level?", SA_REORDER);
      $this->add_rapp_function(2, _("&Barcodes"), "/inventory/barcodes?", SA_INVENTORYLOCATION);
      $this->add_module(_("Pricing and Costs"));
      $this->add_lapp_function(3, _("Sales &Pricing"), "/inventory/prices?", SA_SALESPRICE);
      $this->add_lapp_function(3, _("Purchasing &Pricing"), "/inventory/purchasing_data?", SA_PURCHASEPRICING);
      $this->add_rapp_function(3, _("Standard &Costs"), "/inventory/cost_update?", SA_STANDARDCOST);
    }
  }
