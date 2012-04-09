<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   adv.accounts.app
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  class Sales_Type {
    /**
     * @static
     *
     * @param $name
     * @param $tax_included
     * @param $factor
     */
    static public function add($name, $tax_included, $factor) {
      $sql = "INSERT INTO sales_types (sales_type,tax_included,factor) VALUES (" . DB::escape($name) . ","
        . DB::escape($tax_included) . "," . DB::escape($factor) . ")";
      DB::query($sql, "could not add sales type");
    }
    /**
     * @static
     *
     * @param $id
     * @param $name
     * @param $tax_included
     * @param $factor
     */
    static public function update($id, $name, $tax_included, $factor) {
      $sql = "UPDATE sales_types SET sales_type = " . DB::escape($name) . ",
	tax_included =" . DB::escape($tax_included) . ", factor=" . DB::escape($factor) . " WHERE id = " . DB::escape($id);
      DB::query($sql, "could not update sales type");
    }
    /**
     * @static
     *
     * @param bool $all
     *
     * @return null|PDOStatement
     */
    static public function get_all($all = FALSE) {
      $sql = "SELECT * FROM sales_types";
      if (!$all) {
        $sql .= " WHERE !inactive";
      }
      return DB::query($sql, "could not get all sales types");
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return ADV\Core\DB\Query_Result|Array
     */
    static public function get($id) {
      $sql = "SELECT * FROM sales_types WHERE id=" . DB::escape($id);
      $result = DB::query($sql, "could not get sales type");
      return DB::fetch($result);
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return mixed
     */
    static public function get_name($id) {
      $sql = "SELECT sales_type FROM sales_types WHERE id=" . DB::escape($id);
      $result = DB::query($sql, "could not get sales type");
      $row = DB::fetch_row($result);
      return $row[0];
    }
    /**
     * @static
     *
     * @param $id
     */
    static public function delete($id) {
      $sql = "DELETE FROM sales_types WHERE id=" . DB::escape($id);
      DB::query($sql, "The Sales type record could not be deleted");
      $sql = "DELETE FROM prices WHERE sales_type_id=" . DB::escape($id);
      DB::query($sql, "The Sales type prices could not be deleted");
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param bool $submit_on_change
     * @param bool $special_option
     *
     * @return string
     */
    static public function  select($name, $selected_id = NULL, $submit_on_change = FALSE, $special_option = FALSE) {
      $sql = "SELECT id, sales_type, inactive FROM sales_types";
      return select_box($name, $selected_id, $sql, 'id', 'sales_type', array(
        'spec_option' => $special_option === TRUE ? _("All Sales Types") :
          $special_option, 'spec_id' => 0, 'select_submit' => $submit_on_change, //	 'async' => false,
      ));
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $submit_on_change
     * @param bool $special_option
     */
    static public function  cells($label, $name, $selected_id = NULL, $submit_on_change = FALSE, $special_option = FALSE) {
      if ($label != NULL) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo static::select($name, $selected_id, $submit_on_change, $special_option);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $submit_on_change
     * @param bool $special_option
     */
    static public function  row($label, $name, $selected_id = NULL, $submit_on_change = FALSE, $special_option = FALSE) {
      echo "<tr><td class='label'>$label</td>";
      static::cells(NULL, $name, $selected_id, $submit_on_change, $special_option);
      echo "</tr>\n";
    }
    /**
     * @static
     * @return bool
     */
    static public function can_process() {
     if (strlen($_POST['sales_type']) == 0) {
       Event::error(_("The sales type description cannot be empty."));
       JS::set_focus('sales_type');
       return FALSE;
     }
     if (!Validation::is_num('factor', 0)) {
       Event::error(_("Calculation factor must be valid positive number."));
       JS::set_focus('factor');
       return FALSE;
     }
     return TRUE;
   }
  }


