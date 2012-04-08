<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  class Apps_Manufacturing extends Application {
    /**
     *
     */
    function __construct() {
      global $installed_extensions;
      parent::__construct("Manufacturing", _($this->help_context = "&Manufacturing"));
      $this->add_module(_("Transactions"));
      $this->add_lapp_function(0, _("Work &Order Entry"),
        "manufacturing/work_order_entry.php?", SA_WORKORDERENTRY);
      $this->add_lapp_function(0, _("&Outstanding Work Orders"),
        "manufacturing/search_work_orders.php?outstanding_only=1", SA_MANUFTRANSVIEW);
      $this->add_module(_("Inquiries and Reports"));
      $this->add_lapp_function(1, _("Costed Bill Of Material Inquiry"),
        "manufacturing/inquiry/bom_cost_inquiry.php?", SA_WORKORDERCOST);
      $this->add_lapp_function(1, _("Inventory Item Where Used &Inquiry"),
        "manufacturing/inquiry/where_used_inquiry.php?", SA_WORKORDERANALYTIC);
      $this->add_lapp_function(1, _("Work Order &Inquiry"),
        "manufacturing/search_work_orders.php?", SA_MANUFTRANSVIEW);
      $this->add_rapp_function(1, _("Manufacturing &Reports"),
        "reporting/reports_main.php?Class=3", SA_MANUFTRANSVIEW);
      $this->add_module(_("Maintenance"));
      $this->add_lapp_function(2, _("&Bills Of Material"),
        "manufacturing/manage/bom_edit.php?", SA_BOM);
      $this->add_lapp_function(2, _("&Work Centres"),
        "manufacturing/manage/work_centres.php?", SA_WORKCENTRES);
      if (count($installed_extensions) > 0) {
        foreach ($installed_extensions as $mod) {
          if (@$mod['active'] && $mod['type'] == 'plugin' && $mod["tab"] == "manufacturing") {
            $this->add_rapp_function(2, $mod["title"],
              "modules/" . $mod["path"] . "/" . $mod["filename"] . "?",
              isset($mod["access"]) ? $mod["access"] : SA_OPEN);
          }
        }
      }
    }
  }


