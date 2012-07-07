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
  use DB_Company;
  use ADV\App\Application;

  /**

   */
  class Dimensions extends Application {
    public $name = "Dimensions";
    public $help_context = "&Dimensions";
    public $enabled = false;
    /**

     */
    public function __construct() {
      $this->enabled = DB_Company::get_pref('use_dimension');
      parent::__construct();
    }
    /**

     */
    public function buildMenu() {
      $this->add_module(_("Transactions"));
      $this->addLeftFunction(0, _("Dimension &Entry"), "/dimensions/dimension_entry?", SA_DIMENSION);
      $this->addLeftFunction(0, _("&Outstanding Dimensions"), "/dimensions/inquiry/search_dimensions?outstanding_only=1", SA_DIMTRANSVIEW);
      $this->add_module(_("Inquiries and Reports"));
      $this->addLeftFunction(1, _("Dimension &Inquiry"), "/dimensions/inquiry/search_dimensions?", SA_DIMTRANSVIEW);
      $this->addRightFunction(1, _("Dimension &Reports"), "reporting/reports_main?Class=4", SA_DIMENSIONREP);
      $this->add_module(_("Maintenance"));
      $this->addLeftFunction(2, _("Dimension &Tags"), "system/tags?type=dimension", SA_DIMTAGS);
    }
  }

