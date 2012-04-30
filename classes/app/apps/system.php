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
     *
     */
    function __construct() {
      global $installed_extensions;
      parent::__construct("System", _($this->help_context = "S&ystem"));
      $this->add_module(_("Company Setup"));
      $this->add_lapp_function(0, _("&Company Setup"), "/system/company_preferences.php?", SA_SETUPCOMPANY);
      $this->add_lapp_function(0, _("&User Accounts Setup"), "/system/users.php?", SA_USERS);
      $this->add_lapp_function(0, _("&Access Setup"), "/system/security_roles.php?", SA_SECROLES);
      $this->add_lapp_function(0, _("&Display Setup"), "/system/display_prefs.php?", SA_SETUPDISPLAY);
      $this->add_lapp_function(0, _("&Forms Setup"), "/system/forms_setup.php?", SA_FORMSETUP);
      $this->add_rapp_function(0, _("&Taxes"), "/taxes/tax_types.php?", SA_TAXRATES);
      $this->add_rapp_function(0, _("Tax &Groups"), "/taxes/tax_groups.php?", SA_TAXGROUPS);
      $this->add_rapp_function(0, _("Item Ta&x Types"), "/taxes/item_tax_types.php?", SA_ITEMTAXTYPE);
      $this->add_rapp_function(0, _("System and &General GL Setup"), "/system/gl_setup.php?", SA_GLSETUP);
      $this->add_rapp_function(0, _("&Fiscal Years"), "/system/fiscalyears.php?", SA_FISCALYEARS);
      $this->add_rapp_function(0, _("&Print Profiles"), "/system/print_profiles.php?", SA_PRINTPROFILE);
      $this->add_module(_("Miscellaneous"));
      $this->add_lapp_function(1, _("Pa&yment Terms"), "/system/payment_terms.php?", SA_PAYTERMS);
      $this->add_lapp_function(1, _("Shi&pping Company"), "/system/shipping_companies.php?", SA_SHIPPING);
      $this->add_rapp_function(1, _("&Points of Sale"), "/sales/manage/sales_points.php?", SA_POSSETUP);
      $this->add_rapp_function(1, _("&Printers"), "/system/printers.php?", SA_PRINTERS);
      $this->add_module(_("Maintenance"));
      $this->add_lapp_function(2, _("&Void a Transaction"), "/system/void_transaction.php?", SA_VOIDTRANSACTION);
      $this->add_lapp_function(2, _("View or &Print Transactions"), "/system/view_print_transaction.php?", SA_VIEWPRINTTRANSACTION);
      $this->add_lapp_function(2, _("&Attach Documents"), "/system/attachments.php?filterType=20", SA_ATTACHDOCUMENT);
      $this->add_lapp_function(2, _("System &Diagnostics"), "/system/system_diagnostics.php?", SA_OPEN);
      $this->add_rapp_function(2, _("&Backup and Restore"), "/system/backups.php?", SA_BACKUP);
      $this->add_rapp_function(2, _("Create/Update &Companies"), "/system/create_coy.php?", SA_CREATECOMPANY);
      $this->add_rapp_function(2, _("Install/Update &Languages"), "/system/inst_lang.php?", SA_CREATELANGUAGE);
      $this->add_rapp_function(2, _("Install/Activate &Extensions"), "/system/inst_module.php?", SA_CREATEMODULES);
      $this->add_rapp_function(2, _("Software &Upgrade"), "/system/inst_upgrade.php?", SA_SOFTWAREUPGRADE);
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

