<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 6/11/11
	 * Time: 2:53 AM
	 * To change this template use File | Settings | File Templates.
	 */
	class Num {
		/**
		 * @static
		 *
		 * @param $number
		 *
		 * @return int|string
		 */
		static public function	price_format($number) {
			return Num::format(Num::round($number, User::prefs()->price_dec()), User::prefs()->price_dec());
		}

		/**
		 * @static
		 *
		 * @param $number
		 * @param $dec
		 *
		 * @return int|string
		 */
		static public function	price_decimal($number, &$dec) {
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

		/**
		 * @static
		 *
		 * @param		 $number
		 * @param int $decimals
		 *
		 * @return float
		 */
		static public function	round($number, $decimals = 0) {
			return round($number, $decimals, PHP_ROUND_HALF_EVEN);
		}

		/**
		 * @static
		 *
		 * @param		 $number
		 * @param int $decimals
		 *
		 * @return int|string
		 */
		static public function	format($number, $decimals = 0) {
			$tsep = Config::get('separators_thousands', User::prefs()->tho_sep());
			$dsep = Config::get('separators_decimal', User::prefs()->dec_sep());
			//return number_format($number, $decimals, $dsep,	$tsep);
			$delta = ($number < 0 ? -.0000000001 : .0000000001);
			$number = number_format($number + $delta, $decimals, $dsep, $tsep);
			return ($number == -0 ? 0 : $number);
		}

		/**
		 * @static
		 *
		 * @param $number
		 *
		 * @return int|string
		 */
		static public function	exrate_format($number) {
			return Num::format($number, User::prefs()->exrate_dec());
		}

		/**
		 * @static
		 *
		 * @param $number
		 *
		 * @return int|string
		 */
		static public function	percent_format($number) {
			return Num::format($number, User::prefs()->percent_dec());
		}

		/**
		 * @static
		 *
		 * @param $price
		 * @param $round_to
		 *
		 * @return float|int
		 */
		static public function round_to_nearest($price, $round_to) {
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

		/**
		 * @static
		 *
		 * @param $number
		 *
		 * @return string
		 * Simple English version of number to words conversion.

		 */
		static public function to_words($number) {
			$Bn = floor($number / 1000000000); /* Billions (giga) */
			$number -= $Bn * 1000000000;
			$Gn = floor($number / 1000000); /* Millions (mega) */
			$number -= $Gn * 1000000;
			$kn = floor($number / 1000); /* Thousands (kilo) */
			$number -= $kn * 1000;
			$Hn = floor($number / 100); /* Hundreds (hecto) */
			$number -= $Hn * 100;
			$Dn = (int)floor($number / 10); /* Tens (deca) */
			$n = $number % 10; /* Ones */
			$res = "";
			if ($Bn) {
				$res .= Num::to_words($Bn) . " Billion";
			}
			if ($Gn) {
				$res .= (empty($res) ? "" : " ") . Num::to_words($Gn) . " Million";
			}
			if ($kn) {
				$res .= (empty($res) ? "" : " ") . Num::to_words($kn) . " Thousand";
			}
			if ($Hn) {
				$res .= (empty($res) ? "" : " ") . Num::to_words($Hn) . " Hundred";
			}
			$ones = array(
				"", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine", "Ten", "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen", "Seventeen",
				"Eightteen", "Nineteen"
			);
			$tens = array("", "", "Twenty", "Thirty", "Fourty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety");
			if ($Dn || $n) {
				if (!empty($res)) {
					$res .= " and ";
				}
				if ($Dn < 2) {
					$res .= $ones[$Dn * 10 + $n];
				}
				else {
					$res .= $tens[$Dn];
					if ($n) {
						$res .= "-" . $ones[$n];
					}
				}
			}
			if (empty($res)) {
				$res = "zero";
			}
			return $res;
		}
	}
