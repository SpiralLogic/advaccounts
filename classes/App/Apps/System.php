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
  class System extends Application
  {
    public $name = "System";
    public $help_context = "&System";
    public function buildMenu() {
      $this->add_module(_("Company Setup"));
      $this->addLeftFunction(0, _("&Company Setup"), "/system/company_preferences?", SA_SETUPCOMPANY);
      $this->addLeftFunction(0, _("&User Accounts Setup"), "/system/users?", SA_USERS);
      $this->addLeftFunction(0, _("&Access Setup"), "/system/security_roles?", SA_SECROLES);
      $this->addLeftFunction(0, _("&Display Setup"), "/system/display_prefs?", SA_SETUPDISPLAY);
      $this->addLeftFunction(0, _("&Forms Setup"), "/system/forms_setup?", SA_FORMSETUP);
      $this->addRightFunction(0, _("&Taxes"), "/taxes/tax_types?", SA_TAXRATES);
      $this->addRightFunction(0, _("Tax &Groups"), "/taxes/tax_groups?", SA_TAXGROUPS);
      $this->addRightFunction(0, _("Item Ta&x Types"), "/taxes/item_tax_types?", SA_ITEMTAXTYPE);
      $this->addRightFunction(0, _("System and &General GL Setup"), "/system/gl_setup?", SA_GLSETUP);
      $this->addRightFunction(0, _("&Fiscal Years"), "/system/fiscalyears?", SA_FISCALYEARS);
      $this->addRightFunction(0, _("&Print Profiles"), "/system/print_profiles?", SA_PRINTPROFILE);
      $this->add_module(_("Miscellaneous"));
      $this->addLeftFunction(1, _("Pa&yment Terms"), "/system/payment_terms?", SA_PAYTERMS);
      $this->addLeftFunction(1, _("Shi&pping Company"), "/system/shipping_companies?", SA_SHIPPING);
      $this->addRightFunction(1, _("&Points of Sale"), "/sales/manage/sales_points?", SA_POSSETUP);
      $this->addRightFunction(1, _("&Printers"), "/system/printers?", SA_PRINTERS);
      $this->add_module(_("Maintenance"));
      $this->addLeftFunction(2, _("&Void a Transaction"), "/system/void_transaction?", SA_VOIDTRANSACTION);
      $this->addLeftFunction(2, _("View or &Print Transactions"), "/system/view_print_transaction?", SA_VIEWPRINTTRANSACTION);
      $this->addLeftFunction(2, _("&Attach Documents"), "/system/attachments?filterType=20", SA_ATTACHDOCUMENT);
      $this->addLeftFunction(2, _("System &Diagnostics"), "/system/system_diagnostics?", SA_OPEN);
      $this->addRightFunction(2, _("&Backup and Restore"), "/system/backups?", SA_BACKUP);
      $this->addRightFunction(2, _("Create/Update &Companies"), "/system/create_coy?", SA_CREATECOMPANY);
      $this->addRightFunction(2, _("Install/Update &Languages"), "/system/inst_lang?", SA_CREATELANGUAGE);
      $this->addRightFunction(2, _("Install/Activate &Extensions"), "/system/inst_module?", SA_CREATEMODULES);
      $this->addRightFunction(2, _("Software &Upgrade"), "/system/inst_upgrade?", SA_SOFTWAREUPGRADE);
    }
  }

