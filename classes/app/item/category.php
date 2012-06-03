<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Item_Category {

    /**
     * @static
     *
     * @param $description
     * @param $tax_type_id
     * @param $sales_account
     * @param $cogs_account
     * @param $inventory_account
     * @param $adjustment_account
     * @param $assembly_account
     * @param $units
     * @param $mb_flag
     * @param $dim1
     * @param $dim2
     * @param $no_sale
     */
    public static function add($description, $tax_type_id, $sales_account, $cogs_account, $inventory_account, $adjustment_account, $assembly_account, $units, $mb_flag, $dim1, $dim2, $no_sale) {
      $sql = "INSERT INTO stock_category (description, dflt_tax_type,
			dflt_units, dflt_mb_flag, dflt_sales_act, dflt_cogs_act,
			dflt_inventory_act, dflt_adjustment_act, dflt_assembly_act,
			dflt_dim1, dflt_dim2, dflt_no_sale)
		VALUES (" . DB::escape($description) . "," . DB::escape($tax_type_id) . "," . DB::escape($units) . "," . DB::escape($mb_flag) . "," . DB::escape($sales_account) . "," . DB::escape($cogs_account) . "," . DB::escape($inventory_account) . "," . DB::escape($adjustment_account) . "," . DB::escape($assembly_account) . "," . DB::escape($dim1) . "," . DB::escape($dim2) . "," . DB::escape($no_sale) . ")";
      DB::query($sql, "an item category could not be added");
    }
    /**
     * @static
     *
     * @param $id
     * @param $description
     * @param $tax_type_id
     * @param $sales_account
     * @param $cogs_account
     * @param $inventory_account
     * @param $adjustment_account
     * @param $assembly_account
     * @param $units
     * @param $mb_flag
     * @param $dim1
     * @param $dim2
     * @param $no_sale
     */
    public static function update($id, $description, $tax_type_id, $sales_account, $cogs_account, $inventory_account, $adjustment_account, $assembly_account, $units, $mb_flag, $dim1, $dim2, $no_sale) {
      $sql = "UPDATE stock_category SET " . "description = " . DB::escape($description) . "," . "dflt_tax_type = " . DB::escape($tax_type_id) . "," . "dflt_units = " . DB::escape($units) . "," . "dflt_mb_flag = " . DB::escape($mb_flag) . "," . "dflt_sales_act = " . DB::escape($sales_account) . "," . "dflt_cogs_act = " . DB::escape($cogs_account) . "," . "dflt_inventory_act = " . DB::escape($inventory_account) . "," . "dflt_adjustment_act = " . DB::escape($adjustment_account) . "," . "dflt_assembly_act = " . DB::escape($assembly_account) . "," . "dflt_dim1 = " . DB::escape($dim1) . "," . "dflt_dim2 = " . DB::escape($dim2) . "," . "dflt_no_sale = " . DB::escape($no_sale) . "WHERE category_id = " . DB::escape($id);
      DB::query($sql, "an item category could not be updated");
    }
    /**
     * @static
     *
     * @param $id
     */
    public static function delete($id) {
      $sql = "DELETE FROM stock_category WHERE category_id=" . DB::escape($id);
      DB::query($sql, "an item category could not be deleted");
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return ADV\Core\DB\Query_Result|Array
     */
    public static function get($id) {
      $sql    = "SELECT * FROM stock_category WHERE category_id=" . DB::escape($id);
      $result = DB::query($sql, "an item category could not be retrieved");
      return DB::fetch($result);
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return mixed
     */
    public static function get_name($id) {
      $sql    = "SELECT description FROM stock_category WHERE category_id=" . DB::escape($id);
      $result = DB::query($sql, "could not get sales type");
      $row    = DB::fetch_row($result);
      return $row[0];
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param bool $spec_opt
     * @param bool $submit_on_change
     *
     * @return string
     */
    public static function select($name, $selected_id = NULL, $spec_opt = FALSE, $submit_on_change = FALSE) {
      $sql = "SELECT category_id, description, inactive FROM stock_category";
      return Form::selectBox($name, $selected_id, $sql, 'category_id', 'description', array(
        'order' => 'category_id', 'spec_option' => $spec_opt, 'spec_id' => -1, 'select_submit' => $submit_on_change, 'async' => TRUE
      ));
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $spec_opt
     * @param bool $submit_on_change
     */
    public static function cells($label, $name, $selected_id = NULL, $spec_opt = FALSE, $submit_on_change = FALSE) {
      if ($label != NULL) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo Item_Category::select($name, $selected_id, $spec_opt, $submit_on_change);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $spec_opt
     * @param bool $submit_on_change
     */
    public static function row($label, $name, $selected_id = NULL, $spec_opt = FALSE, $submit_on_change = FALSE) {
      echo "<tr><td class='label'>$label</td>";
      Item_Category::cells(NULL, $name, $selected_id, $spec_opt, $submit_on_change);
      echo "</tr>\n";
    }
  }


