<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Inv_Movement
  {
    /**
     * @static
     *
     * @param $name
     */
    public static function add_type($name)
    {
      $sql
        = "INSERT INTO movement_types (name)
        VALUES (" . DB::escape($name) . ")";
      DB::query($sql, "could not add item movement type");
    }
    /**
     * @static
     *
     * @param $type_id
     * @param $name
     */
    public static function update_type($type_id, $name)
    {
      $sql = "UPDATE movement_types SET name=" . DB::escape($name) . "
            WHERE id=" . DB::escape($type_id);
      DB::query($sql, "could not update item movement type");
    }
    /**
     * @static
     *
     * @param bool $all
     *
     * @return null|PDOStatement
     */
    public static function get_all_types($all = false)
    {
      $sql = "SELECT * FROM movement_types";
      if (!$all) {
        $sql .= " WHERE !inactive";
      }

      return DB::query($sql, "could not get all item movement type");
    }
    /**
     * @static
     *
     * @param $type_id
     *
     * @return ADV\Core\DB\Query_Result|Array
     */
    public static function get_type($type_id)
    {
      $sql    = "SELECT * FROM movement_types WHERE id=" . DB::escape($type_id);
      $result = DB::query($sql, "could not get item movement type");

      return DB::fetch($result);
    }
    /**
     * @static
     *
     * @param $type_id
     */
    public static function delete($type_id)
    {
      $sql = "DELETE FROM movement_types WHERE id=" . DB::escape($type_id);
      DB::query($sql, "could not delete item movement type");
    }
    /**
     * @static
     *
     * @param      $type
     * @param      $type_no
     * @param bool $visible
     *
     * @return null|PDOStatement
     */
    public static function get($type, $type_no, $visible = false)
    {
      $sql = "SELECT stock_moves.*, stock_master.description, " . "stock_master.units,locations.location_name," . "stock_master.material_cost + " . "stock_master.labour_cost + " . "stock_master.overhead_cost AS FixedStandardCost
                FROM stock_moves,locations,stock_master
                WHERE stock_moves.stock_id = stock_master.stock_id
                AND locations.loc_code=stock_moves.loc_code
                AND type=" . DB::escape($type) . " AND trans_no=" . DB::escape($type_no) . " ORDER BY trans_id";
      if ($visible) {
        $sql .= " AND stock_moves.visible=1";
      }

      return DB::query($sql, "Could not get stock moves");
    }
    /**
     * @static
     *
     * @param $type
     * @param $type_no
     */
    public static function void($type, $type_no)
    {
      $sql
        = "UPDATE stock_moves SET qty=0, price=0, discount_percent=0,
                standard_cost=0	WHERE type=" . DB::escape($type) . " AND trans_no=" . DB::escape($type_no);
      DB::query($sql, "Could not void stock moves");
    }
    /**
     * @static
     *
     * @param        $type
     * @param        $stock_id
     * @param        $trans_no
     * @param        $location
     * @param        $date_
     * @param        $reference
     * @param        $quantity
     * @param        $std_cost
     * @param int    $person_id
     * @param int    $show_or_hide
     * @param int    $price
     * @param int    $discount_percent
     * @param string $error_msg
     *
     * @return null|string
     */
    public static function add($type, $stock_id, $trans_no, $location, $date_, $reference, $quantity, $std_cost, $person_id = 0, $show_or_hide = 1, $price = 0, $discount_percent = 0, $error_msg = "")
    {
      // do not add a stock move if it's a non-inventory item
      if (!Item::is_inventory_item($stock_id)) {
        return null;
      }
      $date = Dates::date2sql($date_);
      $sql
            = "INSERT INTO stock_moves (stock_id, trans_no, type, loc_code,
            tran_date, person_id, reference, qty, standard_cost, visible, price,
            discount_percent) VALUES (" . DB::escape($stock_id) . ", " . DB::escape($trans_no) . ", " . DB::escape($type) . ",	" . DB::escape($location) . ", '$date', " . DB::escape($person_id) . ", " . DB::escape($reference) . ", " . DB::escape($quantity) . ", " . DB::escape($std_cost) . "," . DB::escape($show_or_hide) . ", " . DB::escape($price) . ", " . DB::escape($discount_percent) . ")";
      if ($error_msg == "") {
        $error_msg = "The stock movement record cannot be inserted";
      }
      DB::query($sql, $error_msg);

      return DB::insert_id();
    }
    /***
     * @static
     *
     * @param     $type             is 10 (invoice) or 11 (credit)
     * @param     $stock_id
     * @param     $trans_id
     * @param     $location
     * @param     $date_
     * @param     $reference
     * @param     $quantity         is used as is (if it's neg it's neg, if it's pos it's pos)
     * @param     $std_cost         is in home currency
     * @param int $show_or_hide     1 show this item in invoice/credit views, 0 to hide it (used for write-off items)
     * @param int $price            in customer's currency
     * @param int $discount_percent
     *
     * @return mixed|null
     */
    public static function add_for_debtor($type, $stock_id, $trans_id, $location, $date_, $reference, $quantity, $std_cost, $show_or_hide = 1, $price = 0, $discount_percent = 0)
    {
      return Inv_Movement::add($type, $stock_id, $trans_id, $location, $date_, $reference, $quantity, $std_cost, 0, $show_or_hide, $price, $discount_percent, "The customer stock movement record cannot be inserted");
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     */
    public static function row($label, $name, $selected_id = null)
    {
      echo "<tr><td class='label'>$label</td>";
      Inv_Movement::types_cells(null, $name, $selected_id);
      echo "</tr>\n";
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     *
     * @return string
     */
    public static function types($name, $selected_id = null)
    {
      $sql = "SELECT id, name FROM movement_types";

      return Forms::selectBox($name, $selected_id, $sql, 'id', 'name', array());
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     */
    public static function types_cells($label, $name, $selected_id = null)
    {
      if ($label != null) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo Inv_Movement::types($name, $selected_id);
      echo "</td>\n";
    }
  }
