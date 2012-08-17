<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Item_Category
  {
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
    public static function add($description, $tax_type_id, $sales_account, $cogs_account, $inventory_account, $adjustment_account, $assembly_account, $units, $mb_flag, $dim1, $dim2, $no_sale)
    {
      $sql
        = "INSERT INTO stock_category (description, dflt_tax_type,
			dflt_units, dflt_mb_flag, dflt_sales_act, dflt_cogs_act,
			dflt_inventory_act, dflt_adjustment_act, dflt_assembly_act,
			dflt_dim1, dflt_dim2, dflt_no_sale)
		VALUES (" . DB::_escape($description) . "," . DB::_escape($tax_type_id) . "," . DB::_escape($units) . "," . DB::_escape($mb_flag) . "," . DB::_escape($sales_account) . "," . DB::_escape($cogs_account) . "," . DB::_escape($inventory_account) . "," . DB::_escape($adjustment_account) . "," . DB::_escape($assembly_account) . "," . DB::_escape($dim1) . "," . DB::_escape($dim2) . "," . DB::_escape($no_sale) . ")";
      DB::_query($sql, "an item category could not be added");
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
    public static function update($id, $description, $tax_type_id, $sales_account, $cogs_account, $inventory_account, $adjustment_account, $assembly_account, $units, $mb_flag, $dim1, $dim2, $no_sale)
    {
      $sql = "UPDATE stock_category SET " . "description = " . DB::_escape($description) . "," . "dflt_tax_type = " . DB::_escape($tax_type_id) . "," . "dflt_units = " . DB::_escape($units) . "," . "dflt_mb_flag = " . DB::_escape($mb_flag) . "," . "dflt_sales_act = " . DB::_escape($sales_account) . "," . "dflt_cogs_act = " . DB::_escape($cogs_account) . "," . "dflt_inventory_act = " . DB::_escape($inventory_account) . "," . "dflt_adjustment_act = " . DB::_escape($adjustment_account) . "," . "dflt_assembly_act = " . DB::_escape($assembly_account) . "," . "dflt_dim1 = " . DB::_escape($dim1) . "," . "dflt_dim2 = " . DB::_escape($dim2) . "," . "dflt_no_sale = " . DB::_escape($no_sale) . "WHERE category_id = " . DB::_escape($id);
      DB::_query($sql, "an item category could not be updated");
    }
    /**
     * @static
     *
     * @param $id
     */
    public static function delete($id)
    {
      $sql = "DELETE FROM stock_category WHERE category_id=" . DB::_escape($id);
      DB::_query($sql, "an item category could not be deleted");
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return \ADV\Core\DB\Query\Result|Array
     */
    public static function get($id)
    {
      $sql    = "SELECT * FROM stock_category WHERE category_id=" . DB::_escape($id);
      $result = DB::_query($sql, "an item category could not be retrieved");
      return DB::_fetch($result);
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return mixed
     */
    public static function get_name($id)
    {
      $sql    = "SELECT description FROM stock_category WHERE category_id=" . DB::_escape($id);
      $result = DB::_query($sql, "could not get sales type");
      $row    = DB::_fetchRow($result);
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
    public static function select($name, $selected_id = null, $spec_opt = false, $submit_on_change = false)
    {
      $sql = "SELECT category_id, description, inactive FROM stock_category";
      return Forms::selectBox($name, $selected_id, $sql, 'category_id', 'description', array(
                                                                                            'order'         => 'category_id',
                                                                                            'spec_option'   => $spec_opt,
                                                                                            'spec_id'       => -1,
                                                                                            'select_submit' => $submit_on_change,
                                                                                            'async'         => true
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
    public static function cells($label, $name, $selected_id = null, $spec_opt = false, $submit_on_change = false)
    {
      if ($label != null) {
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
    public static function row($label, $name, $selected_id = null, $spec_opt = false, $submit_on_change = false)
    {
      echo "<tr><td class='label'>$label</td>";
      Item_Category::cells(null, $name, $selected_id, $spec_opt, $submit_on_change);
      echo "</tr>\n";
    }
  }


