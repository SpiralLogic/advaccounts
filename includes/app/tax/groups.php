<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   adv.accounts.app
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  class Tax_Groups {

    static public function clear_shipping_tax_group() {
      $sql = "UPDATE tax_groups SET tax_shipping=0 WHERE 1";
      DB::query($sql, "could not update tax_shipping fields");
    }
    /**
     * @static
     *
     * @param $name
     * @param $tax_shipping
     * @param $taxes
     * @param $rates
     */
    static public function add($name, $tax_shipping, $taxes, $rates) {
      DB::begin();
      if ($tax_shipping) // only one tax group for shipping
      {
        static::clear_shipping_tax_group();
      }
      $sql = "INSERT INTO tax_groups (name, tax_shipping) VALUES (" . DB::escape($name) . ", " . DB::escape($tax_shipping) . ")";
      DB::query($sql, "could not add tax group");
      $id = DB::insert_id();
      static::add_items($id, $taxes, $rates);
      DB::commit();
    }
    /**
     * @static
     *
     * @param $id
     * @param $name
     * @param $tax_shipping
     * @param $taxes
     * @param $rates
     */
    static public function update($id, $name, $tax_shipping, $taxes, $rates) {
      DB::begin();
      if ($tax_shipping) // only one tax group for shipping
      {
        static::clear_shipping_tax_group();
      }
      $sql = "UPDATE tax_groups SET name=" . DB::escape($name) . ",tax_shipping=" . DB::escape($tax_shipping) . " WHERE id=" . DB::escape($id);
      DB::query($sql, "could not update tax group");
      static::delete_items($id);
      static::add_items($id, $taxes, $rates);
      DB::commit();
    }
    /**
     * @static
     *
     * @param bool $all
     *
     * @return null|PDOStatement
     */
    static public function get_all($all = FALSE) {
      $sql = "SELECT * FROM tax_groups";
      if (!$all) {
        $sql .= " WHERE !inactive";
      }
      return DB::query($sql, "could not get all tax group");
    }
    /**
     * @static
     *
     * @param $type_id
     *
     * @return ADV\Core\DB\Query_Result|Array
     */
    static public function get($type_id) {
      $sql = "SELECT * FROM tax_groups WHERE id=" . DB::escape($type_id);
      $result = DB::query($sql, "could not get tax group");
      return DB::fetch($result);
    }
    /**
     * @static
     *
     * @param $id
     */
    static public function delete($id) {
      DB::begin();
      $sql = "DELETE FROM tax_groups WHERE id=" . DB::escape($id);
      DB::query($sql, "could not delete tax group");
      static::delete_items($id);
      DB::commit();
    }
    /**
     * @static
     *
     * @param $id
     * @param $items
     * @param $rates
     */
    static public function add_items($id, $items, $rates) {
      for ($i = 0; $i < count($items); $i++) {
        $sql
          = "INSERT INTO tax_group_items (tax_group_id, tax_type_id, rate)
			VALUES (" . DB::escape($id) . ", " . DB::escape($items[$i]) . ", " . $rates[$i] . ")";
        DB::query($sql, "could not add item tax group item");
      }
    }
    /**
     * @static
     *
     * @param $id
     */
    static public function delete_items($id) {
      $sql = "DELETE FROM tax_group_items WHERE tax_group_id=" . DB::escape($id);
      DB::query($sql, "could not delete item tax group items");
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return null|PDOStatement
     */
    static public function get_for_item($id) {
      $sql
        = "SELECT tax_group_items.*, tax_types.name AS tax_type_name, tax_types.rate,
		tax_types.sales_gl_code, tax_types.purchasing_gl_code
		FROM tax_group_items, tax_types	WHERE tax_group_id=" . DB::escape($id) . "	AND tax_types.id=tax_type_id";
      return DB::query($sql, "could not get item tax type group items");
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return array
     */
    static public function get_items_as_array($id) {
      $ret_tax_array = array();
      $tax_group_items = static::get_for_item($id);
      while ($tax_group_item = DB::fetch($tax_group_items)) {
        $index = $tax_group_item['tax_type_id'];
        $ret_tax_array[$index]['tax_type_id'] = $tax_group_item['tax_type_id'];
        $ret_tax_array[$index]['tax_type_name'] = $tax_group_item['tax_type_name'];
        $ret_tax_array[$index]['sales_gl_code'] = $tax_group_item['sales_gl_code'];
        $ret_tax_array[$index]['purchasing_gl_code'] = $tax_group_item['purchasing_gl_code'];
        $ret_tax_array[$index]['rate'] = $tax_group_item['rate'];
        $ret_tax_array[$index]['Value'] = 0;
      }
      return $ret_tax_array;
    }
    /**
     * @static
     * @return null|PDOStatement
     */
    static public function get_shipping_items() {
      $sql
        = "SELECT tax_group_items.*, tax_types.name AS tax_type_name, tax_types.rate,
		tax_types.sales_gl_code, tax_types.purchasing_gl_code
		FROM tax_group_items, tax_types, tax_groups
		WHERE tax_groups.tax_shipping=1
		AND tax_groups.id=tax_group_id
		AND tax_types.id=tax_type_id";
      return DB::query($sql, "could not get shipping tax group items");
    }
    /**
     * @static
     * @return array
     */
    static public function for_shipping_as_array() {
      $ret_tax_array = array();
      $tax_group_items = static::get_shipping_items();
      while ($tax_group_item = DB::fetch($tax_group_items)) {
        $index = $tax_group_item['tax_type_id'];
        $ret_tax_array[$index]['tax_type_id'] = $tax_group_item['tax_type_id'];
        $ret_tax_array[$index]['tax_type_name'] = $tax_group_item['tax_type_name'];
        $ret_tax_array[$index]['sales_gl_code'] = $tax_group_item['sales_gl_code'];
        $ret_tax_array[$index]['purchasing_gl_code'] = $tax_group_item['purchasing_gl_code'];
        $ret_tax_array[$index]['rate'] = $tax_group_item['rate'];
        $ret_tax_array[$index]['Value'] = 0;
      }
      return $ret_tax_array;
    }

    // TAX GROUPS
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param bool $none_option
     * @param bool $submit_on_change
     *
     * @return string
     */
    static public function select($name, $selected_id = NULL, $none_option = FALSE, $submit_on_change = FALSE) {
      $sql = "SELECT id, name FROM tax_groups";
      return select_box($name, $selected_id, $sql, 'id', 'name', array(
        'order' => 'id', 'spec_option' => $none_option,
        'spec_id' => ALL_NUMERIC,
        'select_submit' => $submit_on_change, 'async' => FALSE,
      ));
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $none_option
     * @param bool $submit_on_change
     */
    static public function cells($label, $name, $selected_id = NULL, $none_option = FALSE, $submit_on_change = FALSE) {
      if ($label != NULL) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo Tax_Groups::select($name, $selected_id, $none_option, $submit_on_change);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $none_option
     * @param bool $submit_on_change
     */
    static public function row($label, $name, $selected_id = NULL, $none_option = FALSE, $submit_on_change = FALSE) {
      echo "<tr><td class='label'>$label</td>";
      Tax_Groups::cells(NULL, $name, $selected_id, $none_option, $submit_on_change);
      echo "</tr>\n";
    }
  }


