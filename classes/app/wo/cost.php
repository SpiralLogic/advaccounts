<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class WO_Cost {

    /**
     * @static
     *
     * @param $stock_id
     * @param $qty
     * @param $date_
     */
    public static function add_material($stock_id, $qty, $date_) {
      $m_cost = 0;
      $result = WO::get_bom($stock_id);
      while ($bom_item = DB::fetch($result)) {
        $standard_cost = Item_Price::get_standard_cost($bom_item['component']);
        $m_cost += ($bom_item['quantity'] * $standard_cost);
      }
      $dec = User::price_dec();
      Num::price_decimal($m_cost, $dec);
      $sql           = "SELECT material_cost FROM stock_master WHERE stock_id = " . DB::escape($stock_id);
      $result        = DB::query($sql);
      $myrow         = DB::fetch($result);
      $material_cost = $myrow['material_cost'];
      $qoh           = Item::get_qoh_on_date($stock_id, NULL, $date_);
      if ($qoh < 0) {
        $qoh = 0;
      }
      if ($qoh + $qty != 0) {
        $material_cost = ($qoh * $material_cost + $qty * $m_cost) / ($qoh + $qty);
      }
      $material_cost = Num::round($material_cost, $dec);
      $sql           = "UPDATE stock_master SET material_cost=$material_cost
		WHERE stock_id=" . DB::escape($stock_id);
      DB::query($sql, "The cost details for the inventory item could not be updated");
    }
    /**
     * @static
     *
     * @param $stock_id
     * @param $qty
     * @param $date_
     * @param $costs
     */
    public static function add_overhead($stock_id, $qty, $date_, $costs) {
      $dec = User::price_dec();
      Num::price_decimal($costs, $dec);
      if ($qty != 0) {
        $costs /= $qty;
      }
      $sql           = "SELECT overhead_cost FROM stock_master WHERE stock_id = " . DB::escape($stock_id);
      $result        = DB::query($sql);
      $myrow         = DB::fetch($result);
      $overhead_cost = $myrow['overhead_cost'];
      $qoh           = Item::get_qoh_on_date($stock_id, NULL, $date_);
      if ($qoh < 0) {
        $qoh = 0;
      }
      if ($qoh + $qty != 0) {
        $overhead_cost = ($qoh * $overhead_cost + $qty * $costs) / ($qoh + $qty);
      }
      $overhead_cost = Num::round($overhead_cost, $dec);
      $sql           = "UPDATE stock_master SET overhead_cost=" . DB::escape($overhead_cost) . "
		WHERE stock_id=" . DB::escape($stock_id);
      DB::query($sql, "The cost details for the inventory item could not be updated");
    }
    /**
     * @static
     *
     * @param $stock_id
     * @param $qty
     * @param $date_
     * @param $costs
     */
    public static function add_labour($stock_id, $qty, $date_, $costs) {
      $dec = User::price_dec();
      Num::price_decimal($costs, $dec);
      if ($qty != 0) {
        $costs /= $qty;
      }
      $sql         = "SELECT labour_cost FROM stock_master WHERE stock_id = " . DB::escape($stock_id);
      $result      = DB::query($sql);
      $myrow       = DB::fetch($result);
      $labour_cost = $myrow['labour_cost'];
      $qoh         = Item::get_qoh_on_date($stock_id, NULL, $date_);
      if ($qoh < 0) {
        $qoh = 0;
      }
      if ($qoh + $qty != 0) {
        $labour_cost = ($qoh * $labour_cost + $qty * $costs) / ($qoh + $qty);
      }
      $labour_cost = Num::round($labour_cost, $dec);
      $sql         = "UPDATE stock_master SET labour_cost=" . DB::escape($labour_cost) . "
		WHERE stock_id=" . DB::escape($stock_id);
      DB::query($sql, "The cost details for the inventory item could not be updated");
    }
    /**
     * @static
     *
     * @param $stock_id
     * @param $qty
     * @param $date_
     * @param $costs
     */
    public static function add_issue($stock_id, $qty, $date_, $costs) {
      if ($qty != 0) {
        $costs /= $qty;
      }
      $sql           = "SELECT material_cost FROM stock_master WHERE stock_id = " . DB::escape($stock_id);
      $result        = DB::query($sql);
      $myrow         = DB::fetch($result);
      $material_cost = $myrow['material_cost'];
      $dec           = User::price_dec();
      Num::price_decimal($material_cost, $dec);
      $qoh = Item::get_qoh_on_date($stock_id, NULL, $date_);
      if ($qoh < 0) {
        $qoh = 0;
      }
      if ($qoh + $qty != 0) {
        $material_cost = ($qty * $costs) / ($qoh + $qty);
      }
      $material_cost = Num::round($material_cost, $dec);
      $sql           = "UPDATE stock_master SET material_cost=material_cost+" . DB::escape($material_cost) . " WHERE stock_id=" . DB::escape($stock_id);
      DB::query($sql, "The cost details for the inventory item could not be updated");
    }
  }


