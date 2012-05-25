<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.core
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  namespace ADV\Core;
  /**

   */
  class Num {

    use Traits\Singleton;

    /**
     * @var int
     */
    public static $price_dec = 2;
    /**
     * @var string
     */
    public static $tho_sep = ',';
    /**
     * @var string
     */
    public static $dec_sep = '.';
    /**
     * @var string
     */
    public static $exrate_dec = '.';
    /**
     * @var
     */
    public static $percent_dec;

    /**

     */
    protected function __construct() {
      static::$price_dec   = \User::prefs()->price_dec();
      static::$tho_sep     = \User::tho_sep();
      static::$dec_sep     = \User::dec_sep();
      static::$exrate_dec  = \User::prefs()->exrate_dec();
      static::$percent_dec = \User::prefs()->percent_dec();
    }

    /**
     * @static
     *
     * @param $number
     *
     * @return int|string
     */
    public static function  price_format($number) {
      static::i();
      return static::format(static::round($number, static::$price_dec + 2), static::$price_dec);
    }
    /**
     * @static
     *
     * @param $number
     * @param $dec
     *
     * @return int|string
     */
    public static function  price_decimal($number, &$dec) {
      static::i();
      $dec = static::$price_dec;
      $str = strval($number);
      $pos = strpos($str, '.');
      if ($pos !== FALSE) {
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
     * @param     $number
     * @param int $decimals
     *
     * @return float
     */
    public static function  round($number, $decimals = 0) {
      return round($number, $decimals, PHP_ROUND_HALF_EVEN);
    }
    /**
     * @static
     *
     * @param     $number
     * @param int $decimals
     *
     * @return int|string
     */
    public static function  format($number, $decimals = 0) {
      static::i();
      $tsep = static::$tho_sep;
      $dsep = static::$dec_sep;
      //return number_format($number, $decimals, $dsep,	$tsep);
      $delta  = ($number < 0 ? -.0000000001 : .0000000001);
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
    public static function  exrate_format($number) {
      static::i();
      return static::format($number, static::$exrate_dec);
    }
    /**
     * @static
     *
     * @param $number
     *
     * @return int|string
     */
    public static function  percent_format($number) {
      static::i();
      return static::format($number, static::$percent_dec);
    }
    /**
     * @static
     *
     * @param $price
     * @param $round_to
     *
     * @return float|int
     */
    public static function round_to_nearest($price, $round_to) {
      if ($price == 0) {
        return 0;
      }
      $pow = pow(10, static::$price_dec);
      if ($pow >= $round_to) {
        $mod = ($pow % $round_to);
      }
      else {
        $mod = ($round_to % $pow);
      }
      if ($mod != 0) {
        $price = ceil($price) - ($pow - $round_to) / $pow;
      }
      else {
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
    public static function to_words($number) {
      $Bn = floor($number / 1000000000); /* Billions (giga) */
      $number -= $Bn * 1000000000;
      $Gn = floor($number / 1000000); /* Millions (mega) */
      $number -= $Gn * 1000000;
      $kn = floor($number / 1000); /* Thousands (kilo) */
      $number -= $kn * 1000;
      $Hn = floor($number / 100); /* Hundreds (hecto) */
      $number -= $Hn * 100;
      $Dn  = (int) floor($number / 10); /* Tens (deca) */
      $n   = $number % 10; /* Ones */
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
        "", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine", "Ten", "Eleven", "Twelve", "Thirteen",
        "Fourteen", "Fifteen", "Sixteen", "Seventeen",
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
