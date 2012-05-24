<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Apps_System extends Application {

    /**

     */
    function __construct() {
      global $installed_extensions;
      parent::__construct("System", _($this->help_context = "S&ystem"));
      $this->add_module(_("Company Setup"));
      $this->add_lapp_function(0, _("&Company Setup"), "/system/company_preferences?", SA_SETUPCOMPANY);
      $this->add_lapp_function(0, _("&User Accounts Setup"), "/system/users?", SA_USERS);
      $this->add_lapp_function(0, _("&Access Setup"), "/system/security_roles?", SA_SECROLES);
      $this->add_lapp_function(0, _("&Display Setup"), "/system/display_prefs?", SA_SETUPDISPLAY);
      $this->add_lapp_function(0, _("&Forms Setup"), "/system/forms_setup?", SA_FORMSETUP);
      $this->add_rapp_function(0, _("&Taxes"), "/taxes/tax_types?", SA_TAXRATES);
      $this->add_rapp_function(0, _("Tax &Groups"), "/taxes/tax_groups?", SA_TAXGROUPS);
      $this->add_rapp_function(0, _("Item Ta&x Types"), "/taxes/item_tax_types?", SA_ITEMTAXTYPE);
      $this->add_rapp_function(0, _("System and &General GL Setup"), "/system/gl_setup?", SA_GLSETUP);
      $this->add_rapp_function(0, _("&Fiscal Years"), "/system/fiscalyears?", SA_FISCALYEARS);
      $this->add_rapp_function(0, _("&Print Profiles"), "/system/print_profiles?", SA_PRINTPROFILE);
      $this->add_module(_("Miscellaneous"));
      $this->add_lapp_function(1, _("Pa&yment Terms"), "/system/payment_terms?", SA_PAYTERMS);
      $this->add_lapp_function(1, _("Shi&pping Company"), "/system/shipping_companies?", SA_SHIPPING);
      $this->add_rapp_function(1, _("&Points of Sale"), "/sales/manage/sales_points?", SA_POSSETUP);
      $this->add_rapp_function(1, _("&Printers"), "/system/printers?", SA_PRINTERS);
      $this->add_module(_("Maintenance"));
      $this->add_lapp_function(2, _("&Void a Transaction"), "/system/void_transaction?", SA_VOIDTRANSACTION);
      $this->add_lapp_function(2, _("View or &Print Transactions"), "/system/view_print_transaction?", SA_VIEWPRINTTRANSACTION);
      $this->add_lapp_function(2, _("&Attach Documents"), "/system/attachments?filterType=20", SA_ATTACHDOCUMENT);
      $this->add_lapp_function(2, _("System &Diagnostics"), "/system/system_diagnostics?", SA_OPEN);
      $this->add_rapp_function(2, _("&Backup and Restore"), "/system/backups?", SA_BACKUP);
      $this->add_rapp_function(2, _("Create/Update &Companies"), "/system/create_coy?", SA_CREATECOMPANY);
      $this->add_rapp_function(2, _("Install/Update &Languages"), "/system/inst_lang?", SA_CREATELANGUAGE);
      $this->add_rapp_function(2, _("Install/Activate &Extensions"), "/system/inst_module?", SA_CREATEMODULES);
      $this->add_rapp_function(2, _("Software &Upgrade"), "/system/inst_upgrade?", SA_SOFTWAREUPGRADE);
      if (count($installed_extensions) > 0) {
        foreach ($installed_extensions as $mod) {
          if (@$mod['active'] && $mod['type'] == 'plugin' && $mod["tab"] == "system") {
            $this->add_rapp_function(2, $mod["title"], "modules/" . $mod["path"] . "/" . $mod["filename"] . "?", isset($mod["access"]) ?
              $mod["access"] : SA_OPEN);
          }
        }
      }
    }
  }


