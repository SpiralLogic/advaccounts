<?php
	/**********************************************************************
	Copyright (C) Advanced Group PTY LTD
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
	 ***********************************************************************/
	/*
	 date validation and parsing functions

	 These functions refer to the global variable defining the date format
	 The date format is defined in config.php called dateformats
	 this can be a string either "d/m/Y" for UK/Australia/New Zealand dates or
	 "m/d/Y" for US/Canada format dates depending on setting in preferences.

	 */
	if (function_exists("date_default_timezone_set") && function_exists("date_default_timezone_get")) {
		date_default_timezone_set(date_default_timezone_get());
	}
	/**
	 *
	 */
	class Dates
	{
		/**
		 * @static
		 *
		 * @param $year
		 * @param $month
		 * @param $day
		 *
		 * @return string
		 */
		static function __date($year, $month, $day) {
			$how = User::date_format();
			$sep = Config::get('separators_date', 0);
			$day = (int)$day;
			$month = (int)$month;
			if ($day < 10) {
				$day = "0" . $day;
			}
			if ($month < 10) {
				$month = "0" . $month;
			}
			if ($how == 0) {
				return $month . $sep . $day . $sep . $year;
			}
			else if ($how == 1) {
				return $day . $sep . $month . $sep . $year;
			} else {
				return $year . $sep . $month . $sep . $day;
			}
		}

		/**
		 * @static
		 *
		 * @param $date_
		 *
		 * @return int
		 */
		static function is_date($date_) {
			if ($date_ == null || $date_ == "") {
				return 0;
			}
			$how = User::date_format();
			$sep = Config::get('separators_date', 0);
			$date_ = trim($date_);
			$date_ = str_replace($sep, "", $date_);
			$day = $month = $year = 0;
			if (strlen($date_) == 6) {
				if ($how == 0) {
					$day = substr($date_, 2, 2);
					$month = substr($date_, 0, 2);
					$year = substr($date_, 4, 2);
				}
				elseif ($how == 1) {
					$day = substr($date_, 0, 2);
					$month = substr($date_, 2, 2);
					$year = substr($date_, 4, 2);
				}
				else {
					$day = substr($date_, 4, 2);
					$month = substr($date_, 2, 2);
					$year = substr($date_, 0, 2);
				}
			}
			elseif (strlen($date_) == 8) {
				if ($how == 0) {
					$day = substr($date_, 2, 2);
					$month = substr($date_, 0, 2);
					$year = substr($date_, 4, 4);
				}
				elseif ($how == 1) {
					$day = substr($date_, 0, 2);
					$month = substr($date_, 2, 2);
					$year = substr($date_, 4, 4);
				}
				else {
					$day = substr($date_, 6, 2);
					$month = substr($date_, 4, 2);
					$year = substr($date_, 0, 4);
				}
			}
			if (!isset($year) || (int)$year > 9999) {
				return 0;
			}
			if (is_long((int)$day) && is_long((int)$month) && is_long((int)$year)) {
				if (Config::get('accounts_datesystem') == 1) {
					list($year, $month, $day) = Dates::jalali_to_gregorian($year, $month, $day);
				}
				else if (Config::get('accounts_datesystem') == 2) {
					list($year, $month, $day) = Dates::islamic_to_gregorian($year, $month, $day);
				}
				if (checkdate((int)$month, (int)$day, (int)$year)) {
					return 1;
				}
				else {
					return 0;
				}
			}
			else { /*Can't be in an appropriate DefaultDateFormat */
				return 0;
			}
		}

		//end of is_date function
		/**
		 * @static
		 * @return string
		 */
		static function Today() {
			$year = date("Y");
			$month = date("n");
			$day = date("j");
			if (Config::get('accounts_datesystem') == 1) {
				list($year, $month, $day) = Dates::gregorian_to_jalali($year, $month, $day);
			}
			else if (Config::get('accounts_datesystem') == 2) {
				list($year, $month, $day) = Dates::gregorian_to_islamic($year, $month, $day);
			}
			return Dates::__date($year, $month, $day);
		}

		/**
		 * @static
		 * @return string
		 */
		static function Now() {
			if (User::date_format() == 0) {
				return date("h:i a");
			} else {
				return date("H:i");
			}
		}

		/**
		 *	Retrieve and optionaly set default date for new document.
		 *
		 * @param null $date
		 *
		 * @return
		 */
		static function new_doc_date($date = null) {
			if (isset($date) && $date != '') {
				$_SESSION['_default_date'] = $date;
			}
			if (!isset($_SESSION['_default_date']) || !User::sticky_date()) {
				$_SESSION['_default_date'] = Dates::Today();
			}
			return $_SESSION['_default_date'];
		}

		/**
		 * @static
		 *
		 * @param			$date
		 * @param bool $convert
		 *
		 * @return int
		 */
		static function is_date_in_fiscalyear($date, $convert = false) {
			if (!Config::get('use_fiscalyear')) {
				return 1;
			}
			$myrow = DB_Company::get_current_fiscalyear();
			if ($myrow['closed'] == 1) {
				return 0;
			}
			if ($convert) {
				$date2 = Dates::sql2date($date);
			} else {
				$date2 = $date;
			}
			$begin = Dates::sql2date($myrow['begin']);
			$end = Dates::sql2date($myrow['end']);
			if (Dates::date1_greater_date2($begin, $date2) || Dates::date1_greater_date2($date2, $end)) {
				return 0;
			}
			return 1;
		}

		/**
		 * @static
		 * @return string
		 */
		static function begin_fiscalyear() {
			$myrow = DB_Company::get_current_fiscalyear();
			return Dates::sql2date($myrow['begin']);
		}

		/**
		 * @static
		 * @return string
		 */
		static function end_fiscalyear() {
			$myrow = DB_Company::get_current_fiscalyear();
			return Dates::sql2date($myrow['end']);
		}

		/**
		 * @static
		 *
		 * @param $date
		 *
		 * @return string
		 */
		static function begin_month($date) {
			list($day, $month, $year) = Dates::explode_date_to_dmy($date);
			if (Config::get('accounts_datesystem') == 1) {
				/** @noinspection PhpUnusedLocalVariableInspection */
				list($year, $month, $day) = Dates::gregorian_to_jalali($year, $month, $day);
			}
			else if (Config::get('accounts_datesystem') == 2) {
				/** @noinspection PhpUnusedLocalVariableInspection */
				list($year, $month, $day) = Dates::gregorian_to_islamic($year, $month, $day);
			}
			return Dates::__date($year, $month, 1);
		}

		/**
		 * @static
		 *
		 * @param $date
		 *
		 * @return string
		 */
		static function end_month($date) {
			list($day, $month, $year) = Dates::explode_date_to_dmy($date);
			if (Config::get('accounts_datesystem') == 1) {
				/** @noinspection PhpUnusedLocalVariableInspection */
				list($year, $month, $day) = Dates::gregorian_to_jalali($year, $month, $day);
				$days_in_month = array(
					31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30,
					((((((($year - (($year > 0) ? 474 : 473)) % 2820) + 474) + 38) * 682) % 2816) < 682 ? 30
					 : 29)
				);
			}
			else if (Config::get('accounts_datesystem') == 2) {
				/** @noinspection PhpUnusedLocalVariableInspection */
				list($year, $month, $day) = Dates::gregorian_to_islamic($year, $month, $day);
				$days_in_month
				 = array(30, 29, 30, 29, 30, 29, 30, 29, 30, 29, 30, (((((11 * $year) + 14) % 30) < 11) ? 30 : 29));
			}
			else // gregorian date
			{
				$days_in_month
				 = array(
					31, ((!($year % 4) && (($year % 100) || !($year % 400))) ? 29 : 28), 31, 30, 31, 30, 31, 31, 30,
					31, 30, 31
				);
			}
			return Dates::__date($year, $month, $days_in_month[$month - 1]);
		}

		/**
		 * @static
		 *
		 * @param $date
		 * @param $days
		 *
		 * @return string
		 */
		static function add_days($date, $days) {
			list($day, $month, $year) = Dates::explode_date_to_dmy($date);
			$timet = Mktime(0, 0, 0, $month, $day + $days, $year);
			if (Config::get('accounts_datesystem') == 1 || Config::get('accounts_datesystem') == 2) {
				if (Config::get('accounts_datesystem') == 1) {
					list($year, $month, $day) = Dates::gregorian_to_jalali(date("Y", $timet), date("n", $timet), date("j", $timet));
				}
				else if (Config::get('accounts_datesystem') == 2) {
					list($year, $month, $day) = Dates::gregorian_to_islamic(date("Y", $timet), date("n", $timet), date("j", $timet));
				}
				return Dates::__date($year, $month, $day);
			}
			return date(User::date_display(), $timet);
		}

		/**
		 * @static
		 *
		 * @param $date
		 * @param $months
		 *
		 * @return string
		 */
		static function add_months($date, $months) {
			list($day, $month, $year) = Dates::explode_date_to_dmy($date);
			$timet = Mktime(0, 0, 0, $month + $months, $day, $year);
			if (Config::get('accounts_datesystem') == 1 || Config::get('accounts_datesystem') == 2) {
				if (Config::get('accounts_datesystem') == 1) {
					list($year, $month, $day) = Dates::gregorian_to_jalali(date("Y", $timet), date("n", $timet), date("j", $timet));
				}
				else if (Config::get('accounts_datesystem') == 2) {
					list($year, $month, $day) = Dates::gregorian_to_islamic(date("Y", $timet), date("n", $timet), date("j", $timet));
				}
				return Dates::__date($year, $month, $day);
			}
			return date(User::date_display(), $timet);
		}

		/**
		 * @static
		 *
		 * @param $date
		 * @param $years
		 *
		 * @return string
		 */
		static function add_years($date, $years) {
			list($day, $month, $year) = Dates::explode_date_to_dmy($date);
			$timet = Mktime(0, 0, 0, $month, $day, $year + $years);
			if (Config::get('accounts_datesystem') == 1 || Config::get('accounts_datesystem') == 2) {
				if (Config::get('accounts_datesystem') == 1) {
					list($year, $month, $day) = Dates::gregorian_to_jalali(date("Y", $timet), date("n", $timet), date("j", $timet));
				}
				else if (Config::get('accounts_datesystem') == 2) {
					list($year, $month, $day) = Dates::gregorian_to_islamic(date("Y", $timet), date("n", $timet), date("j", $timet));
				}
				return Dates::__date($year, $month, $day);
			}
			return date(User::date_display(), $timet);
		}

		//_______________________________________________________________
		/**
		 * @static
		 *
		 * @param $date_
		 *
		 * @return string
		 */
		static function sql2date($date_) {
			//for MySQL dates are in the format YYYY-mm-dd
			if ($date_ == null || strlen($date_) == 0) {
				return "";
			}
			$year = $month = $day = '';
			if (strpos($date_, "/")) { // In MySQL it could be either / or -
				list($year, $month, $day) = explode("/", $date_);
			}
			elseif (strpos($date_, "-")) {
				list($year, $month, $day) = explode("-", $date_);
			}
			if (!isset($date) && strlen($day) > 4) { /*chop off the time stuff */
				$day = substr($day, 0, 2);
			}
			if (Config::get('accounts_datesystem') == 1) {
				list($year, $month, $day) = Dates::gregorian_to_jalali($year, $month, $day);
			}
			else if (Config::get('accounts_datesystem') == 2) {
				list($year, $month, $day) = Dates::gregorian_to_islamic($year, $month, $day);
			}
			return Dates::__date($year, $month, $day);
		} // end static function sql2date
		/**
		 * @static
		 *
		 * @param			$date_
		 * @param bool $pad
		 *
		 * @return int|string
		 */
		static function date2sql($date_, $pad = true) {
			/* takes a date in a the format specified in $DefaultDateFormat
																	and converts to a yyyy/mm/dd format */
			$how = User::date_format();
			$sep = Config::get('separators_date', User::date_sep());
			if ($date_ == null || strlen($date_) == 0) {
				return "";
			}
			$date_ = trim($date_);
			/** @noinspection PhpUnusedLocalVariableInspection */
			$year = $month = $day = 0;
			// Split up the date by the separator based on "how" to split it
			if ($how == 0) // MMDDYYYY
			{
				list($month, $day, $year) = explode($sep, $date_);
			}
			elseif ($how == 1) // DDMMYYYY
			{
				list($day, $month, $year) = explode($sep, $date_);
			}
			else // $how == 2, YYYYMMDD
			{
				list($year, $month, $day) = explode($sep, $date_);
			}
			//to modify assumption in 2030
			if (Config::get('accounts_datesystem') == 0 || Config::get('accounts_datesystem') == 3) {
				if ((int)$year < 60) {
					$year = "20" . $year;
				}
				elseif ((int)$year > 59 && (int)$year < 100) {
					$year = "19" . $year;
				}
			}
			if ((int)$year > 9999) {
				return 0;
			}
			if (Config::get('accounts_datesystem') == 1) {
				list($year, $month, $day) = Dates::jalali_to_gregorian($year, $month, $day);
			}
			else if (Config::get('accounts_datesystem') == 2) {
				list($year, $month, $day) = Dates::islamic_to_gregorian($year, $month, $day);
			}
			// Pad with 0s if needed
			if (strlen($month) == 1) {
				$month = "0$month";
			}
			if (strlen($day) == 1) {
				$day = "0$day";
			}
			if ($pad) {
				return $year . "-" . $month . "-" . $day;
			}
			$return = (($year != 20) ? $year . '-' : '');
			$return .= (($month > 0) ? $month . '-' : '');
			$return .= ($day > 0) ? $day : '';
			return $return;
		}

		// end of function
		/**
		 * @static
		 *
		 * @param $date1
		 * @param $date2
		 *
		 * @return int
		 */
		static function date1_greater_date2($date1, $date2) {
			/* returns 1 true if date1 is greater than date_ 2 */
			$date1 = Dates::date2sql($date1);
			$date2 = Dates::date2sql($date2);
			list($year1, $month1, $day1) = explode("-", $date1);
			list($year2, $month2, $day2) = explode("-", $date2);
			if ($year1 > $year2) {
				return 1;
			}
			elseif ($year1 == $year2) {
				if ($month1 > $month2) {
					return 1;
				}
				elseif ($month1 == $month2) {
					if ($day1 > $day2) {
						return 1;
					}
				}
			}
			return 0;
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
		static function date_diff2($date1, $date2, $period) {
			/* expects dates in the format specified in $DefaultDateFormat - period can be one of 'd','w','y','m'
																	months are assumed to be 30 days and years 365.25 days This only works
																	provided that both dates are after 1970. Also only works for dates up to the year 2035 ish */
			$date1 = Dates::date2sql($date1);
			$date2 = Dates::date2sql($date2);
			list($year1, $month1, $day1) = explode("-", $date1);
			list($year2, $month2, $day2) = explode("-", $date2);
			$stamp1 = mktime(0, 0, 0, (int)$month1, (int)$day1, (int)$year1);
			$stamp2 = mktime(0, 0, 0, (int)$month2, (int)$day2, (int)$year2);
			$difference = $stamp1 - $stamp2;
			/* difference is the number of seconds between each date negative if date_ 2 > date_ 1 */
			switch ($period) {
				case "d":
					return (int)($difference / (24 * 60 * 60));
				case "w":
					return (int)($difference / (24 * 60 * 60 * 7));
				case "m":
					return (int)($difference / (24 * 60 * 60 * 30));
				case "s":
					return $difference;
				case "y":
					return (int)($difference / (24 * 60 * 60 * 365.25));
				default:
					Return 0;
			}
		}

		/**
		 * @static
		 *
		 * @param $date_
		 *
		 * @return array
		 * @throws Adv_Exception
		 */
		static function explode_date_to_dmy($date_) {
			$date = Dates::date2sql($date_);
			if ($date == "") {
				$disp = User::date_display();
				throw new Adv_Exception("Dates must be entered in the format $disp. Sent was $date");
			}
			list($year, $month, $day) = explode("-", $date);
			return array($day, $month, $year);
		}

		/**
		 * @static
		 *
		 * @param $a
		 * @param $b
		 *
		 * @return int
		 */
		static function div($a, $b) {
			return (int)($a / $b);
		}

		/* Based on convertor to and from Gregorian and Jalali calendars.
						Copyright (C) 2000  Roozbeh Pournader and Mohammad Toossi
						Released under GNU General Public License */
		/**
		 * @static
		 *
		 * @param $g_y
		 * @param $g_m
		 * @param $g_d
		 *
		 * @return array
		 */
		static function gregorian_to_jalali($g_y, $g_m, $g_d) {
			$g_days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
			$j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);
			$gy = $g_y - 1600;
			$gm = $g_m - 1;
			$gd = $g_d - 1;
			$g_day_no = 365 * $gy + static::div($gy + 3, 4) - static::div($gy + 99, 100) + static::div($gy + 399, 400);
			for (
				$i = 0; $i < $gm; ++$i
			)
			{
				$g_day_no += $g_days_in_month[$i];
			}
			if ($gm > 1 && (($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0))
			) /* leap and after Feb */ {
				$g_day_no++;
			}
			$g_day_no += $gd;
			$j_day_no = $g_day_no - 79;
			$j_np = static::div($j_day_no, 12053); /* 12053 = 365*33 + 32/4 */
			$j_day_no %= 12053;
			$jy = 979 + 33 * $j_np + 4 * static::div($j_day_no, 1461); /* 1461 = 365*4 + 4/4 */
			$j_day_no %= 1461;
			if ($j_day_no >= 366) {
				$jy += static::div($j_day_no - 1, 365);
				$j_day_no = ($j_day_no - 1) % 365;
			}
			for (
				$i = 0; $i < 11 && $j_day_no >= $j_days_in_month[$i]; ++$i
			)
			{
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
		static function jalali_to_gregorian($j_y, $j_m, $j_d) {
			$g_days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
			$j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);
			$jy = $j_y - 979;
			$jm = $j_m - 1;
			$jd = $j_d - 1;
			$j_day_no = 365 * $jy + static::div($jy, 33) * 8 + static::div($jy % 33 + 3, 4);
			for (
				$i = 0; $i < $jm; ++$i
			)
			{
				$j_day_no += $j_days_in_month[$i];
			}
			$j_day_no += $jd;
			$g_day_no = $j_day_no + 79;
			$gy = 1600 + 400 * static::div($g_day_no, 146097); /* 146097 = 365*400 + 400/4 - 400/100 + 400/400 */
			$g_day_no %= 146097;
			$leap = true;
			if ($g_day_no >= 36525) /* 36525 = 365*100 + 100/4 */ {
				$g_day_no--;
				$gy += 100 * static::div($g_day_no, 36524); /* 36524 = 365*100 + 100/4 - 100/100 */
				$g_day_no %= 36524;
				if ($g_day_no >= 365) {
					$g_day_no++;
				} else {
					$leap = false;
				}
			}
			$gy += 4 * static::div($g_day_no, 1461); /* 1461 = 365*4 + 4/4 */
			$g_day_no %= 1461;
			if ($g_day_no >= 366) {
				$leap = false;
				$g_day_no--;
				$gy += static::div($g_day_no, 365);
				$g_day_no %= 365;
			}
			for (
				$i = 0; $g_day_no >= $g_days_in_month[$i] + ($i == 1 && $leap); $i++
			)
			{
				$g_day_no -= $g_days_in_month[$i] + ($i == 1 && $leap);
			}
			$gm = $i + 1;
			$gd = $g_day_no + 1;
			return array($gy, $gm, $gd);
		}

		/* Based on Hidri Date Script
						Released under GNU General Public License */
		/**
		 * @static
		 *
		 * @param $g_y
		 * @param $g_m
		 * @param $g_d
		 *
		 * @return array
		 */
		static function gregorian_to_islamic($g_y, $g_m, $g_d) {
			$y = $g_y;
			$m = $g_m;
			$d = $g_d;
			if (($y > 1582) || (($y == 1582) && ($m > 10)) || (($y == 1582) && ($m == 10) && ($d > 14))) {
				$jd = (int)((1461 * ($y + 4800 + (int)(($m - 14) / 12))) / 4) + (int)((367 * ($m - 2 - 12 * ((int)(($m - 14) / 12)))) / 12) - (int)((3 * ((int)(($y + 4900 + (int)(($m - 14) / 12)) / 100))) / 4) + $d - 32075;
			}
			else {
				$jd = 367 * $y - (int)((7 * ($y + 5001 + (int)(($m - 9) / 7))) / 4) + (int)((275 * $m) / 9) + $d + 1729777;
			}
			$l = $jd - 1948440 + 10632;
			$n = (int)(($l - 1) / 10631);
			$l = $l - 10631 * $n + 354;
			$j = ((int)((10985 - $l) / 5316)) * ((int)((50 * $l) / 17719)) + ((int)($l / 5670)) * ((int)((43 * $l) / 15238));
			$l = $l - ((int)((30 - $j) / 15)) * ((int)((17719 * $j) / 50)) - ((int)($j / 16)) * ((int)((15238 * $j) / 43)) + 29;
			$m = (int)((24 * $l) / 709);
			$d = $l - (int)((709 * $m) / 24);
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
		static function islamic_to_gregorian($i_y, $i_m, $i_d) {
			$y = $i_y;
			$m = $i_m;
			$d = $i_d;
			$jd = (int)((11 * $y + 3) / 30) + 354 * $y + 30 * $m - (int)(($m - 1) / 2) + $d + 1948440 - 385;
			if ($jd > 2299160) {
				$l = $jd + 68569;
				$n = (int)((4 * $l) / 146097);
				$l = $l - (int)((146097 * $n + 3) / 4);
				$i = (int)((4000 * ($l + 1)) / 1461001);
				$l = $l - (int)((1461 * $i) / 4) + 31;
				$j = (int)((80 * $l) / 2447);
				$d = $l - (int)((2447 * $j) / 80);
				$l = (int)($j / 11);
				$m = $j + 2 - 12 * $l;
				$y = 100 * ($n - 49) + $i + $l;
			}
			else {
				$j = $jd + 1402;
				$k = (int)(($j - 1) / 1461);
				$l = $j - 1461 * $k;
				$n = (int)(($l - 1) / 365) - (int)($l / 1461);
				$i = $l - 365 * $n + 30;
				$j = (int)((80 * $i) / 2447);
				$d = $i - (int)((2447 * $j) / 80);
				$i = (int)($j / 11);
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
			$ret = $time;
			$formatter = 0;
			$formats = array('ms', 's', 'm');
			if ($time >= 1000 && $time < 60000) {
				$formatter = 1;
				$ret = ($time / 1000);
			}
			if ($time >= 60000) {
				$formatter = 2;
				$ret = ($time / 1000) / 60;
			}
			$ret = number_format($ret, 3, '.', '') . ' ' . $formats[$formatter];
			return $ret;
		}
	}

?>
