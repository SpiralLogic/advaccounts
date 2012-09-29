<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @date      22/09/12
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Controllers;

  use \ADV\App\Controller\Menu;

  /**

   */
  class Banking extends Menu {
    public $name = "Banking";
    public $help_context = "&Banking";
    /**

     */
    protected function before() {
      $module = $this->add_module(_("Transactions"));
      $module->addLeftFunction(_("&Payments"), "/banking/banking?NewPayment=Yes", SA_PAYMENT);
      $module->addLeftFunction(_("&Deposits"), "/banking/banking?NewDeposit=Yes", SA_DEPOSIT);
      $module->addLeftFunction(_("Bank Account &Transfers"), "/gl/bank_transfer?", SA_BANKTRANSFER);
      $module->addRightFunction(_("&Journal Entry"), "/gl/gl_journal?NewJournal=Yes", SA_JOURNALENTRY);
      $module->addRightFunction(_("&Budget Entry"), "/gl/gl_budget?", SA_BUDGETENTRY);
      $module->addRightFunction(_("&Reconcile Bank Account"), "/banking/reconcile?", SA_RECONCILE);
      /*      $module->addRightFunction(_("&Compare Reconcile to Bank Statement"), "/gl/inquiry/bankstatement?", SA_RECONCILE);*/
      $module = $this->add_module(_("Inquiries and Reports"));
      $module->addLeftFunction(_("&Journal Inquiry"), "/gl/inquiry/journal?", SA_GLANALYTIC);
      $module->addLeftFunction(_("GL &Inquiry"), "/gl/inquiry/gl_account?", SA_GLTRANSVIEW);
      $module->addLeftFunction(_("Bank Account &Inquiry"), "/gl/inquiry/bank?", SA_BANKTRANSVIEW);
      $module->addLeftFunction(_("Ta&x Inquiry"), "/gl/inquiry/tax?", SA_TAXREP);
      $module->addRightFunction(_("Trial &Balance"), "/gl/inquiry/gl_trial_balance?", SA_GLANALYTIC);
      $module->addRightFunction(_("Balance &Sheet Drilldown"), "/gl/inquiry/balance_sheet?", SA_GLANALYTIC);
      $module->addRightFunction(_("&Profit and Loss Drilldown"), "/gl/inquiry/profit_loss?", SA_GLANALYTIC);
      $module->addRightFunction(_("Banking &Reports"), "/reporting/reports_main?Class=5", SA_BANKREP);
      $module->addRightFunction(_("General Ledger &Reports"), "/reporting/reports_main?Class=6", SA_GLREP);
      $module = $this->add_module(_("Maintenance"));
      $module->addLeftFunction(_("Bank &Accounts"), "/gl/manage/bank_accounts?", SA_BANKACCOUNT);
      $module->addLeftFunction(_("&Quick Entries"), "/gl/manage/gl_quick_entries?", SA_QUICKENTRY);
      $module->addLeftFunction(_("Account &Tags"), "system/tags?type=account", SA_GLACCOUNTTAGS);
      //  $module->addLeftFunction(_("Payment Methods"), "/gl/manage/payment_methods", SA_BANKACCOUNT);
      $module->addLeftFunction(_("&Currencies"), "/banking/manage/currencies?", SA_CURRENCY);
      $module->addLeftFunction(_("&Exchange Rates"), "/gl/manage/exchange_rates?", SA_EXCHANGERATE);
      $module->addRightFunction(_("&GL Accounts"), "/gl/manage/gl_accounts?", SA_GLACCOUNT);
      $module->addRightFunction(_("GL Account &Groups"), "/gl/manage/gl_account_types?", SA_GLACCOUNTGROUP);
      $module->addRightFunction(_("GL Account &Classes"), "/gl/manage/gl_account_classes?", SA_GLACCOUNTCLASS);
    }
  }
