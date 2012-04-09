<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   adv.accounts.app
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  class Item_Line {

    public $stock_id;
    public $description;
    public $units;
    public $mb_flag;
    public $quantity;
    public $price;
    public $standard_cost;
    /**
     * @param      $stock_id
     * @param      $qty
     * @param null $standard_cost
     * @param null $description
     */
    function __construct($stock_id, $qty, $standard_cost = NULL, $description = NULL) {
      $item_row = Item::get($stock_id);
      if ($item_row == NULL) {
        Errors::db_error("invalid item added to order : $stock_id", "");
      }
      $this->mb_flag = $item_row["mb_flag"];
      $this->units = $item_row["units"];
      if ($description == NULL) {
        $this->description = $item_row["description"];
      }
      else {
        $this->description = $description;
      }
      if ($standard_cost == NULL) {
        $this->standard_cost = $item_row["actual_cost"];
      }
      else {
        $this->standard_cost = $standard_cost;
      }
      $this->stock_id = $stock_id;
      $this->quantity = $qty;
      //$this->price = $price;
      $this->price = 0;
    }
    /**
     * @param $location
     * @param $date_
     * @param $reverse
     *
     * @return Item_Line|null
     */
    function check_qoh($location, $date_, $reverse) {
      if (!DB_Company::get_pref('allow_negative_stock')) {
        if (WO::has_stock_holding($this->mb_flag)) {
          $quantity = $this->quantity;
          if ($reverse) {
            $quantity = -$this->quantity;
          }
          if ($quantity >= 0) {
            return NULL;
          }
          $qoh = Item::get_qoh_on_date($this->stock_id, $location, $date_);
          if ($quantity + $qoh < 0) {
            return $this;
          }
        }
      }
      return NULL;
    }
    /**
     * @param $field
     */
    function start_focus($field) {
      Ajax::i()->activate('items_table');
       JS::set_focus($field);
    }
  }




