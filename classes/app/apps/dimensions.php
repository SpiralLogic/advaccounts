<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Apps_Dimensions extends Application
  {
    public $name = "Dimensions";
    public $help_context = "&Dimensions";
    public $enabled = false;
    public function __construct()
    {
      $this->enabled = DB_Company::get_pref('use_dimension');
      parent::__construct();
    }
    /**

     */
    public function buildMenu()
    {
        $this->add_module(_("Transactions"));
        $this->add_lapp_function(0, _("Dimension &Entry"), "/dimensions/dimension_entry?", SA_DIMENSION);
        $this->add_lapp_function(0, _("&Outstanding Dimensions"), "/dimensions/inquiry/search_dimensions?outstanding_only=1", SA_DIMTRANSVIEW);
        $this->add_module(_("Inquiries and Reports"));
        $this->add_lapp_function(1, _("Dimension &Inquiry"), "/dimensions/inquiry/search_dimensions?", SA_DIMTRANSVIEW);
        $this->add_rapp_function(1, _("Dimension &Reports"), "reporting/reports_main?Class=4", SA_DIMENSIONREP);
        $this->add_module(_("Maintenance"));
        $this->add_lapp_function(2, _("Dimension &Tags"), "system/tags?type=dimension", SA_DIMTAGS);
    }
  }

