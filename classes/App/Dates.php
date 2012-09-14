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

  use ADV\Core\Config;
  use DB_Company;
  use ADV\Core\Session;

  /**

   */
  class Dates extends \ADV\Core\Dates {
    use \ADV\Core\Traits\StaticAccess2;

    protected $User = null;
    protected $Config = null;
    protected $Session = null;
    protected $Company = null;
    /**
     * @param \Config                   $config
     * @param \ADV\App\User|User        $User
     * @param \Session                  $Session $Session
     * @param \DB_Company               $Company
     */
    public function __construct(\Config $config = null, User $User = null, \Session $Session = null, \DB_Company $Company = null) {
      $config               = $config ? : Config::i();
      $this->User           = $User ? : User::i();
      $this->Session        = $Session ? : Session::i();
      $this->Company        = $Company ? : DB_Company::i();
      $this->userFiscalYear = $config->_get('use_fiscalyear');
      $this->sep            = $this->separators[is_int($this->User->_date_sep()) ? $this->User->_date_sep() : $config->get('date.ui_separator')];
      $this->format         = $this->User->_date_format();
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
        $this->Session->setGlobal('date', $date);
      } else {
        $date = $this->Session->getGlobal('date');
      }
      if (!$date || !$this->User->_sticky_doc_date()) {
        $date = $this->Session->setGlobal('date', $this->_today());
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
      if (!$this->userFiscalYear) {
        return true;
      }
      $myrow = $this->Company->_get_current_fiscalyear();
      if ($myrow['closed'] == 1) {
        return false;
      }
      if ($convert) {
        $date2 = $this->sqlToDate($date);
      } else {
        $date2 = $date;
      }
      $begin = $this->_sqlToDate($myrow['begin']);
      $end   = $this->_sqlToDate($myrow['end']);
      return ($this->isGreaterThan($date2, $begin) || $this->isGreaterThan($end, $date2));
    }
    /**
     * @static
     * @return string Date in Users Format
     */
    public function beginFiscalYear() {
      $myrow = \DB_Company::get_current_fiscalyear();
      return $this->sqlToDate($myrow['begin']);
    }
    /**
     * @static
     * @return string Date in Users Format
     */
    public function endFiscalYear() {
      $myrow = \DB_Company::get_current_fiscalyear();
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
