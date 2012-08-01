<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Item_Purchase
  {
    /**
     * @static
     *
     * @param      $creditor_id
     * @param      $stock_id
     * @param      $price
     * @param      $suppliers_uom
     * @param      $conversion_factor
     * @param      $supplier_description
     * @param null $stockid
     */
    public static function add($creditor_id, $stock_id, $price, $suppliers_uom, $conversion_factor, $supplier_description, $stockid = null)
    {
      if ($stockid == null) {
        $stockid = Item::get_stockid($stock_id);
      }
      $sql
        = "INSERT INTO purch_data (creditor_id, stockid, stock_id, price, suppliers_uom,
        conversion_factor, supplier_description) VALUES (";
      $sql .= DB::escape($creditor_id) . ", " . DB::escape($stock_id) . ", " . DB::escape($stockid) . ", " . $price . ", " . DB::escape($suppliers_uom) . ", " . $conversion_factor . ", " . DB::escape($supplier_description) . ")";
      DB::query($sql, "The supplier purchasing details could not be added");
    }
    /**
     * @static
     *
     * @param $selected_id
     * @param $stock_id
     * @param $price
     * @param $suppliers_uom
     * @param $conversion_factor
     * @param $supplier_description
     */
    public static function update($selected_id, $stock_id, $price, $suppliers_uom, $conversion_factor, $supplier_description)
    {
      $sql = "UPDATE purch_data SET price=" . $price . ",
        suppliers_uom=" . DB::escape($suppliers_uom) . ",
        conversion_factor=" . $conversion_factor . ",
        supplier_description=" . DB::escape($supplier_description) . "
        WHERE stock_id=" . DB::escape($stock_id) . " AND
        creditor_id=" . DB::escape($selected_id);
      DB::query($sql, "The supplier purchasing details could not be updated");
    }
    /**
     * @static
     *
     * @param $selected_id
     * @param $stock_id
     */
    public static function delete($selected_id, $stock_id)
    {
      $sql = "DELETE FROM purch_data WHERE creditor_id=" . DB::escape($selected_id) . "
        AND stock_id=" . DB::escape($stock_id);
      DB::query($sql, "could not delete purchasing data");
    }
    /**
     * @static
     *
     * @param $stock_id
     *
     * @return null|PDOStatement
     */
    public static function getAll($stock_id)
    {
      $sql
        = "SELECT purch_data.*,suppliers.name, suppliers.curr_code
        FROM purch_data INNER JOIN suppliers
        ON purch_data.creditor_id=suppliers.creditor_id
        WHERE stock_id = " . DB::escape($stock_id);

      return DB::query($sql, "The supplier purchasing details for the selected part could not be retrieved");
    }
    /**
     * @static
     *
     * @param $selected_id
     * @param $stock_id
     *
     * @return \ADV\Core\DB\Query\Result|Array
     */
    public static function get($selected_id, $stock_id)
    {
      $sql
              = "SELECT purch_data.*,suppliers.name FROM purch_data
        INNER JOIN suppliers ON purch_data.creditor_id=suppliers.creditor_id
        WHERE purch_data.creditor_id=" . DB::escape($selected_id) . "
        AND purch_data.stock_id=" . DB::escape($stock_id);
      $result = DB::query($sql, "The supplier purchasing details for the selected supplier and item could not be retrieved");

      return DB::fetch($result);
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param bool $all_option
     * @param bool $submit_on_change
     * @param bool $all
     * @param bool $editkey
     * @param bool $legacy
     *
     * @return string
     */
    public static function select($name, $selected_id = null, $all_option = false, $submit_on_change = false, $all = false, $editkey = false, $legacy = false)
    {
      return Item::select($name, $selected_id, $all_option, $submit_on_change, array(
                                                                                    'where'         => "mb_flag!= '" . STOCK_MANUFACTURE . "'",
                                                                                    'show_inactive' => $all,
                                                                                    'editable'      => false
                                                                               ), false, $legacy);
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $all_option
     * @param bool $submit_on_change
     * @param bool $editkey
     */
    public static function cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $editkey = false)
    {
      if ($label != null) {
        echo "<td>$label</td>\n";
      }
      echo Item::select($name, $selected_id, $all_option, $submit_on_change, array(
                                                                                  'where'       => "mb_flag!= '" . STOCK_MANUFACTURE . "'",
                                                                                  'editable'    => 30,
                                                                                  'cells'       => true,
                                                                                  'description' => '',
                                                                                  'class'       => 'auto'
                                                                             ));
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $all_option
     * @param bool $submit_on_change
     * @param bool $editkey
     */
    public static function row($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $editkey = false)
    {
      echo "<tr><td class='label'>$label</td>";
      Item_Purchase::cells(null, $name, $selected_id, $all_option, $submit_on_change, $editkey);
      echo "</tr>\n";
    }
  }
