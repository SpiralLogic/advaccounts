<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: advanced
   * Date: 9/05/12
   * Time: 2:43 PM
   * To change this template use File | Settings | File Templates.
   */
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core;
  class Cell
  {
    /**
     * @param        $label
     * @param string $params
     * @param null   $id
     */
    public static function amountDecimal($label, $params = "", $id = null) {
      $dec = 0;
      Cell::label(Num::priceDecimal($label, $dec), ' class="right nowrap"' . $params, $id);
    }
    /**
     * @param        $label
     * @param bool   $bold
     * @param string $params
     * @param null   $id
     */
    public static function amount($label, $bold = false, $params = "", $id = null) {
      if ($bold) {
        Cell::label("<span class='bold'>" . Num::priceFormat($label) . "</span>", "class='amount'" . $params, $id);
      } else {
        Cell::label(Num::priceFormat($label), "class='amount'" . $params, $id);
      }
    }
    /**
     * @param        $label
     * @param string $params
     * @param null   $id
     */
    public static function description($label, $params = "", $id = null) {
      Cell::label($label, $params . " class='desc'", $id);
    }
    /**
     * @param        $label
     * @param string $params
     * @param null   $id
     */
    public static function email($label, $params = "", $id = null) {
      Cell::label("<a href='mailto:$label'>$label</a>", $params, $id);
    }
    /**
     * @param        $label
     * @param        $value
     * @param string $params
     * @param string $params2
     * @param null   $id
     */
    public static function labels($label, $value, $params = "", $params2 = "", $id = null) {
      if ($label != null) {
        echo "<td class='label' {$params}>{$label}</td>\n";
      }
      Cell::label($value, $params2, $id);
    }
    /**
     * @param        $label
     * @param string $params
     */
    public static function labelHeader($label, $params = "") {
      echo "<th $params>$label</th>\n";
    }
    /**
     * @param        $label
     * @param string $params
     * @param null   $id
     *
     * @return mixed
     */
    public static function label($label, $params = "", $id = null) {
      if (!empty($id)) {
        $params .= " id='$id'";
        Ajax::addUpdate($id, $id, $label);
      }
      echo "<td $params >$label</td>\n";

      return $label;
    }
    /**
     * @param      $label
     * @param bool $bold
     * @param null $id
     */
    public static function percent($label, $bold = false, $id = null) {
      if ($bold) {
        Cell::label("<span class='bold'>" . Num::percentFormat($label) . "</span>", ' class="right nowrap"', $id);
      } else {
        Cell::label(Num::percentFormat($label), ' class="right nowrap"', $id);
      }
    }
    /**
     * @param      $label
     * @param bool $bold
     * @param null $dec
     * @param null $id
     */
    public static function qty($label, $bold = false, $dec = null, $id = null) {
      if (!isset($dec)) {
        $dec = User::qty_dec();
      }
      if ($bold) {
        Cell::label("<span class='bold'>" . Num::format($label, $dec) . "</span>", ' class="right nowrap"', $id);
      } else {
        Cell::label(Num::format(Num::round($label), $dec), ' class="right nowrap"', $id);
      }
    }
    /**
     * @param        $label
     * @param bool   $bold
     * @param string $params
     * @param null   $id
     */
    public static function unit_amount($label, $bold = false, $params = "", $id = null) {
      if ($bold) {
        Cell::label("<span class='bold'>" . Num::priceForamt($label) . "</span>", ' class="right nowrap"' . $params, $id);
      } else {
        Cell::label(Num::priceForamt($label), ' class="right nowrap"' . $params, $id);
      }
    }
    /**
     * @param $value
     */
    public static function debitOrCredit($value) {
      $value = Num::round($value, User::price_dec());
      if ($value >= 0) {
        Cell::amount($value);
        Cell::label("");
      } elseif ($value < 0) {
        Cell::label("");
        Cell::amount(abs($value));
      }
    }
  }

