<?php
  /**
   * ADVAccounting
   * PHP version 5.4
   * @category  PHP
   * @package   Adv.accounts.core
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @license   http://www.advancedgroup.com.au/license Advanced Licence
   * @version   GIT:
   * @link      http://www.advancedgroup.com.au
   */
  namespace ADV\Core;
    /**
     * Dates validation and parsing functions
     * These functions refer to the global variable defining the date format
     * The date format is defined in config.php called dateformats
     * this can be a string either "d/m/Y" for UK/Australia/New Zealand dates or
     * "m/d/Y" for US/Canada format dates depending on setting in preferences.
     * @category PHP
     * @package  Adv.accounts.core
     */
  /**
   * @method __date()
   * @method dateToSql($date)
   * @method today()
   * @method now()
   * @method Dates i()
   * @method isDate($date = null, $format = null)
   * @method isDateInFiscalYear($date, $convert = false)
   * @method isGreaterThan($date1, $date2)
   * @method newDocDate()
   * @method addDays($date, $days)
   * @method sqlToDate($date)
   * @method beginMonth($date)
   * @method addMonths($date, $months)
   * @method endFiscalYear()
   * @method beginFiscalYear()
   */
  class Dates
  {
    use Traits\StaticAccess;

    protected $sep = null;
    protected $formats = null;
    protected $separators = null;
    protected $User = null;
    protected $Config = null;
    protected $Session = null;
    protected $Company = null;
    public function __construct(Config $config = null, \User $User = null, Session $Session = null, \DB_Company $Company = null) {
      $config               = $config ? : Config::i();
      $this->User           = $User ? : \User::i();
      $this->Session        = $Session ? : Session::i();
      $this->Company        = $Company ? : \DB_Company::i();
      $this->formats        = $config->_get('date.formats');
      $this->separators     = $config->_get('date.separators');
      $this->userFiscalYear = $config->_get('use_fiscalyear');
      $this->sep            = $this->separators[$config->_get('date.ui_separator')];
    }
    /**
     * @static
     *
     * @param null $date
     * @param null $format
     *
     * @internal param $date
     * @return bool
     */
    public function _isDate($date = null, $format = null) {
      if (!$date) {
        return false;
      }
      $how  = ($format !== null) ? $format : $this->User->_date_format();
      $date = str_replace($this->separators, '/', trim($date));
      if ($how == 0) {
        list($month, $day, $year) = explode('/', $date) + [0=> false, 1=> false, 2=> false];
      } elseif ($how == 1) {
        list($day, $month, $year) = explode('/', $date) + [0=> false, 1=> false, 2=> false];
      } else {
        list($year, $month, $day) = explode('/', $date) + [0=> false, 1=> false, 2=> false];
      }
      if (!isset($year) || (int) $year > 9999) {
        return false;
      }
      if (is_long((int) $day) && is_long((int) $month) && is_long((int) $year)) {
        if (checkdate((int) $month, (int) $day, (int) $year)) {
          return true;
        } else {
          return false;
        }
      } else { /*Can't be in an appropriate DefaultDateFormat */
        return false;
      }
    }
    /**
     * @return string User format of the days date.
     */
    public function _today($sql_format=false) {
      if ($sql_format){
        return date('Y-m-d');

      }
      return $this->date(date("Y"), date("n"), date("j"));
    }
    /**
     * @return string User format of the current time .
     */
    public function _now() {
      if ($this->User->_date_format() == 0) {
        return date("h:i a");
      } else {
        return date("H:i");
      }
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
    /**
     * @static
     *
     * @param $date
     *
     * @return string Date in Users Format
     */
    public function _beginMonth($date) {
      /** @noinspection PhpUnusedLocalVariableInspection */
      list($year, $month, $day) = $this->_explode($date);
      return $this->date($year, $month, 1);
    }
    /**
     * @static
     *
     * @param $date
     *
     * @return string Date in Users Format
     */
    public function _endMonth($date) {
      /** @noinspection PhpUnusedLocalVariableInspection */
      list($year, $month, $day) = $this->_explode($date);
      $days_in_month = array(
        31, ((!($year % 4) && (($year % 100) || !($year % 400))) ? 29 : 28), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31
      );
      return $this->date($year, $month, $days_in_month[$month - 1]);
    }
    /**
     * @static
     *
     * @param $date
     * @param $days
     *
     * @return string Date in Users Format
     */
    public function _addDays($date, $days) {
      list($year, $month, $day) = $this->_explode($date);
      $timet = mktime(0, 0, 0, $month, $day + $days, $year);
      return date($this->User->_date_display(), $timet);
    }
    /**
     * @static
     *
     * @param $date
     * @param $months
     *
     * @return string Date in Users Format
     */
    public function _addMonths($date, $months) {
      list($year, $month, $day) = $this->_explode($date);
      $timet = mktime(0, 0, 0, $month + $months, $day, $year);
      return date($this->User->_date_display(), $timet);
    }
    /**
     * @static
     *
     * @param $date
     * @param $years
     *
     * @return string Date in Users Format
     */
    public function _addYears($date, $years) {
      list($year, $month, $day) = $this->_explode($date);
      $timet = mktime(0, 0, 0, $month, $day, $year + $years);
      return date($this->User->_date_display(), $timet);
    }
    /**
     * @static
     *
     * @param $date
     *
     * @return string Date in Users Format
     */
    public function _sqlToDate($date) {
      //for MySQL dates are in the format YYYY-mm-dd
      if ($date == null || strlen($date) == 0) {
        return "";
      }
      $date = $this->_dateToSql($date);
      $how  = $this->formats[$this->User->_date_format()];
      $date = \DateTime::createFromFormat('Y-m-d', $date);
      return $date->format($how);
    } // end static function sqlToDate
    /**
     * @static
     *
     * @param $date
     *
     * @internal param bool $pad
     * @return string Date in SQL ISO Format
     */
    public function _dateToSql($date) {
      if (!$date) {
        return $this->_today(true);
      }
      if (strlen($date) > 10) {
        $date = substr($date, 0, 10);
      }
      $parts = explode('-', $date);
      if (count($parts) == 3 && checkdate($parts[1], $parts[2], $parts[0])) {
        return $date;
      }
      $how  = $this->formats[$this->User->_date_format()];
      $date = \DateTime::createFromFormat($how, $date);
      return $date->format('Y-m-d');
    }
    /**
     * @static
     *
     * @param $date1
     * @param $date2
     *
     * @return int|bool
     */
    public function _isGreaterThan($date1, $date2) {
      /* returns 1 true if date1 is greater than date_ 2 */
      if (!$date1 || !$date2) {
        return false;
      }

      return ($this->_differenceBetween($date1, $date2) >= 0);
    }
    /**
     * @static
     *
     * @param $date1
     * @param $date2
     * @param $period
     *
     * @return int
     */
    public function _differenceBetween($date1, $date2, $period = 'd') {
      /* expects dates in the format specified in $DefaultDateFormat - period can be one of 'd','w','y','m'
months are assumed to be 30 days and years 365.25 days This only works
provided that both dates are after 1970. Also only works for dates up to the year 2035 ish */
      $date1    = new \DateTime($this->_dateToSql($date1));
      $date2    = new \DateTime($this->_dateToSql($date2));
      $interval = $date2->diff($date1);
      switch ($period) {
        case 'd':
          return $interval->format('%r%d');
        case 'w':
          return floor($interval->format('%r%d') / 7);
        case 'y':
          return $interval->format('%r%y');
        case 'm':
          return $interval->format('%r%m');
      }
    }
    /**
     * @static
     *
     * @param $date
     *
     * @throws \Exception
     * @internal param $date
     * @return array [year,month,day]
     */
    protected function _explode($date) {
      $date = $this->_dateToSql($date);
      return explode("-", $date);
    }
    /** Based on converter to and from Gregorian and Jalali calendars.
    Copyright (C) 2000 Roozbeh Pournader and Mohammad Toossi
    Released under GNU General Public License
     * @static
     *
     * @param $g_y
     * @param $g_m
     * @param $g_d
     *
     * @return array
     */
    public function _gregorianToJalai($g_y, $g_m, $g_d) {
      $g_days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
      $j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);
      $gy              = $g_y - 1600;
      $gm              = $g_m - 1;
      $gd              = $g_d - 1;
      $g_day_no        = 365 * $gy + $this->div($gy + 3, 4) - $this->div($gy + 99, 100) + $this->div($gy + 399, 400);
      for ($i = 0; $i < $gm; ++$i) {
        $g_day_no += $g_days_in_month[$i];
      }
      if ($gm > 1 && (($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0))
      ) /* leap and after Feb */ {
        $g_day_no++;
      }
      $g_day_no += $gd;
      $j_day_no = $g_day_no - 79;
      $j_np     = $this->div($j_day_no, 12053); /* 12053 = 365*33 + 32/4 */
      $j_day_no %= 12053;
      $jy = 979 + 33 * $j_np + 4 * $this->div($j_day_no, 1461); /* 1461 = 365*4 + 4/4 */
      $j_day_no %= 1461;
      if ($j_day_no >= 366) {
        $jy += $this->div($j_day_no - 1, 365);
        $j_day_no = ($j_day_no - 1) % 365;
      }
      for ($i = 0; $i < 11 && $j_day_no >= $j_days_in_month[$i]; ++$i) {
        $j_day_no -= $j_days_in_month[$i];
      }
      $jm = $i + 1;
      $jd = $j_day_no + 1;
      return array($jy, $jm, $jd);
    }
    /**
     * @static
     *
     * @param $j_y
     * @param $j_m
     * @param $j_d
     *
     * @return array
     */
    public function _jalaiToGregorian($j_y, $j_m, $j_d) {
      $g_days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
      $j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);
      $jy              = $j_y - 979;
      $jm              = $j_m - 1;
      $jd              = $j_d - 1;
      $j_day_no        = 365 * $jy + $this->div($jy, 33) * 8 + $this->div($jy % 33 + 3, 4);
      for ($i = 0; $i < $jm; ++$i) {
        $j_day_no += $j_days_in_month[$i];
      }
      $j_day_no += $jd;
      $g_day_no = $j_day_no + 79;
      $gy       = 1600 + 400 * $this->div($g_day_no, 146097); /* 146097 = 365*400 + 400/4 - 400/100 + 400/400 */
      $g_day_no %= 146097;
      $leap = true;
      if ($g_day_no >= 36525) /* 36525 = 365*100 + 100/4 */ {
        $g_day_no--;
        $gy += 100 * $this->div($g_day_no, 36524); /* 36524 = 365*100 + 100/4 - 100/100 */
        $g_day_no %= 36524;
        if ($g_day_no >= 365) {
          $g_day_no++;
        } else {
          $leap = false;
        }
      }
      $gy += 4 * $this->div($g_day_no, 1461); /* 1461 = 365*4 + 4/4 */
      $g_day_no %= 1461;
      if ($g_day_no >= 366) {
        $leap = false;
        $g_day_no--;
        $gy += $this->div($g_day_no, 365);
        $g_day_no %= 365;
      }
      for ($i = 0; $g_day_no >= $g_days_in_month[$i] + ($i == 1 && $leap); $i++) {
        $g_day_no -= $g_days_in_month[$i] + ($i == 1 && $leap);
      }
      $gm = $i + 1;
      $gd = $g_day_no + 1;
      return array($gy, $gm, $gd);
    }
    /**
     * @static
     *
     * @param     $name
     * @param int $month
     *
     * @return string
     */
    public function _months($name, $month = 0) {
      $months[-1] = 'Current';
      for ($i = 0; $i < 11; $i++) {
        $months[$i] = date('F', strtotime("now - $i months"));
      }
      return \Forms::arraySelect($name, $month, $months);
    }
    /**
     * @static
     *
     * @param $g_y
     * @param $g_m
     * @param $g_d
     *
     * @return array
     */
    public function _gregorianToIslamic($g_y, $g_m, $g_d) {
      $y = $g_y;
      $m = $g_m;
      $d = $g_d;
      if (($y > 1582) || (($y == 1582) && ($m > 10)) || (($y == 1582) && ($m == 10) && ($d > 14))) {
        $jd = (int) ((1461 * ($y + 4800 + (int) (($m - 14) / 12))) / 4) + (int) ((367 * ($m - 2 - 12 * ((int) (($m - 14) / 12)))) / 12) - (int) ((3 * ((int) (($y + 4900 + (int) (($m - 14) / 12)) / 100))) / 4) + $d - 32075;
      } else {
        $jd = 367 * $y - (int) ((7 * ($y + 5001 + (int) (($m - 9) / 7))) / 4) + (int) ((275 * $m) / 9) + $d + 1729777;
      }
      $l = $jd - 1948440 + 10632;
      $n = (int) (($l - 1) / 10631);
      $l = $l - 10631 * $n + 354;
      $j = ((int) ((10985 - $l) / 5316)) * ((int) ((50 * $l) / 17719)) + ((int) ($l / 5670)) * ((int) ((43 * $l) / 15238));
      $l = $l - ((int) ((30 - $j) / 15)) * ((int) ((17719 * $j) / 50)) - ((int) ($j / 16)) * ((int) ((15238 * $j) / 43)) + 29;
      $m = (int) ((24 * $l) / 709);
      $d = $l - (int) ((709 * $m) / 24);
      $y = 30 * $n + $j - 30;
      return array($y, $m, $d);
    }
    /**
     * @static
     *
     * @param $i_y
     * @param $i_m
     * @param $i_d
     *
     * @return array
     */
    public function _islamicToGregorian($i_y, $i_m, $i_d) {
      $y  = $i_y;
      $m  = $i_m;
      $d  = $i_d;
      $jd = (int) ((11 * $y + 3) / 30) + 354 * $y + 30 * $m - (int) (($m - 1) / 2) + $d + 1948440 - 385;
      if ($jd > 2299160) {
        $l = $jd + 68569;
        $n = (int) ((4 * $l) / 146097);
        $l = $l - (int) ((146097 * $n + 3) / 4);
        $i = (int) ((4000 * ($l + 1)) / 1461001);
        $l = $l - (int) ((1461 * $i) / 4) + 31;
        $j = (int) ((80 * $l) / 2447);
        $d = $l - (int) ((2447 * $j) / 80);
        $l = (int) ($j / 11);
        $m = $j + 2 - 12 * $l;
        $y = 100 * ($n - 49) + $i + $l;
      } else {
        $j = $jd + 1402;
        $k = (int) (($j - 1) / 1461);
        $l = $j - 1461 * $k;
        $n = (int) (($l - 1) / 365) - (int) ($l / 1461);
        $i = $l - 365 * $n + 30;
        $j = (int) ((80 * $i) / 2447);
        $d = $i - (int) ((2447 * $j) / 80);
        $i = (int) ($j / 11);
        $m = $j + 2 - 12 * $i;
        $y = 4 * $k + $n + $i - 4716;
      }
      return array($y, $m, $d);
    }
    /**
     * @static
     *
     * @param $time
     *
     * @return float|string
     */
    public static function getReadableTime($time) {
      $ret       = $time;
      $formatter = 0;
      $formats   = array('ms', 's', 'm');
      if ($time >= 1000 && $time < 60000) {
        $formatter = 1;
        $ret       = ($time / 1000);
      }
      if ($time >= 60000) {
        $formatter = 2;
        $ret       = ($time / 1000) / 60;
      }
      $ret = number_format($ret, 3, '.', '') . ' ' . $formats[$formatter];
      return $ret;
    }
    /**
     * @static
     *
     * @param $a
     * @param $b
     *
     * @return int
     */
    protected function div($a, $b) {
      return (int) ($a / $b);
    }
    /**
     * @static
     *
     * @param      $year
     * @param      $month
     * @param      $day
     * @param null $format
     *
     * @return string
     */
    protected function date($year, $month, $day, $format = null) {
      $how  = $this->formats [($format !== null) ? $format : $this->User->_date_format()];
      $date = mktime(0, 0, 0, (int) $month, (int) $day, (int) $year);
      $how  = str_replace('/', $this->sep, $how);
      return date($how, $date);
    }
  }
