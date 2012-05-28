<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Apps_GL extends Application
  {
    public $name = 'GL';
    public $help_context = "&Banking";
    /**

     */
    public function buildMenu()
    {
      $this->add_module(_("Transactions"));
      $this->add_lapp_function(0, _("&Payments"), "/gl/gl_bank?NewPayment=Yes", SA_PAYMENT);
      $this->add_lapp_function(0, _("&Deposits"), "/gl/gl_bank?NewDeposit=Yes", SA_DEPOSIT);
      $this->add_lapp_function(0, _("Bank Account &Transfers"), "/gl/bank_transfer?", SA_BANKTRANSFER);
      $this->add_rapp_function(0, _("&Journal Entry"), "/gl/gl_journal?NewJournal=Yes", SA_JOURNALENTRY);
      $this->add_rapp_function(0, _("&Budget Entry"), "/gl/gl_budget?", SA_BUDGETENTRY);
      $this->add_rapp_function(0, _("&Reconcile Bank Account"), "/gl/bank_account_reconcile?", SA_RECONCILE);
      $this->add_rapp_function(0, _("Undepostied Funds"), "/gl/undeposited_funds?", SA_RECONCILE);
      $this->add_module(_("Inquiries and Reports"));
      $this->add_lapp_function(1, _("&Journal Inquiry"), "/gl/inquiry/journal?", SA_GLANALYTIC);
      $this->add_lapp_function(1, _("GL &Inquiry"), "/gl/inquiry/gl_account?", SA_GLTRANSVIEW);
      $this->add_lapp_function(1, _("Bank Account &Inquiry"), "/gl/inquiry/bank?", SA_BANKTRANSVIEW);
      $this->add_lapp_function(1, _("Ta&x Inquiry"), "/gl/inquiry/tax?", SA_TAXREP);
      $this->add_rapp_function(1, _("Trial &Balance"), "/gl/inquiry/gl_trial_balance?", SA_GLANALYTIC);
      $this->add_rapp_function(1, _("Balance &Sheet Drilldown"), "/gl/inquiry/balance_sheet?", SA_GLANALYTIC);
      $this->add_rapp_function(1, _("&Profit and Loss Drilldown"), "/gl/inquiry/profit_loss?", SA_GLANALYTIC);
      $this->add_rapp_function(1, _("Banking &Reports"), "/reporting/reports_main?Class=5", SA_BANKREP);
      $this->add_rapp_function(1, _("General Ledger &Reports"), "/reporting/reports_main?Class=6", SA_GLREP);
      $this->add_module(_("Maintenance"));
      $this->add_lapp_function(2, _("Bank &Accounts"), "/gl/manage/bank_accounts?", SA_BANKACCOUNT);
      $this->add_lapp_function(2, _("&Quick Entries"), "/gl/manage/gl_quick_entries?", SA_QUICKENTRY);
      $this->add_lapp_function(2, _("Account &Tags"), "system/tags?type=account", SA_GLACCOUNTTAGS);
      $this->add_lapp_function(2, _("Payment Methods"), "/gl/manage/payment_methods", SA_BANKACCOUNT);
      $this->add_lapp_function(2, _("&Currencies"), "/gl/manage/currencies?", SA_CURRENCY);
      $this->add_lapp_function(2, _("&Exchange Rates"), "/gl/manage/exchange_rates?", SA_EXCHANGERATE);
      $this->add_rapp_function(2, _("&GL Accounts"), "/gl/manage/gl_accounts?", SA_GLACCOUNT);
      $this->add_rapp_function(2, _("GL Account &Groups"), "/gl/manage/gl_account_types?", SA_GLACCOUNTGROUP);
      $this->add_rapp_function(2, _("GL Account &Classes"), "/gl/manage/gl_account_classes?", SA_GLACCOUNTCLASS);

    }
  }

