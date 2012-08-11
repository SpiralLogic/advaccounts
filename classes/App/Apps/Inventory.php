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
      $module=$this->add_module(_("Transactions"));
      $module->addLeftFunction(_("Inventory Location &Transfers"), "/inventory/transfers?NewTransfer=1", SA_LOCATIONTRANSFER);
      $module->addLeftFunction(_("Inventory &Adjustments"), "/inventory/adjustments?NewAdjustment=1", SA_INVENTORYADJUSTMENT);
      $module=$this->add_module(_("Inquiries and Reports"));
      $module->addLeftFunction(_("Inventory Item &Movements"), "/inventory/inquiry/stock_movements?", SA_ITEMSTRANSVIEW);
      $module->addLeftFunction(_("Inventory Item &Status"), "/inventory/inquiry/stock_status?", SA_ITEMSSTATVIEW);
      $module->addRightFunction(_("Inventory &Reports"), "reporting/reports_main?Class=2", SA_ITEMSTRANSVIEW);
      $module=$this->add_module(_("Maintenance"));
      $module->addLeftFunction(_("&Items"), "/inventory/manage/items?", SA_ITEM);
      $module->addLeftFunction(_("&Items2"), "items/quickitems?", SA_ITEM);
      $module->addLeftFunction(_("&Foreign Item Codes"), "/inventory/manage/item_codes?", SA_FORITEMCODE);
      $module->addLeftFunction(_("Sales &Kits"), "/inventory/manage/sales_kits?", SA_SALESKIT);
      $module->addLeftFunction(_("Item &Categories"), "/inventory/manage/item_categories?", SA_ITEMCATEGORY);
      $module->addLeftFunction(_("Inventory &Locations"), "/inventory/manage/locations?", SA_INVENTORYLOCATION);
      $module->addRightFunction(_("Inventory &Movement Types"), "/inventory/manage/movement_types?", SA_INVENTORYMOVETYPE);
      $module->addRightFunction(_("&Units of Measure"), "/inventory/manage/item_units?", SA_UOM);
      $module->addRightFunction(_("&Reorder Levels"), "/inventory/reorder_level?", SA_REORDER);
      $module->addRightFunction(_("&Barcodes"), "/inventory/barcodes?", SA_INVENTORYLOCATION);
      $module=$this->add_module(_("Pricing and Costs"));
      $module->addLeftFunction( _("Sales &Pricing"), "/inventory/prices?", SA_SALESPRICE);
      $module->addLeftFunction( _("Purchasing &Pricing"), "/inventory/purchasing_data?", SA_PURCHASEPRICING);
      $module->addRightFunction(3, _("Standard &Costs"), "/inventory/cost_update?", SA_STANDARDCOST);
    }
  }
