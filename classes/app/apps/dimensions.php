<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Apps_Dimensions extends Application {

    /**

     */
    function __construct() {
      global $installed_extensions;
      $dim = DB_Company::get_pref('use_dimension');
      parent::__construct("Dimensions", _($this->help_context = "&Dimensions"), $dim);

      if ($dim > 0) {
        $this->add_module(_("Transactions"));
        $this->add_lapp_function(0, _("Dimension &Entry"),
          "/dimensions/dimension_entry?", SA_DIMENSION);
        $this->add_lapp_function(0, _("&Outstanding Dimensions"),
          "/dimensions/inquiry/search_dimensions?outstanding_only=1", SA_DIMTRANSVIEW);

        $this->add_module(_("Inquiries and Reports"));
        $this->add_lapp_function(1, _("Dimension &Inquiry"),
          "/dimensions/inquiry/search_dimensions?", SA_DIMTRANSVIEW);

        $this->add_rapp_function(1, _("Dimension &Reports"),
          "reporting/reports_main?Class=4", SA_DIMENSIONREP);

        $this->add_module(_("Maintenance"));
        $this->add_lapp_function(2, _("Dimension &Tags"),
          "system/tags?type=dimension", SA_DIMTAGS);

        if (count($installed_extensions) > 0) {
          foreach ($installed_extensions as $mod) {
            if (@$mod['active'] && $mod['type'] == 'plugin' && $mod["tab"] == "dimensions") {
              $this->add_rapp_function(2, $mod["title"],
                "modules/" . $mod["path"] . "/" . $mod["filename"] . "?",
                isset($mod["access"]) ? $mod["access"] : SA_OPEN);
            }
          }
        }
      }
    }
  }


