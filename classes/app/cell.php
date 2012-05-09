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

  class Cell {

    /**
     * @param        $label
     * @param string $params
     * @param null   $id
     */
    static function amountDecimal($label, $params = "", $id = NULL) {
      $dec = 0;
      Cell::label(Num::price_decimal($label, $dec), ' class="right nowrap"' . $params, $id);
    }

    /**
     * @param        $label
     * @param bool   $bold
     * @param string $params
     * @param null   $id
     */
    static function amount($label, $bold = FALSE, $params = "", $id = NULL) {
      if ($bold) {
        Cell::label("<span class='bold'>" . Num::price_format($label) . "</span>", "class='amount'" . $params, $id);
      }
      else {
        Cell::label(Num::price_format($label), "class='amount'" . $params, $id);
      }
    }

    /**
     * @param        $label
     * @param string $params
     * @param null   $id
     */
    static function description($label, $params = "", $id = NULL) {
      Cell::label($label, $params . " class='desc'", $id);
    }

    /**
     * @param        $label
     * @param string $params
     * @param null   $id
     */
    static function email($label, $params = "", $id = NULL) {
      Cell::label("<a href='mailto:$label'>$label</a>", $params, $id);
    }

    /**
     * @param        $label
     * @param        $value
     * @param string $params
     * @param string $params2
     * @param null   $id
     */
    static function labels($label, $value, $params = "", $params2 = "", $id = NULL) {
      if ($label != NULL) {
        echo "<td class='label' {$params}>{$label}</td>\n";
      }
      Cell::label($value, $params2, $id);
    }

    /**
     * @param        $label
     * @param string $params
     */
    static function labelHeader($label, $params = "") {
      echo "<th $params>$label</th>\n";
    }

    /**
     * @param        $label
     * @param string $params
     * @param null   $id
     *
     * @return mixed
     */
    static function label($label, $params = "", $id = NULL) {

      if (!empty($id)) {
        $params .= " id='$id'";
        Ajax::i()->addUpdate($id, $id, $label);
      }
      echo "<td $params >$label</td>\n";
      return $label;
    }

    /**
     * @param      $label
     * @param bool $bold
     * @param null $id
     */
    static function percent($label, $bold = FALSE, $id = NULL) {
      if ($bold) {
        Cell::label("<span class='bold'>" . Num::percent_format($label) . "</span>", ' class="right nowrap"', $id);
      }
      else {
        Cell::label(Num::percent_format($label), ' class="right nowrap"', $id);
      }
    }

    /**
     * @param      $label
     * @param bool $bold
     * @param null $dec
     * @param null $id
     */
    static function qty($label, $bold = FALSE, $dec = NULL, $id = NULL) {
      if (!isset($dec)) {
        $dec = User::qty_dec();
      }
      if ($bold) {
        Cell::label("<span class='bold'>" . Num::format($label, $dec) . "</span>", ' class="right nowrap"', $id);
      }
      else {
        Cell::label(Num::format(Num::round($label), $dec), ' class="right nowrap"', $id);
      }
    }

    /**
     * @param        $label
     * @param bool   $bold
     * @param string $params
     * @param null   $id
     */
    static function unit_amount($label, $bold = FALSE, $params = "", $id = NULL) {
      if ($bold) {
        Cell::label("<span class='bold'>" . unit_price_format($label) . "</span>", ' class="right nowrap"' . $params, $id);
      }
      else {
        Cell::label(unit_price_format($label), ' class="right nowrap"' . $params, $id);
      }
    }

    /**
     * @param $value
     */
    static function debitOrCredit($value) {
      $value = Num::round($value, User::price_dec());
      if ($value >= 0) {
        Cell::amount($value);
        Cell::label("");
      }
      elseif ($value < 0) {
        Cell::label("");
        Cell::amount(abs($value));
      }
    }
  }

