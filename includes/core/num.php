<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 6/11/11
	 * Time: 2:53 AM
	 * To change this template use File | Settings | File Templates.
	 */
	class Num
	{
		public static function  price_format($number)
		{
			return Num::format($number, User::prefs()->price_dec());
		}

		public static function  price_decimal($number, &$dec)
		{
			$dec = User::price_dec();
			$str = strval($number);
			$pos = strpos($str, '.');
			if ($pos !== false) {
				$len = strlen(substr($str, $pos + 1));
				if ($len > $dec) {
					$dec = $len;
				}
			}
			return Num::format($number, $dec);
		}

		public static function  round($number, $decimals = 0)
		{
			return round($number, $decimals, PHP_ROUND_HALF_EVEN);
		}

		public static function  format($number, $decimals = 0)
		{
			$tsep = Config::get('separators_thousands', User::prefs()->tho_sep());
			$dsep = Config::get('separators_decimal', User::prefs()->dec_sep());
			//return number_format($number, $decimals, $dsep,	$tsep);
			$delta = ($number < 0 ? -.0000000001 : .0000000001);
			$number = number_format($number + $delta, $decimals, $dsep, $tsep);
			return ($number == -0 ? 0 : $number);
		}

		//	Current ui mode.
		// 2008-06-15. Added extra parameter $stock_id and reference for $dec

		public static function  qty_format($number, $stock_id = null, &$dec)
		{
			$dec = Num::qty_dec($stock_id);
			return Num::format($number, $dec);
		}

		// and get_qty_dec
		public static function  qty_dec($stock_id = null)
		{
			if (is_null($stock_id)) {
				$dec = User::prefs()->qty_dec();
			} else  {
				$dec = Item_Unit::get_decimal($stock_id);
			}
			return $dec;
		}


		public static function  exrate_format($number)
		{
			return Num::format($number, User::prefs()->exrate_dec());
		}

		public static function  percent_format($number)
		{
			return Num::format($number, User::prefs()->percent_dec());
		}


		public static 	function round_to_nearest($price, $round_to)
			{
				if ($price == 0) {
					return 0;
				}
				$pow = pow(10, User::price_dec());
				if ($pow >= $round_to) {
					$mod = ($pow % $round_to);
				} else {
					$mod = ($round_to % $pow);
				}
				if ($mod != 0) {
					$price = ceil($price) - ($pow - $round_to) / $pow;
				} else {
					$price = ceil($price * ($pow / $round_to)) / ($pow / $round_to);
				}
				return $price;
			}

	}
