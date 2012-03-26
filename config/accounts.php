<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  return array(
    'allowcharacters' => NULL,
    /* Date systems. 0 = traditional, 1 = Jalali used by Iran, nabour countries, Afghanistan and some other Central Asian nations,
                         2 = Islamic used by other arabic nations. 3 = traditional, but where non-workday is Friday and start of week is Saturday */
    'datesystem' => 0, /* email stock location if order below reorder-level */
    'stock_emailnotify' => FALSE, //
    'allocation_allowance' => 0.005, /* use old style convert (income and expense in BS, PL) */
    'gl_oldconvertstyle' => FALSE, /* print_invoice_no. 0 = print reference number, 1 = print invoice number */
    'logs_audits' => TRUE, /* use old style convert (income and expense in BS, PL) */
  );
