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
  use ADV\App\Application\Application;

  /**

   */
  class Inventory extends Application
  {
    public $name = "Inventory";
    public $help_context = "&Inventory";
    public function buildMenu() {
      $this->add_module(_("Transactions"));
      $this->addLeftFunction(0, _("Inventory Location &Transfers"), "/inventory/transfers?NewTransfer=1", SA_LOCATIONTRANSFER);
      $this->addLeftFunction(0, _("Inventory &Adjustments"), "/inventory/adjustments?NewAdjustment=1", SA_INVENTORYADJUSTMENT);
      $this->add_module(_("Inquiries and Reports"));
      $this->addLeftFunction(1, _("Inventory Item &Movements"), "/inventory/inquiry/stock_movements?", SA_ITEMSTRANSVIEW);
      $this->addLeftFunction(1, _("Inventory Item &Status"), "/inventory/inquiry/stock_status?", SA_ITEMSSTATVIEW);
      $this->addRightFunction(1, _("Inventory &Reports"), "reporting/reports_main?Class=2", SA_ITEMSTRANSVIEW);
      $this->add_module(_("Maintenance"));
      $this->addLeftFunction(2, _("&Items"), "/inventory/manage/items?", SA_ITEM);
      $this->addLeftFunction(2, _("&Items2"), "items/quickitems?", SA_ITEM);
      $this->addLeftFunction(2, _("&Foreign Item Codes"), "/inventory/manage/item_codes?", SA_FORITEMCODE);
      $this->addLeftFunction(2, _("Sales &Kits"), "/inventory/manage/sales_kits?", SA_SALESKIT);
      $this->addLeftFunction(2, _("Item &Categories"), "/inventory/manage/item_categories?", SA_ITEMCATEGORY);
      $this->addLeftFunction(2, _("Inventory &Locations"), "/inventory/manage/locations?", SA_INVENTORYLOCATION);
      $this->addRightFunction(2, _("Inventory &Movement Types"), "/inventory/manage/movement_types?", SA_INVENTORYMOVETYPE);
      $this->addRightFunction(2, _("&Units of Measure"), "/inventory/manage/item_units?", SA_UOM);
      $this->addRightFunction(2, _("&Reorder Levels"), "/inventory/reorder_level?", SA_REORDER);
      $this->addRightFunction(2, _("&Barcodes"), "/inventory/barcodes?", SA_INVENTORYLOCATION);
      $this->add_module(_("Pricing and Costs"));
      $this->addLeftFunction(3, _("Sales &Pricing"), "/inventory/prices?", SA_SALESPRICE);
      $this->addLeftFunction(3, _("Purchasing &Pricing"), "/inventory/purchasing_data?", SA_PURCHASEPRICING);
      $this->addRightFunction(3, _("Standard &Costs"), "/inventory/cost_update?", SA_STANDARDCOST);
    }
  }
