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
		function price_format($number)
		{
			return Num::format($number, User::prefs()->price_dec());
		}

		function price_decimal($number, &$dec)
		{
			$dec = user_price_dec();
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

		function round($number, $decimals = 0)
		{
			return round($number, $decimals, PHP_ROUND_HALF_EVEN);
		}

		function format($number, $decimals = 0)
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
		//--------------------------------------------------------------------
		function qty_format($number, $stock_id = null, &$dec)
		{
			$dec = get_qty_dec($stock_id);
			return Num::format($number, $dec);
		}

		// and get_qty_dec
		function get_qty_dec($stock_id = null)
		{
			if ($stock_id != null) {
				$dec = Item_Unit::get_decimal($stock_id);
			}
			if ($stock_id == null || $dec == -1 || $dec == null) {
				$dec = User::prefs()->qty_dec();
			}
			return $dec;
		}

		//-------------------------------------------------------------------
		function exrate_format($number)
		{
			return Num::format($number, User::prefs()->exrate_dec());
		}

		function percent_format($number)
		{
			return Num::format($number, User::prefs()->percent_dec());
		}
	}
