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
    /** Allow charcters in accounts */
    'allowcharacters' => NULL,
    /**
     * Date systems:
     * 0 = traditional
     * 1 = Jalali used by Iran, nabour countries, Afghanistan and some other Central Asian nations,
     * 2 = Islamic used by other arabic nations.
     * 3 = traditional, but where non-workday is Friday and start of week is Saturday
     */
    'datesystem' => 0,
    /** email stock location if order below reorder-level */
    'stock_emailnotify' => FALSE,
    /** Allocation ALlowance */
    'allocation_allowance' => 0.005,
    /** Audit Logging */
    'logs_audits' => TRUE,
  );
