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
  /**

   */
  class Dates extends \ADV\Core\Dates
  {
    protected $User = null;
    protected $Config = null;
    protected $Session = null;
    protected $Company = null;
    /**
     * @param \Config                   $config
     * @param \User                     $User
     * @param \Session                  $Session $Session
     * @param \DB_Company               $Company
     */
    public function __construct(\Config $config = null, \User $User = null, \Session $Session = null, \DB_Company $Company = null) {
      $config               = $config ? : \Config::i();
      $this->User           = $User ? : \User::i();
      $this->Session        = $Session ? : \Session::i();
      $this->Company        = $Company ? : \DB_Company::i();
      $this->userFiscalYear = $config->_get('use_fiscalyear');
      $this->sep            = $this->separators[is_int($this->User->_date_sep())?$this->User->_date_sep(): $config->_get('date.ui_separator')];
      $this->format         = $this->User->_date_format();
    }
    /**
     * Retrieve and optionally set default date for new document.
     *
     * @param null $date
     *
     * @return mixed|null
     */
    public function _newDocDate($date = null) {
      if (!$date) {
        $this->Session->_setGlobal('date', $date);
      } else {
        $date = $this->Session->_getGlobal('date');
      }
      if (!$date || !$this->User->_sticky_doc_date()) {
        $date = $this->Session->_setGlobal('date', $this->_today());
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
    public function _isDateInFiscalYear($date, $convert = false) {
      if (!$this->userFiscalYear) {
        return true;
      }
      $myrow = $this->Company->_get_current_fiscalyear();
      if ($myrow['closed'] == 1) {
        return false;
      }
      if ($convert) {
        $date2 = $this->_sqlToDate($date);
      } else {
        $date2 = $date;
      }
      $begin = $this->_sqlToDate($myrow['begin']);
      $end   = $this->_sqlToDate($myrow['end']);
      return ($this->_isGreaterThan($date2, $begin) || $this->_isGreaterThan($end, $date2));
    }
    /**
     * @static
     * @return string Date in Users Format
     */
    public function _beginFiscalYear() {
      $myrow = \DB_Company::get_current_fiscalyear();
      return $this->_sqlToDate($myrow['begin']);
    }
    /**
     * @static
     * @return string Date in Users Format
     */
    public function _endFiscalYear() {
      $myrow = \DB_Company::get_current_fiscalyear();
      return $this->_sqlToDate($myrow['end']);
    }
  }