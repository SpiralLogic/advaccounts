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
  class Manufacturing extends Application {
    public $name = "Manufacturing";
    public $help_context = "&Manufacturing";
    /**

     */
    public function buildMenu() {
      $this->add_module(_("Transactions"));
      $this->addLeftFunction(0, _("Work &Order Entry"), "/manufacturing/work_order_entry?", SA_WORKORDERENTRY);
      $this->addLeftFunction(0, _("&Outstanding Work Orders"), "/manufacturing/search_work_orders?outstanding_only=1", SA_MANUFTRANSVIEW);
      $this->add_module(_("Inquiries and Reports"));
      $this->addLeftFunction(1, _("Costed Bill Of Material Inquiry"), "/manufacturing/inquiry/bom_cost?", SA_WORKORDERCOST);
      $this->addLeftFunction(1, _("Inventory Item Where Used &Inquiry"), "/manufacturing/inquiry/where_used?", SA_WORKORDERANALYTIC);
      $this->addLeftFunction(1, _("Work Order &Inquiry"), "/manufacturing/search_work_orders?", SA_MANUFTRANSVIEW);
      $this->addRightFunction(1, _("Manufacturing &Reports"), "reporting/reports_main?Class=3", SA_MANUFTRANSVIEW);
      $this->add_module(_("Maintenance"));
      $this->addLeftFunction(2, _("&Bills Of Material"), "/manufacturing/manage/bom_edit?", SA_BOM);
      $this->addLeftFunction(2, _("&Work Centres"), "/manufacturing/manage/work_centres?", SA_WORKCENTRES);
    }
  }

