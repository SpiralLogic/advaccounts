<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @date      28/07/12
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App;

  use DB_Company;

  /**

   */
  class Dates extends \ADV\Core\Dates {
    use \ADV\Core\Traits\StaticAccess2;

    public $sticky_doc_date = false;
    public $use_fiscal_year = false;
    protected $Session = null;
    protected $Company = null;
    public $docDate;
    /**
     * @param \DB_Company                        $company
     *
     * @internal param \ADV\Core\Session $session
     */
    public function __construct(DB_Company $company) {
      $this->Company = $company;
    }
    /**
     * Retrieve and optionally set default date for new document.
     *
     * @param null $date
     *
     * @return mixed|null
     */
    public function newDocDate($date = null) {
      if (!$date) {
        $this->docDate = $date;
      } else {
        $date = $this->docDate;
      }
      if (!$date || !(bool) $this->sticky_doc_date) {
        $date = $this->docDate = $this->today();
      }
      return $date;
    }
    /**
     * @static
     *
     * @param      $date
     * @param bool $convert
     *
     * @return bool
     */
    public function isDateInFiscalYear($date, $convert = false) {
      if (!(bool) $this->userFiscalYear) {
        return true;
      }
      $myrow = DB_Company::i()->_get_current_fiscalyear();
      if ($myrow['closed'] == 1) {
        return false;
      }
      if ($convert) {
        $date2 = $this->sqlToDate($date);
      } else {
        $date2 = $date;
      }
      $begin = $this->sqlToDate($myrow['begin']);
      $end   = $this->sqlToDate($myrow['end']);
      return ($this->isGreaterThan($date2, $begin) || $this->isGreaterThan($end, $date2));
    }
    /**
     * @static
     * @return string Date in Users Format
     */
    public function beginFiscalYear() {
      $myrow = DB_Company::i()->_get_current_fiscalyear();
      return $this->sqlToDate($myrow['begin']);
    }
    /**
     * @static
     * @return string Date in Users Format
     */
    public function endFiscalYear() {
      $myrow = DB_Company::i()->_get_current_fiscalyear();
      return $this->sqlToDate($myrow['end']);
    }
    /**
     * @return mixed
     */
    /**
     * @static
     *
     * @param     $name
     * @param int $month
     *
     * @return string
     */
    public function months($name, $month = 0) {
      $months[-1] = 'Current';
      for ($i = 0; $i < 11; $i++) {
        $months[$i] = date('F', strtotime("now - $i months"));
      }
      return Forms::arraySelect($name, $month, $months);
    }
  }
